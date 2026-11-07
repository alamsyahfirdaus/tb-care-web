<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEducationalMaterialReadsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('educational_material_reads')) {
            Schema::create('educational_material_reads', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('educational_material_id')->constrained('educational_materials')->cascadeOnDelete();
                $table->timestamp('read_at')->useCurrent();
                $table->timestamps();

                // Satu pengguna hanya memiliki satu catatan pembacaan unik per materi edukasi
                $table->unique(['user_id', 'educational_material_id'], 'user_material_read_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('educational_material_reads');
    }
}
