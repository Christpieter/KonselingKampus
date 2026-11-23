<?php

namespace Database\Seeders;

use App\Models\Availability;
use App\Models\ProblemType;
use App\Models\Question;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(['email' => 'admin@konseling.test'], [
            'name' => 'Admin Konseling', 'password' => 'password', 'role' => 'admin',
        ]);

        $types = [
            'Akademik' => 'Kesulitan belajar, tugas menumpuk, skripsi, nilai.',
            'Karier' => 'Arah karier, magang, kebingungan memilih jalur setelah lulus.',
            'Relasi dan Pertemanan' => 'Konflik dengan teman, pasangan, atau teman sekamar.',
            'Keluarga' => 'Tekanan atau konflik di rumah.',
            'Stres dan Kecemasan' => 'Cemas berlebihan, sulit tidur, merasa kewalahan.',
            'Suasana Hati' => 'Sedih berkepanjangan, kehilangan semangat.',
            'Keuangan' => 'Biaya kuliah dan kebutuhan sehari-hari.',
            'Lainnya' => 'Hal lain yang belum masuk daftar.',
        ];
        foreach ($types as $name => $desc) {
            ProblemType::firstOrCreate(['name' => $name], ['description' => $desc]);
        }

        // DASS-21 Questions (21 items, 3 subscales: depression, anxiety, stress)
        // Skala jawaban: 0 tidak pernah, 1 beberapa hari, 2 lebih dari separuh hari, 3 hampir setiap hari.
        // Skor per subskala: jumlah 7 item x 2 = 0-42 (DASS-21 standar dikalikan 2)
        // Kategori: Normal (0-9 Depr, 0-7 Anx, 0-14 Stress), Mild (10-13, 8-9, 15-18), Moderate (14-20, 10-14, 19-25), Severe (21-27, 15-19, 26-33), Extremely Severe (28+, 20+, 34+)
        foreach (Question::DASS21_QUESTIONS as $q) {
            Question::updateOrCreate(['position' => $q['position']], [
                'text' => $q['text'],
                'subscale' => $q['subscale'],
            ]);
        }

        // Akun contoh untuk uji coba. Hapus sebelum dipakai sungguhan.
        if (app()->environment('local')) {
            User::updateOrCreate(['email' => 'mahasiswa@konseling.test'], [
                'name' => 'Mahasiswa Contoh', 'nim' => '2024001', 'password' => 'password', 'role' => 'mahasiswa',
            ]);

            $c = User::updateOrCreate(['email' => 'konselor@konseling.test'], [
                'name' => 'Konselor Contoh', 'password' => 'password', 'role' => 'konselor',
            ]);
            $p = $c->profile()->updateOrCreate([], [
                'graduate_field' => 'Psikologi', 'education' => 'S2 Psikologi Profesi (contoh)',
                'specializations' => 'Stres dan Kecemasan, Akademik, Karier',
                'bio' => 'Data contoh untuk uji coba aplikasi.', 'status' => 'approved',
            ]);
            if (! $p->certifications()->exists()) {
                $p->certifications()->create(['name' => 'Sertifikasi contoh', 'issuer' => 'Lembaga contoh', 'year' => 2022]);
            }
            foreach ([1, 3, 5] as $dow) {
                foreach ([['09:00', '10:00'], ['10:00', '11:00'], ['13:00', '14:00']] as [$s, $e]) {
                    Availability::firstOrCreate(
                        ['counselor_id' => $c->id, 'day_of_week' => $dow, 'start_time' => $s],
                        ['end_time' => $e]
                    );
                }
            }
        }
    }
}
