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
            $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade')->onUpdate('cascade'); 
            $table->foreignId('treatment_type_id')->nullable()->constrained('treatment_types')->onDelete('set null')->onUpdate('cascade');
            // $table->foreignId('puskesmas_id')->nullable()->constrained('puskesmas')->onDelete('set null')->onUpdate('cascade');
            $table->date('diagnosis_date'); // Tanggal diagnosis pasien
            $table->date('start_date'); // Tanggal mulai pengobatan
            $table->date('end_date')->nullable(); // Tanggal selesai pengobatan
            $table->time('medication_time')->nullable(); // Waktu pengingat untuk minum obat
            $table->json('prescription')->nullable(); // Resep obat
            $table->integer('treatment_status')->nullable(); // Status pengobatan (berjalan, selesai, gagal, dll.)
            // $table->timestamps();
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
