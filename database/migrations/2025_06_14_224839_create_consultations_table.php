<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateConsultationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('consultations', function (Blueprint $table) {
            $table->id(); // Primary key

            // ID pengguna yang mengirim konsultasi (pengirim)
            $table->foreignId('user_id')
                ->constrained('users')        // Relasi ke tabel users
                ->onDelete('cascade')         // Jika user dihapus, konsultasi juga ikut terhapus
                ->onUpdate('cascade');        // Jika user.id berubah, update di sini juga

            // ID penerima konsultasi (jika privat), null jika publik
            $table->foreignId('recipient_id')
                ->nullable()                  // Boleh null jika konsultasi bersifat publik
                ->comment('private = user_id, null = public')
                ->constrained('users')        // Relasi ke tabel users
                ->onDelete('set null')        // Jika penerima dihapus, set null
                ->onUpdate('cascade');        // Jika user.id berubah, update di sini juga

            // Judul topik konsultasi
            $table->string('title');

            // Isi pesan konsultasi
            $table->text('message');

            // Lampiran gambar/file (jika ada)
            $table->string('attachment')->nullable();

            // Status apakah sudah dijawab atau belum
            $table->boolean('is_answered')->default(false); // false = belum dijawab, true = sudah dijawab

            // Timestamps untuk created_at dan updated_at
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
        Schema::dropIfExists('consultations');
    }
}
