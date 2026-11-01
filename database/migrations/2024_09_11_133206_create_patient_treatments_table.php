<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePatientTreatmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('patient_treatments', function (Blueprint $table) {
            $table->id();

            // Relasi ke pasien
            $table->foreignId('patient_id')
                ->constrained('patients')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            // Relasi ke jenis pengobatan (opsional)
            $table->foreignId('treatment_type_id')
                ->nullable()
                ->constrained('treatment_types')
                ->onDelete('set null')
                ->onUpdate('cascade');

            $table->date('diagnosis_date'); // Tanggal pasien didiagnosis TB
            $table->date('start_date');     // Tanggal mulai pengobatan TB
            $table->date('end_date')->nullable(); // Tanggal selesai pengobatan TB (jika sudah)
            $table->integer('treatment_days')->nullable(); // Jumlah hari pengobatan, hasil dari end - start

            $table->time('medication_time')->nullable(); // Waktu harian untuk pengingat minum obat

            $table->json('prescription')->nullable(); // Data resep obat dalam format JSON

            $table->enum('treatment_status', ['Berjalan', 'Selesai', 'Gagal', 'Meninggal'])->default('Berjalan');


            $table->timestamps();

            // Index untuk mempercepat query berdasarkan status pengobatan
            $table->index('treatment_status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('patient_treatments');
    }
}
