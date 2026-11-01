<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVillagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('villages')) {
            Schema::create('villages', function (Blueprint $table) {
                $table->id();
                $table->string('code', 10)->nullable();
                $table->string('name', 100);
                $table->foreignId('subdistrict_id')
                    ->nullable()
                    ->constrained('subdistricts')
                    ->onUpdate('cascade')
                    ->onDelete('cascade');
                $table->timestamps();

                $table->index('subdistrict_id');
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
        Schema::dropIfExists('villages');
    }
}
