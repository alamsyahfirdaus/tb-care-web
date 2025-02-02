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
            $table->string('title_material');
            $table->text('description')->nullable();
            $table->enum('material_type', ['file', 'url'])->nullable();
            $table->string('thumbnail')->nullable();
            $table->string('material_file')->nullable();
            $table->string('material_url')->nullable();
            $table->boolean('is_publish')->default(true);
            $table->foreignId('user_id')->nullable()->comment('created_by')->constrained('users')->onDelete('set null');
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
