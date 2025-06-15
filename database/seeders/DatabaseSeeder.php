<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // \App\Models\User::factory(10)->create();
        $this->call(UserTypeSeeder::class);
        $this->call(UserSeeder::class);
        $this->call(PuskesmasSeeder::class);
        $this->call(ProvinceSeeder::class);
        $this->call(DistrictSeeder::class);
        $this->call(SubdistrictSeeder::class);
        $this->call(ScreeningSeeder::class);
        $this->call(CategorySeeder::class);
        $this->call(EducationalMaterialSeeder::class);
        $this->call(CoordinatorSeeder::class);
        $this->call(PatientsTableSeeder::class);
        $this->call(HealthOfficesSeeder::class);
        $this->call(TreatmentTypesSeeder::class);
        $this->call(PatientTreatmentsSeeder::class);
        $this->call(MedicationRecordsSeeder::class);
        $this->call(ConsultationSeeder::class);
    }
}
