<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class RegisterPerluTindakLanjutTreatmentStatus extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Memastikan kolom treatment_status pada tabel patient_treatments berkapasitas VARCHAR(50)
        // dan dapat menyimpan status baru 'perlu_tindak_lanjut' secara aman tanpa menghapus data yang ada.
        if (Schema::hasTable('patient_treatments') && Schema::hasColumn('patient_treatments', 'treatment_status')) {
            if (DB::getDriverName() !== 'sqlite') {
                DB::statement("ALTER TABLE patient_treatments MODIFY treatment_status VARCHAR(50) NOT NULL DEFAULT 'Berjalan'");
            } else {
                Schema::table('patient_treatments', function (Blueprint $table) {
                    $table->string('treatment_status', 50)->default('Berjalan')->change();
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Tetap mempertahankan VARCHAR(50) agar tidak merusak data jika di-rollback
    }
}
