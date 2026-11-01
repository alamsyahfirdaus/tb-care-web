<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateConsultationRepliesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('consultation_replies', function (Blueprint $table) {
            $table->id();

            // Relasi ke konsultasi utama
            $table->foreignId('consultation_id')
                ->constrained('consultations')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            // Pengirim balasan (user)
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            // Isi pesan balasan
            $table->text('message');

            // Lampiran opsional (gambar/dokumen)
            $table->string('attachment')->nullable();

            // Status apakah sudah dibaca
            $table->boolean('is_read')->default(false); // false = belum dibaca, true = sudah dibaca

            // Waktu pembuatan dan pembaruan
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
        Schema::dropIfExists('consultation_replies');
    }
}
