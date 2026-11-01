<?php

namespace Database\Seeders;

use App\Models\PatientTreatment;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TreatmentVisitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Ambil semua pengobatan pasien
        $treatments = PatientTreatment::all();

        foreach ($treatments as $treatment) {
            // Tambahkan 3 jadwal kunjungan ke depan (mingguan)
            for ($i = 1; $i <= 3; $i++) {
                DB::table('treatment_visits')->insert([
                    'patient_treatment_id' => $treatment->id,
                    'visit_date'           => Carbon::parse($treatment->start_date)->addWeeks($i),
                    'visit_time'           => '08:00:00',
                    'visit_status'         => 'Terjadwal',
                    'notes'                => null,
                    'created_at'           => Carbon::now(),
                    'updated_at'           => Carbon::now(),
                ]);
            }
        }
    }
}
