<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminRebuildDataSeeder extends Seeder
{
    public function run()
    {
        // Only run if screenings table is empty
        if (DB::table('screenings')->count() > 0) {
            echo "Screenings table already contains data, skipping seeder.\n";
            return;
        }

        echo "Seeding comprehensive TB Care data...\n";

        $patients = DB::table('patients')
            ->join('users', 'patients.user_id', '=', 'users.id')
            ->select('patients.*', 'users.name', 'users.gender as user_gender', 'users.phone as user_phone', 'users.date_of_birth')
            ->get();

        $puskesmasList = DB::table('puskesmas')->get();
        $subdistricts = DB::table('subdistricts')->get();
        $villages = DB::table('villages')->get();
        $questions = DB::table('screening_questions')->whereNotNull('question')->get();

        $fakerNames = [
            'L' => ['Ahmad Hidayat', 'Budi Santoso', 'Dedi Kurniawan', 'Eko Prasetyo', 'Fajar Ramadhan', 'Gugun Gunawan', 'Hendra Wijaya', 'Irfan Hakim', 'Joko Susilo', 'Kurnia Sandy', 'Lukman Hakim', 'Muhammad Rizki', 'Nanang Kosim', 'Oki Setiawan', 'Pangestu Adi', 'Rian Hidayat', 'Surya Pratama', 'Taufik Ismail', 'Umar Faruk', 'Wahyu Pratama'],
            'P' => ['Ani Suryani', 'Bella Safitri', 'Citra Dewi', 'Dewi Lestari', 'Endah Purwanti', 'Fitri Handayani', 'Gita Gutawa', 'Hesti Purwadinata', 'Intan Permata', 'Juwita Bahar', 'Kartika Sari', 'Lestari Indah', 'Mega Wati', 'Nurul Aini', 'Ovi Sovianti', 'Putri Ayu', 'Rina Marlina', 'Siti Rahma', 'Tuti Alawiyah', 'Umi Kulsum']
        ];

        // 1. Seed Screenings and Screening Answers
        $screeningIndex = 1;
        $totalScreenings = 85;

        for ($i = 0; $i < $totalScreenings; $i++) {
            $isLinkedToPatient = ($i < 45 && isset($patients[$i]));
            $date = Carbon::now()->subDays(rand(1, 180));

            if ($isLinkedToPatient) {
                $p = $patients[$i];
                $name = $p->name;
                $nik = $p->nik ?? ('32780' . rand(10000000000, 99999999999));
                $phone = $p->user_phone ?? ('08' . rand(111111111, 999999999));
                $gender = $p->user_gender == 'P' ? 'P' : 'L';
                $dob = $p->date_of_birth ? Carbon::parse($p->date_of_birth) : Carbon::now()->subYears(rand(18, 60));
                $age = Carbon::now()->diffInYears($dob);
                $pkmId = $p->puskesmas_id ?? ($puskesmasList->random()->id ?? 1);
                $subdistId = $p->subdistrict_id ?? ($subdistricts->random()->id ?? 1);
                $villageId = $p->village_id ?? ($villages->random()->id ?? 1);
                $address = $p->address ?? 'Tasikmalaya';
                $userId = $p->user_id;
                $patientId = $p->id;
            } else {
                $gender = (rand(0, 1) === 1) ? 'L' : 'P';
                $namePool = $fakerNames[$gender];
                $name = $namePool[array_rand($namePool)] . ' ' . rand(10, 99);
                $nik = '32780' . rand(10000000000, 99999999999);
                $phone = '08' . rand(111111111, 999999999);
                $age = rand(8, 72);
                $randomPkm = $puskesmasList->random();
                $pkmId = $randomPkm->id;
                $subdistId = $randomPkm->subdistrict_id ?? ($subdistricts->random()->id ?? 1);
                $villageId = $villages->random()->id ?? 1;
                $address = 'Jl. Raya No. ' . rand(1, 150) . ', RT 0' . rand(1, 9) . '/RW 0' . rand(1, 9);
                $userId = null;
                $patientId = null;
            }

            // Determine Risk Level: 40% Rendah, 35% Sedang, 25% Tinggi
            $randRisk = rand(1, 100);
            if ($randRisk <= 40) {
                $riskLevel = 'Risiko Rendah';
                $status = 'Selesai';
                $symptomsCount = rand(0, 1);
                $hasCritical = false;
                $score = rand(0, 2);
                $recommendation = 'Kondisi kesehatan saat ini tidak menunjukkan gejala TB yang signifikan. Jaga pola hidup bersih dan sehat (PHBS) serta gunakan masker jika batuk.';
            } elseif ($randRisk <= 75) {
                $riskLevel = 'Risiko Sedang';
                $status = (rand(0, 1) === 1) ? 'Dalam Pemantauan' : 'Perlu Tindak Lanjut';
                $symptomsCount = rand(2, 3);
                $hasCritical = (rand(1, 10) > 6);
                $score = rand(3, 5);
                $recommendation = 'Terdapat beberapa faktor risiko atau gejala ringan. Disarankan memantau perkembangan gejala selama 14 hari ke depan atau berkonsultasi ke Puskesmas terdekat.';
            } else {
                $riskLevel = 'Risiko Tinggi';
                $status = 'Perlu Tindak Lanjut';
                $symptomsCount = rand(3, 6);
                $hasCritical = true;
                $score = rand(6, 12);
                $recommendation = 'PERHATIAN: Ditemukan gejala utama TB (batuk berlanjut >= 2 minggu / batuk darah / penurunan berat badan). Segera lakukan pemeriksaan dahak TCM di Puskesmas terdekat!';
            }

            $catId = ($age >= 15) ? 1 : 2;
            $code = 'SCR-' . $date->format('Ym') . '-' . str_pad($screeningIndex, 4, '0', STR_PAD_LEFT);
            $screeningIndex++;

            $screeningId = DB::table('screenings')->insertGetId([
                'code'                  => $code,
                'user_id'               => $userId,
                'patient_id'            => $patientId,
                'person_name'           => $name,
                'nik'                   => $nik,
                'phone'                 => $phone,
                'age'                   => $age,
                'gender'                => $gender,
                'province_id'           => 1, // Jawa Barat
                'district_id'           => 1, // Tasikmalaya
                'subdistrict_id'        => $subdistId,
                'village_id'            => $villageId,
                'address'               => $address,
                'puskesmas_id'          => $pkmId,
                'screening_category_id' => $catId,
                'total_score'           => $score,
                'risk_level'            => $riskLevel,
                'status'                => $status,
                'symptoms_count'        => $symptomsCount,
                'has_critical_symptom'  => $hasCritical ? 1 : 0,
                'recommendation'        => $recommendation,
                'notes'                 => $isLinkedToPatient ? 'Skrining berkala pasien terdaftar' : 'Skrining mandiri masyarakat',
                'screened_at'           => $date,
                'created_at'            => $date,
                'updated_at'            => $date,
            ]);

            // Insert Screening Answers
            $catQuestions = $questions->filter(function($q) use ($catId) {
                return $q->screening_category_id == $catId || is_null($q->screening_category_id);
            });

            foreach ($catQuestions as $q) {
                $isCritical = (bool)$q->is_critical;
                $ansVal = 0;
                $duration = null;

                if ($riskLevel == 'Risiko Tinggi') {
                    if ($isCritical) {
                        $ansVal = (rand(1, 10) <= 8) ? 1 : 0;
                        if ($ansVal == 1 && stripos($q->question, 'Batuk') !== false) {
                            $duration = rand(14, 45);
                        }
                    } else {
                        $ansVal = (rand(1, 10) <= 5) ? 1 : 0;
                    }
                } elseif ($riskLevel == 'Risiko Sedang') {
                    if ($isCritical) {
                        $ansVal = (rand(1, 10) <= 3) ? 1 : 0;
                        if ($ansVal == 1) $duration = rand(7, 14);
                    } else {
                        $ansVal = (rand(1, 10) <= 6) ? 1 : 0;
                    }
                } else {
                    $ansVal = (rand(1, 10) <= 1) ? 1 : 0;
                }

                DB::table('screening_answers')->insert([
                    'screening_id'          => $screeningId,
                    'screening_question_id' => $q->id,
                    'question_text'         => $q->question,
                    'group_name'            => $isCritical ? 'Skrining Gejala' : 'Faktor Risiko',
                    'answer'                => (string)$ansVal,
                    'is_critical'           => $isCritical ? 1 : 0,
                    'score'                 => $ansVal == 1 ? ($isCritical ? 3 : 1) : 0,
                    'duration_days'         => $duration,
                    'notes'                 => $ansVal == 1 ? 'Menjawab Ya' : 'Menjawab Tidak',
                    'created_at'            => $date,
                    'updated_at'            => $date,
                ]);
            }
        }

        echo "Seeded {$totalScreenings} screenings with full answers.\n";

        // 2. Seed Clinical Examinations
        $examTypes = [
            'Tes Cepat Molekuler (TCM)',
            'BTA (Mikroskopis)',
            'Rontgen Dada (Thorax X-Ray)',
            'Kultur / Biakan Dahak',
            'Uji Kepekaan Obat (DST)'
        ];

        $examResults = [
            'Positif', 'Negatif', 'Sensitif Rifampisin', 'Resisten Rifampisin', 'Tersangka / Lesi Aktif', 'Normal'
        ];

        $diagnoses = [
            'TB Paru Terkonfirmasi Bakteriologis',
            'TB Paru Terdiagnosis Klinis',
            'TB Ekstra Paru',
            'Bukan TB',
            'TBC-RO (Resisten Obat)'
        ];

        $examCount = 60;
        for ($j = 0; $j < $examCount; $j++) {
            $patient = $patients[$j % count($patients)];
            $date = Carbon::now()->subDays(rand(5, 120));
            $type = $examTypes[array_rand($examTypes)];
            $res = $examResults[array_rand($examResults)];
            $diag = ($res === 'Negatif' || $res === 'Normal') ? 'Bukan TB' : $diagnoses[array_rand($diagnoses)];

            DB::table('clinical_examinations')->insert([
                'examination_code'  => 'EXAM-' . $date->format('Ym') . '-' . str_pad($j + 1, 4, '0', STR_PAD_LEFT),
                'patient_id'        => $patient->id,
                'puskesmas_id'      => $patient->puskesmas_id ?? 1,
                'officer_id'        => null,
                'examination_date'  => $date->format('Y-m-d'),
                'examination_type'  => $type,
                'result'            => $res,
                'diagnosis'         => $diag,
                'status'            => 'Selesai',
                'laboratory_notes'  => 'Hasil uji laboratorium TB Care terverifikasi.',
                'attachment'        => null,
                'created_at'        => $date,
                'updated_at'        => $date,
            ]);
        }
        echo "Seeded {$examCount} clinical examinations.\n";

        // 3. Seed Close Contacts
        $relationships = ['Keluarga Serumah', 'Teman Kerja', 'Tetangga Dekat', 'Pengasuh', 'Lainnya'];
        $screeningResults = ['Sehat / Tidak Bergejala', 'Gejala TB / Terduga', 'Dirujuk ke Puskesmas', 'Mulai TPT (Pencegahan)', 'Positif TB'];
        $tptStatuses = ['Tidak Perlu', 'Dianjurkan', 'Sedang TPT', 'Selesai TPT', 'Menolak'];

        $contactCount = 50;
        for ($k = 0; $k < $contactCount; $k++) {
            $patient = $patients[$k % count($patients)];
            $cGender = (rand(0, 1) === 1) ? 'L' : 'P';
            $cName = $fakerNames[$cGender][array_rand($fakerNames[$cGender])] . ' (Kontak ' . ($k + 1) . ')';
            $date = Carbon::now()->subDays(rand(10, 90));

            DB::table('close_contacts')->insert([
                'contact_code'     => 'KONT-' . $date->format('Ym') . '-' . str_pad($k + 1, 4, '0', STR_PAD_LEFT),
                'patient_id'       => $patient->id,
                'name'             => $cName,
                'nik'              => '32780' . rand(10000000000, 99999999999),
                'relationship'     => $relationships[array_rand($relationships)],
                'gender'           => $cGender,
                'age'              => rand(5, 65),
                'phone'            => '08' . rand(111111111, 999999999),
                'address'          => $patient->address ?? 'Tasikmalaya',
                'screening_date'   => $date->format('Y-m-d'),
                'screening_result' => $screeningResults[array_rand($screeningResults)],
                'tpt_status'       => $tptStatuses[array_rand($tptStatuses)],
                'notes'            => 'Investigasi kontak erat pasien TB.',
                'created_at'       => $date,
                'updated_at'       => $date,
            ]);
        }
        echo "Seeded {$contactCount} close contacts.\n";

        // 4. Seed System Notifications
        $notifications = [
            [
                'title'   => 'Pengingat Minum Obat Harian Pasien TB',
                'message' => 'Halo Sahabat TB Care, jangan lupa minum obat TB Anda hari ini sesuai jadwal untuk memastikan kesembuhan tuntas. Tetap semangat!',
                'type'    => 'Pengingat Minum Obat',
                'role'    => 'Pasien',
            ],
            [
                'title'   => 'Pemeriksaan Dahak Evaluasi Akhir Bulan ke-2',
                'message' => 'Diberitahukan kepada pasien TB yang telah menyelesaikan pengobatan fase intensif 2 bulan, harap melakukan pemeriksaan dahak ulang di Puskesmas.',
                'type'    => 'Jadwal Kontrol',
                'role'    => 'Pasien',
            ],
            [
                'title'   => 'Sosialisasi Pencegahan Penularan TB di Lingkungan Rumah',
                'message' => 'Buka jendela setiap pagi agar sinar matahari masuk dan sirkulasi udara baik. Kuman TB mati oleh paparan sinar ultraviolet matahari.',
                'type'    => 'Edukasi TB',
                'role'    => 'Semua',
            ],
            [
                'title'   => 'Koordinasi Kader Kesehatan & PJTB Wilayah Tasikmalaya',
                'message' => 'Pertemuan koordinasi pemantauan kepatuhan minum obat (PMO) akan diadakan pekan depan. Harap melengkapi data kunjungan rumah.',
                'type'    => 'Pengumuman Faskes',
                'role'    => 'Petugas',
            ],
            [
                'title'   => 'Peringatan Dini: Skrining Kontak Erat Balita',
                'message' => 'Pastikan seluruh anak balita yang tinggal serumah dengan pasien TB terkonfirmasi segera mendapatkan Terapi Pencegahan TB (TPT).',
                'type'    => 'Peringatan Dini',
                'role'    => 'Petugas',
            ],
        ];

        foreach ($notifications as $n) {
            DB::table('system_notifications')->insert([
                'title'               => $n['title'],
                'message'             => $n['message'],
                'type'                => $n['type'],
                'target_role'         => $n['role'],
                'target_puskesmas_id' => null,
                'sent_by'             => 1,
                'sent_count'          => rand(25, 140),
                'status'              => 'Terkirim',
                'created_at'          => Carbon::now()->subDays(rand(1, 30)),
                'updated_at'          => Carbon::now()->subDays(rand(1, 30)),
            ]);
        }
        echo "Seeded system notifications.\n";

        // 5. Seed Activity Logs
        $adminUser = DB::table('users')->where('user_type_id', 1)->first();
        $activities = [
            ['activity' => 'Login Admin Berhasil', 'module' => 'Autentikasi', 'desc' => 'Admin berhasil masuk ke portal TB Care Admin.'],
            ['activity' => 'Verifikasi Bukti Minum Obat', 'module' => 'Pengobatan', 'desc' => 'Memvalidasi kepatuhan minum obat 15 pasien TB hari ini.'],
            ['activity' => 'Sinkronisasi Data Skrining', 'module' => 'Skrining TB', 'desc' => 'Memproses hasil skrining mandiri dan memperbarui status rujukan Puskesmas.'],
            ['activity' => 'Pembaruan Jadwal Kunjungan', 'module' => 'Pemeriksaan', 'desc' => 'Menjadwalkan pemeriksaan dahak TCM ulang untuk 4 pasien.'],
            ['activity' => 'Ekspor Laporan Bulanan TB', 'module' => 'Laporan', 'desc' => 'Mengunduh laporan rekapitulasi pengobatan TB Care format Excel.'],
            ['activity' => 'Pengiriman Notifikasi Broadcast', 'module' => 'Notifikasi', 'desc' => 'Mengirimkan pesan pengingat minum obat kepada seluruh pasien aktif.'],
        ];

        foreach ($activities as $act) {
            DB::table('activity_logs')->insert([
                'user_id'     => $adminUser->id ?? 1,
                'user_name'   => $adminUser->name ?? 'Administrator TB Care',
                'activity'    => $act['activity'],
                'module'      => $act['module'],
                'description' => $act['desc'],
                'ip_address'  => '127.0.0.1',
                'user_agent'  => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0',
                'created_at'  => Carbon::now()->subHours(rand(1, 48)),
                'updated_at'  => Carbon::now()->subHours(rand(1, 48)),
            ]);
        }
        echo "Seeded activity logs.\n";
    }
}
