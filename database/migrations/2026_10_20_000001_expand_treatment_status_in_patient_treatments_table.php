<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class ExpandTreatmentStatusInPatientTreatmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE patient_treatments MODIFY treatment_status VARCHAR(50) NOT NULL DEFAULT 'Berjalan'");
        } else {
            Schema::table('patient_treatments', function (Blueprint $table) {
                $table->string('treatment_status', 50)->default('Berjalan')->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE patient_treatments MODIFY treatment_status ENUM('Berjalan', 'Selesai', 'Gagal', 'Meninggal') NOT NULL DEFAULT 'Berjalan'");
        }
    }
}
