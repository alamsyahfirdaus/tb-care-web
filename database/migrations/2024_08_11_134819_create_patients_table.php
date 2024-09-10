<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePatientsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->string('nik')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade')->onUpdate('cascade');
            $table->text('address')->nullable();
            $table->foreignId('subdistrict_id')->nullable()->constrained('subdistricts')->onDelete('set null')->onUpdate('cascade');
            $table->string('occupation')->nullable();
            $table->integer('height')->nullable();
            $table->integer('weight')->nullable();
            $table->string('blood_type', 3)->nullable();
            $table->date('diagnosis_date')->nullable();
            $table->foreignId('puskesmas_id')->nullable()->constrained('puskesmas')->onDelete('set null')->onUpdate('cascade'); 
            
            // $table->string('tb_type')->nullable(); // Jenis TB
            // $table->string('treatment_regimen')->nullable(); // Regimen pengobatan
            // $table->date('treatment_start_date')->nullable(); // Tanggal mulai pengobatan
            // $table->date('treatment_end_date')->nullable(); // Tanggal akhir pengobatan
            // $table->date('last_visit_date')->nullable(); // Tanggal kunjungan terakhir
            // $table->text('contact_history')->nullable(); // Riwayat kontak dengan pasien TB lain
            // $table->date('tb_test_date')->nullable(); // Tanggal tes TB dilakukan
            // $table->string('tb_test_result')->nullable(); // Hasil tes TB (Positif/Negatif)
            // $table->string('treatment_type')->nullable(); // Jenis pengobatan
            // $table->integer('treatment_duration')->nullable(); // Durasi pengobatan (dalam hari)
            // $table->date('follow_up_date')->nullable(); // Tanggal kunjungan tindak lanjut
            // $table->text('notes')->nullable(); // Catatan tambahan
            // $table->date('admission_date')->nullable(); // Tanggal masuk pasien ke sistem/klinik
            // $table->string('patient_status')->nullable(); // Status pasien (aktif, sembuh, dalam perawatan)
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('patients'); // Drops the patients table if it exists
    }
}
