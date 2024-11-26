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
        Schema::create('treatment_documents', function (Blueprint $table) {
            $table->id(); // ID utama tabel
            $table->foreignId('patient_treatment_id') // Foreign key untuk pengobatan pasien
                  ->constrained('patient_treatments') // Mengacu pada tabel 'patient_treatments'
                  ->onDelete('cascade') // Menghapus baris terkait jika baris di tabel 'patient_treatments' dihapus
                  ->onUpdate('cascade'); // Memperbarui baris terkait jika baris di tabel 'patient_treatments' diperbarui
            $table->string('document_name'); // Nama atau deskripsi bukti pengobatan
            $table->string('document_path'); // Lokasi file atau URL bukti pengobatan
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
        Schema::dropIfExists('treatment_documents');
    }
}
