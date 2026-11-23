<?php

namespace App\Http\Controllers;

use App\Models\Availability;
use App\Models\Booking;
use App\Models\Message;
use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CounselorPanelController extends Controller
{
    public function dashboard()
    {
        $user = auth()->user();
        $profile = $user->profile;

        $base = $user->counselorBookings()->with(['student', 'problemType', 'report']);

        return view('counselor.dashboard', [
            'profile' => $profile,
            'pending' => (clone $base)->where('status', 'pending')->orderByDesc('needs_priority')->orderBy('session_date')->get(),
            'upcoming' => (clone $base)->where('status', 'confirmed')->orderBy('session_date')->orderBy('start_time')->get(),
            'history' => (clone $base)->whereIn('status', ['completed', 'rejected', 'cancelled'])->latest('session_date')->take(15)->get(),
            'avg' => round((float) $user->ratingsReceived()->avg('stars'), 1),
            'ratingCount' => $user->ratingsReceived()->count(),
        ]);
    }

    public function show(Booking $booking)
    {
        $this->own($booking);
        $booking->load(['student', 'problemType', 'rating', 'report']);

        return view('counselor.booking', [
            'booking' => $booking,
            'questions' => Question::orderBy('position')->get(),
        ]);
    }

    public function confirm(Booking $booking)
    {
        $this->own($booking);
        abort_unless($booking->status === 'pending', 422);
        $booking->update(['status' => 'confirmed']);

        return back()->with('ok', 'Sesi dikonfirmasi.');
    }

    public function reject(Request $request, Booking $booking)
    {
        $this->own($booking);
        abort_unless($booking->status === 'pending', 422);
        $data = $request->validate(['counselor_note' => ['required', 'string', 'max:600']], [
            'counselor_note.required' => 'Tulis alasan penolakan agar mahasiswa bisa memilih jadwal lain.',
        ]);
        $booking->update(['status' => 'rejected', 'counselor_note' => $data['counselor_note']]);

        return redirect()->route('counselor.dashboard')->with('ok', 'Permintaan ditolak. Slot kembali terbuka.');
    }

    public function complete(Request $request, Booking $booking)
    {
        $this->own($booking);
        abort_unless($booking->status === 'confirmed', 422);
        $data = $request->validate(['counselor_note' => ['nullable', 'string', 'max:2000']]);
        $booking->update(['status' => 'completed', 'counselor_note' => $data['counselor_note'] ?? $booking->counselor_note]);

        return redirect()->route('counselor.dashboard')->with('ok', 'Sesi ditandai selesai. Mahasiswa kini bisa memberi rating.');
    }

    /* ---------- Jadwal mingguan ---------- */

    public function availability()
    {
        return view('counselor.availability', [
            'slots' => auth()->user()->availabilities()->orderBy('day_of_week')->orderBy('start_time')->get()->groupBy('day_of_week'),
        ]);
    }

    public function addSlot(Request $request)
    {
        $data = $request->validate([
            'day_of_week' => ['required', 'integer', 'between:1,7'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
        ]);

        $overlap = auth()->user()->availabilities()
            ->where('day_of_week', $data['day_of_week'])
            ->where('start_time', '<', $data['end_time'].':00')
            ->where('end_time', '>', $data['start_time'].':00')
            ->exists();

        if ($overlap) {
            throw ValidationException::withMessages(['start_time' => 'Rentang ini bertabrakan dengan slot yang sudah ada.']);
        }

        auth()->user()->availabilities()->create($data);

        return back()->with('ok', 'Slot ditambahkan.');
    }

    public function removeSlot(Availability $slot)
    {
        abort_unless($slot->counselor_id === auth()->id(), 403);

        $hasBookings = Booking::where('counselor_id', auth()->id())
            ->whereIn('status', Booking::OCCUPYING)
            ->whereDate('session_date', '>=', today())
            ->where('start_time', $slot->start_time)
            ->whereRaw('WEEKDAY(session_date) + 1 = ?', [$slot->day_of_week])
            ->exists();

        if ($hasBookings) {
            return back()->with('err', 'Slot ini masih punya sesi yang akan datang. Selesaikan atau tolak sesinya dulu.');
        }

        $slot->delete();

        return back()->with('ok', 'Slot dihapus.');
    }

    /* ---------- Profil dan sertifikasi ---------- */

    public function profile()
    {
        $profile = auth()->user()->profile()->with('certifications')->firstOrFail();

        return view('counselor.profile', compact('profile'));
    }

    public function updateProfile(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:30'],
            'graduate_field' => ['required', 'string', 'max:150'],
            'education' => ['required', 'string', 'max:200'],
            'specializations' => ['required', 'string', 'max:500'],
            'bio' => ['nullable', 'string', 'max:1500'],
        ]);

        auth()->user()->update(['name' => $data['name'], 'phone' => $data['phone'] ?? null]);
        auth()->user()->profile->update(collect($data)->only(['graduate_field', 'education', 'specializations', 'bio'])->all());

        return back()->with('ok', 'Profil diperbarui.');
    }

    public function addCertification(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:200'],
            'issuer' => ['nullable', 'string', 'max:200'],
            'year' => ['nullable', 'integer', 'between:1980,'.now()->year],
        ]);

        auth()->user()->profile->certifications()->create($data);

        return back()->with('ok', 'Sertifikasi ditambahkan.');
    }

    public function removeCertification(int $id)
    {
        auth()->user()->profile->certifications()->whereKey($id)->delete();

        return back()->with('ok', 'Sertifikasi dihapus.');
    }

    private function own(Booking $booking): void
    {
        abort_unless($booking->counselor_id === auth()->id(), 403);
    }

       /* ---------- Chat dengan Mahasiswa ---------- */

    private function chatPayload(Message $m): array
    {
        return [
            'id' => $m->id,
            'sender_id' => $m->sender_id,
            'content' => $m->content,
            'time' => $m->created_at->format('H:i'),
            'read' => $m->read_at !== null,
        ];
    }

    /** Halaman chat untuk sesi yang sudah dikonfirmasi (juga endpoint polling via ?since=). */
    public function chatIndex(Request $request, Booking $booking)
    {
        $this->own($booking);
        abort_unless($booking->status === 'confirmed', 422, 'Chat hanya tersedia setelah sesi dikonfirmasi.');

        // Polling: kembalikan pesan baru setelah ID tertentu
        if ($request->has('since')) {
            $messages = $booking->messages()
                ->where('id', '>', (int) $request->query('since'))
                ->orderBy('id')
                ->get();

            $booking->messages()->unreadFor(auth()->user())->update(['read_at' => now()]);

            return response()->json([
                'messages' => $messages->map(fn (Message $m) => $this->chatPayload($m))->values(),
            ]);
        }

        $booking->load(['student', 'messages.sender']);

        // Tandai pesan dari mahasiswa sebagai sudah dibaca
        $booking->messages()->unreadFor(auth()->user())->update(['read_at' => now()]);

        return view('counselor.chat', compact('booking'));
    }

    /** Kirim pesan ke mahasiswa. */
    public function chatSend(Request $request, Booking $booking)
    {
        $this->own($booking);
        abort_unless($booking->status === 'confirmed', 422, 'Chat hanya tersedia setelah sesi dikonfirmasi.');

        $data = $request->validate([
            'content' => ['required', 'string', 'max:3000'],
        ]);

        $message = $booking->messages()->create([
            'sender_id' => auth()->id(),
            'content' => $data['content'],
        ]);

        return response()->json(['message' => $this->chatPayload($message)]);
    }
}