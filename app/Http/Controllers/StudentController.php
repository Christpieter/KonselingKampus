<?php

namespace App\Http\Controllers;

use App\Models\Availability;
use App\Models\Booking;
use App\Models\CounselorProfile;
use App\Models\Message;
use App\Models\ProblemType;
use App\Models\Rating;
use App\Models\TriageSession;
use App\Services\ScheduleService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StudentController extends Controller
{
    public function __construct(private ScheduleService $schedule) {}

    /* ---------- Dashboard ---------- */

    public function dashboard()
    {
        $bookings = auth()->user()->studentBookings()
            ->with(['counselor.profile', 'problemType', 'rating'])
            ->orderByDesc('session_date')->orderByDesc('start_time')->get();

        return view('student.dashboard', compact('bookings'));
    }

    public function cancel(Booking $booking)
    {
        abort_unless($booking->student_id === auth()->id(), 403);
        abort_unless(in_array($booking->status, Booking::OCCUPYING, true), 422, 'Sesi ini tidak bisa dibatalkan.');

        $booking->update(['status' => 'cancelled']);

        return back()->with('ok', 'Sesi dibatalkan. Slot kembali tersedia untuk mahasiswa lain.');
    }

    public function rate(Request $request, Booking $booking)
    {
        abort_unless($booking->student_id === auth()->id(), 403);
        abort_unless($booking->status === 'completed', 422, 'Rating hanya untuk sesi yang sudah selesai.');
        abort_if($booking->rating()->exists(), 422, 'Sesi ini sudah dinilai.');

        $data = $request->validate([
            'stars' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:600'],
        ]);

        Rating::create($data + [
            'booking_id' => $booking->id,
            'student_id' => auth()->id(),
            'counselor_id' => $booking->counselor_id,
        ]);

        return back()->with('ok', 'Terima kasih, rating tersimpan.');
    }

    /* ---------- Alur booking ---------- */

    /** Langkah 0: mulai, boleh membawa konselor pilihan dari halaman profil. */
    public function start(Request $request)
    {
        $student = auth()->user();

        // Check for completed triage (required before booking)
        $triage = TriageSession::where('student_id', $student->id)
            ->where('status', 'completed')
            ->latest()
            ->first();

        if (! $triage) {
            return redirect()->route('student.triage.index')
                ->with('err', 'Silakan selesaikan triage kesehatan mental (DASS-21) terlebih dahulu sebelum membuat janji konseling.');
        }

        $draft = [];

        if ($id = $request->query('konselor')) {
            $profile = CounselorProfile::approved()->where('user_id', $id)->first();
            abort_unless($profile, 404);
            $draft['counselor_id'] = $profile->user_id;
        }

        // Store triage reference in draft for later use
        $draft['triage_session_id'] = $triage->id;

        session(['booking_draft' => $draft]);

        return redirect()->route('student.booking.intake');
    }

    /** Langkah 1: tipe masalah, cerita singkat, 10 kuesioner. */
    public function intake()
    {
        return view('student.intake', [
            'types' => ProblemType::orderBy('name')->get(),
            'draft' => session('booking_draft', []),
        ]);
    }

    public function saveIntake(Request $request)
    {
        $data = $request->validate([
            'problem_type_id' => ['required', 'exists:problem_types,id'],
            'story' => ['required', 'string', 'min:30', 'max:1500'],
        ], [
            'story.min' => 'Ceritakan sedikit lebih panjang, minimal 30 karakter.',
        ]);

        $draft = session('booking_draft', []);
        $draft['problem_type_id'] = (int) $data['problem_type_id'];
        $draft['story'] = $data['story'];
        unset($draft['answers']);
        session(['booking_draft' => $draft]);

        return isset($draft['counselor_id'])
            ? redirect()->route('student.booking.schedule')
            : redirect()->route('student.booking.counselors');
    }

    /** Langkah 2: pilih konselor (dilewati bila sudah dipilih dari profil). */
    public function counselors()
    {
        $draft = $this->requireIntake();
        $type = ProblemType::find($draft['problem_type_id']);

        $profiles = CounselorProfile::approved()->with(['user', 'certifications'])->get()->each(function ($p) use ($type) {
            $p->nextOpen = $this->schedule->nextOpenDate($p->user_id);
            $p->avg = round((float) $p->user->ratingsReceived()->avg('stars'), 1);
            $p->count = $p->user->ratingsReceived()->count();
            $p->match = $type && stripos($p->specializations.' '.$p->graduate_field, $type->name) !== false;
        })->sortBy([
            fn ($a, $b) => ($a->nextOpen === null) <=> ($b->nextOpen === null),
            fn ($a, $b) => $b->match <=> $a->match,
            fn ($a, $b) => $b->avg <=> $a->avg,
        ])->values();

        return view('student.counselors', compact('profiles', 'type', 'draft'));
    }

    public function pickCounselor(Request $request)
    {
        $this->requireIntake();

        $id = $request->validate(['counselor_id' => ['required', 'integer']])['counselor_id'];
        abort_unless(CounselorProfile::approved()->where('user_id', $id)->exists(), 404);

        $draft = session('booking_draft');
        $draft['counselor_id'] = (int) $id;
        session(['booking_draft' => $draft]);

        return redirect()->route('student.booking.schedule');
    }

    /** Langkah 3: kalender dan slot. */
    public function schedulePage(Request $request)
    {
        $draft = $this->requireIntake();
        if (empty($draft['counselor_id'])) {
            return redirect()->route('student.booking.counselors');
        }

        $profile = CounselorProfile::approved()->with('user')->where('user_id', $draft['counselor_id'])->first();
        if (! $profile) {
            return redirect()->route('student.booking.counselors')->with('err', 'Konselor tidak tersedia lagi, pilih yang lain.');
        }

        $min = $this->schedule->earliestDate()->startOfMonth();
        $max = Carbon::today()->addMonths(3)->startOfMonth();

        try {
            $month = Carbon::createFromFormat('Y-m-d', ($request->query('bulan') ?: $min->format('Y-m')).'-01')->startOfDay();
        } catch (\Throwable) {
            $month = $min->copy();
        }
        $month = $month->lt($min) ? $min->copy() : ($month->gt($max) ? $max->copy() : $month);

        $statuses = $this->schedule->monthStatuses($profile->user_id, $month);

        $selected = null;
        $slots = collect();
        if (($d = $request->query('tanggal')) && isset($statuses[$d]) && in_array($statuses[$d], ['available', 'partial', 'full'], true)) {
            $selected = Carbon::parse($d);
            $slots = $this->schedule->slotsFor($profile->user_id, $selected);
        }

        return view('student.schedule', [
            'profile' => $profile,
            'month' => $month,
            'statuses' => $statuses,
            'selected' => $selected,
            'slots' => $slots,
            'prevMonth' => $month->gt($min) ? $month->copy()->subMonth() : null,
            'nextMonth' => $month->lt($max) ? $month->copy()->addMonth() : null,
            'type' => ProblemType::find($draft['problem_type_id']),
        ]);
    }

    /** Langkah 4: simpan booking. */
    public function store(Request $request)
    {
        $draft = $this->requireIntake();
        abort_unless(! empty($draft['counselor_id']), 422);

        $data = $request->validate([
            'tanggal' => ['required', 'date_format:Y-m-d'],
            'slot_id' => ['required', 'integer'],
        ]);

        $date = Carbon::parse($data['tanggal']);
        if ($date->lt($this->schedule->earliestDate())) {
            throw ValidationException::withMessages(['tanggal' => 'Sesi paling awal bisa dipesan untuk besok.']);
        }

        $triage = TriageSession::where('id', $draft['triage_session_id'] ?? null)
            ->where('student_id', auth()->id())
            ->where('status', 'completed')
            ->first();

        if (! $triage) {
            return redirect()->route('student.triage.index')
                ->with('err', 'Silakan selesaikan triage kembali sebelum membuat janji konseling.');
        }

        $booking = DB::transaction(function () use ($draft, $data, $date, $triage) {
            $slot = Availability::where('id', $data['slot_id'])
                ->where('counselor_id', $draft['counselor_id'])
                ->where('day_of_week', $date->dayOfWeekIso)
                ->first();

            if (! $slot) {
                throw ValidationException::withMessages(['slot_id' => 'Slot tidak valid untuk tanggal ini.']);
            }

            $taken = Booking::where('counselor_id', $draft['counselor_id'])
                ->whereDate('session_date', $date->toDateString())
                ->where('start_time', $slot->start_time)
                ->whereIn('status', Booking::OCCUPYING)
                ->lockForUpdate()->exists();

            if ($taken) {
                throw ValidationException::withMessages(['slot_id' => 'Slot ini baru saja diambil orang lain. Pilih jam lain.']);
            }

            $clash = Booking::where('student_id', auth()->id())
                ->whereDate('session_date', $date->toDateString())
                ->where('start_time', $slot->start_time)
                ->whereIn('status', Booking::OCCUPYING)->exists();

            if ($clash) {
                throw ValidationException::withMessages(['slot_id' => 'Anda sudah punya sesi pada jam yang sama.']);
            }

            return Booking::create([
                'student_id' => auth()->id(),
                'triage_session_id' => $triage->id,
                'counselor_id' => $draft['counselor_id'],
                'problem_type_id' => $draft['problem_type_id'],
                'story' => $draft['story'],
                'answers' => $triage->answers ?? [],
                'score' => $triage->total_score,
                'needs_priority' => $triage->needs_priority,
                'session_date' => $date->toDateString(),
                'start_time' => $slot->start_time,
                'end_time' => $slot->end_time,
                'status' => 'pending',
            ]);
        });

        session()->forget('booking_draft');

        return redirect()->route('student.dashboard')
            ->with('ok', 'Permintaan sesi terkirim. Konselor akan mengonfirmasi atau menolaknya; statusnya bisa Anda pantau di halaman ini.');
    }

    private function requireIntake(): array
    {
        $draft = session('booking_draft');

        if (empty($draft['problem_type_id']) || empty($draft['story'])) {
            abort(redirect()->route('student.booking.intake'));
        }

        return $draft;
    }

    /* ---------- Chat dengan Konselor ---------- */

    /** Halaman chat untuk sesi yang sudah dikonfirmasi. */
    public function chatIndex(Request $request, Booking $booking)
    {
        abort_unless($booking->student_id === auth()->id(), 403);
        abort_unless($booking->status === 'confirmed', 422, 'Chat hanya tersedia setelah sesi dikonfirmasi oleh konselor.');

        // Polling endpoint: return new messages since given ID
        if ($request->has('since')) {
            $since = (int) $request->query('since');
            $messages = $booking->messages()
                ->where('id', '>', $since)
                ->with('sender')
                ->orderBy('id')
                ->get();
            return response()->json(['messages' => $messages]);
        }

        $booking->load(['counselor.profile', 'messages.sender']);

        // Mark messages from counselor as read
        $booking->messages()
            ->where('sender_id', '!=', auth()->id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return view('student.chat', compact('booking'));
    }

    /** Kirim pesan ke konselor. */
    public function chatSend(Request $request, Booking $booking)
    {
        abort_unless($booking->student_id === auth()->id(), 403);
        abort_unless($booking->status === 'confirmed', 422);

        $data = $request->validate([
            'content' => ['required', 'string', 'max:3000'],
        ]);

        $message = $booking->messages()->create([
            'sender_id' => auth()->id(),
            'content' => $data['content'],
        ]);

        return response()->json([
            'message' => $message->load('sender'),
        ]);
    }
}
