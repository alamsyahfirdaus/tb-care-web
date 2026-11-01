<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEducationalMediaTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('educational_materials', function (Blueprint $table) {
            $table->id();

            // Judul materi edukasi
            $table->string('title_material', 255);

            // Deskripsi materi (opsional)
            $table->text('description')->nullable();

            // Jenis materi: image atau video
            $table->enum('material_type', ['image', 'video'])->nullable();

            // Path file gambar (jika materi berupa gambar)
            $table->string('image_path')->nullable();

            // URL video (jika materi berupa video)
            $table->string('video_url')->nullable();

            // Status publikasi
            $table->boolean('is_publish')->default(true);

            // User yang membuat materi
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null')
                ->comment('user_id');

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
        Schema::dropIfExists('educational_media');
    }
}
