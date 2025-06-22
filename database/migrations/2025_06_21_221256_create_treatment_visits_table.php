<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTreatmentVisitsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('treatment_visits', function (Blueprint $table) {
            $table->id();

            // Relasi ke pengobatan pasien
            $table->foreignId('patient_treatment_id')
                ->constrained('patient_treatments')
                ->onDelete('cascade');

            $table->date('visit_date');                  // Tanggal kunjungan terjadwal
            $table->time('visit_time')->nullable();      // Waktu kunjungan (opsional)
            $table->enum('visit_status', ['Terjadwal', 'Hadir', 'Tidak Hadir'])->default('terjadwal'); // Status kunjungan
            $table->text('notes')->nullable();           // Catatan pemeriksaan atau evaluasi medis

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('treatment_visits');
    }
}
