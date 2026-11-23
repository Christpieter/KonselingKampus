<?php

namespace App\Services;

use App\Models\Availability;
use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Menghitung status tanggal dan slot untuk satu konselor.
 * Status tanggal: none (tidak ada jadwal), past, available, partial, full.
 */
class ScheduleService
{
    /** Sesi paling awal bisa dipesan besok. */
    public function earliestDate(): Carbon
    {
        return Carbon::tomorrow();
    }

    /** @return array<string,string> peta 'Y-m-d' => status untuk satu bulan */
    public function monthStatuses(int $counselorId, Carbon $month): array
    {
        $start = $month->copy()->startOfMonth();
        $end = $month->copy()->endOfMonth();

        $slotsByDow = Availability::where('counselor_id', $counselorId)->get()->groupBy('day_of_week');

        $booked = Booking::where('counselor_id', $counselorId)
            ->whereBetween('session_date', [$start->toDateString(), $end->toDateString()])
            ->whereIn('status', Booking::OCCUPYING)
            ->get()
            ->groupBy(fn ($b) => $b->session_date->toDateString())
            ->map(fn ($g) => $g->pluck('start_time')->map(fn ($t) => substr($t, 0, 5))->unique()->count());

        $result = [];
        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            $key = $d->toDateString();
            $total = $slotsByDow->get($d->dayOfWeekIso, collect())->count();

            if ($d->lt($this->earliestDate())) {
                $result[$key] = 'past';
            } elseif ($total === 0) {
                $result[$key] = 'none';
            } else {
                $taken = (int) $booked->get($key, 0);
                $result[$key] = $taken === 0 ? 'available' : ($taken >= $total ? 'full' : 'partial');
            }
        }

        return $result;
    }

    /** Slot pada satu tanggal, masing-masing ditandai sudah terisi atau belum. */
    public function slotsFor(int $counselorId, Carbon $date): Collection
    {
        $taken = Booking::where('counselor_id', $counselorId)
            ->whereDate('session_date', $date->toDateString())
            ->whereIn('status', Booking::OCCUPYING)
            ->pluck('start_time')
            ->map(fn ($t) => substr($t, 0, 5))
            ->all();

        return Availability::where('counselor_id', $counselorId)
            ->where('day_of_week', $date->dayOfWeekIso)
            ->orderBy('start_time')
            ->get()
            ->map(fn ($a) => (object) [
                'id' => $a->id,
                'start' => substr($a->start_time, 0, 5),
                'end' => substr($a->end_time, 0, 5),
                'booked' => in_array(substr($a->start_time, 0, 5), $taken, true),
            ]);
    }

    /** Tanggal terdekat yang masih punya slot kosong (cek 3 bulan kalender), atau null. */
    public function nextOpenDate(int $counselorId): ?Carbon
    {
        $first = $this->earliestDate()->startOfMonth();

        foreach ([0, 1, 2] as $offset) {
            $statuses = $this->monthStatuses($counselorId, $first->copy()->addMonths($offset));
            foreach ($statuses as $date => $status) {
                if (in_array($status, ['available', 'partial'], true)) {
                    return Carbon::parse($date);
                }
            }
        }

        return null;
    }
}
