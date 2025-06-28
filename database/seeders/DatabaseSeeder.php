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
        $this->call(EducationalMaterialSeeder::class);
        $this->call(PatientsTableSeeder::class);
        $this->call(TreatmentTypesSeeder::class);
        $this->call(PatientTreatmentsSeeder::class);
        $this->call(MedicationRecordsSeeder::class);
        $this->call(ConsultationSeeder::class);
        $this->call(ConsultationReplySeeder::class);
        $this->call(OfficerSeeder::class);
        $this->call(TreatmentVisitSeeder::class);
        $this->call(NutritionRulesSeeder::class);
    }
}
