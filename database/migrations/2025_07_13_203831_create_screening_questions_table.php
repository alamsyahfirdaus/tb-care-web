<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateScreeningQuestionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('screening_questions', function (Blueprint $table) {
            $table->id();
            // Kategori skrining (misal: <15, ≥15 tahun)
            $table->foreignId('screening_category_id')->nullable()->constrained('screening_categories')->onDelete('cascade');

            // Kelompok pertanyaan seperti: 'Gejala', 'Faktor Risiko', 'Riwayat Kontak', dst.
            $table->string('group')->nullable();

            // Self join: digunakan jika ingin membuat struktur grup-subgrup pertanyaan
            $table->foreignId('group_id')
                ->nullable()
                ->constrained('screening_questions')
                ->onDelete('cascade');

            // Isi teks pertanyaan
            $table->text('question')->nullable();

            // Menentukan apakah pertanyaan termasuk gejala kritis (penentu terduga TBC)
            $table->boolean('is_critical')->default(false);

            // Urutan tampilan
            $table->integer('ordering')->nullable()->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('screening_questions');
    }
}
