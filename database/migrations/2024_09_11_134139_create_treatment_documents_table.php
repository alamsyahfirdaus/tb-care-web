<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTreatmentDocumentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('medication_records', function (Blueprint $table) {
            $table->id();

            // Relasi ke pengobatan pasien
            $table->foreignId('patient_treatment_id')
                ->constrained('patient_treatments')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            // Waktu aktual minum obat (dibandingkan dengan medication_time)
            // $table->dateTime('taken_at');

            // File foto bukti minum obat (opsional)
            $table->string('photo')->nullable();

            // Status verifikasi oleh tenaga medis (true jika sudah dicek)
            $table->boolean('is_verified')->default(false);

            // Status keterlambatan (dibandingkan dengan medication_time)
            $table->boolean('late')->default(false);

            // Catatan tambahan dari petugas (misal: kabur, tidak jelas, dll.)
            $table->text('notes')->nullable();

            // Waktu dibuat dan diperbarui
            $table->timestamps();

            $table->index('is_verified');
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('treatment_documents');
    }
}
