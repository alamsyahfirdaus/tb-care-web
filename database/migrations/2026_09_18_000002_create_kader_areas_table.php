<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateKaderAreasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('kader_areas')) {
            Schema::create('kader_areas', function (Blueprint $table) {
                $table->id();
                $table->foreignId('officer_id')
                    ->constrained('officers')
                    ->onUpdate('cascade')
                    ->onDelete('cascade');
                $table->foreignId('subdistrict_id')
                    ->constrained('subdistricts')
                    ->onUpdate('cascade')
                    ->onDelete('cascade');
                $table->foreignId('village_id')
                    ->constrained('villages')
                    ->onUpdate('cascade')
                    ->onDelete('cascade');
                $table->string('rw', 5);
                $table->string('rt', 5)->nullable()->comment('NULL means all RTs in this RW');
                $table->timestamps();

                $table->index(['officer_id', 'village_id', 'rw', 'rt']);
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
        Schema::dropIfExists('kader_areas');
    }
}
