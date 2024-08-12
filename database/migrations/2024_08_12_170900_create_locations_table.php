<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLocationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->id(); // Kolom ID
            $table->string('name'); // Kolom Nama
            $table->foreignId('district_id')->nullable()
                  ->constrained('locations', 'id') // Self join ke tabel locations untuk district
                  ->onUpdate('cascade')->onDelete('set null'); // Jika district dihapus, set null
            $table->foreignId('province_id')->nullable()
                  ->constrained('locations', 'id') // Self join ke tabel locations untuk province
                  ->onUpdate('cascade')->onDelete('set null'); // Jika province dihapus, set null
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('locations');
    }
}
