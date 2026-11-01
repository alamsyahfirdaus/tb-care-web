<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOfficersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('officers', function (Blueprint $table) {
            $table->id();

            // Jenis petugas: 1 = Dinkes Provinsi, 2 = Dinkes Kab/Kota, 3 = PJTB Puskesmas, 4 = Kader Puskesmas
            $table->unsignedTinyInteger('officer_type_id')->comment('1 = Dinkes Provinsi, 2 = Dinkes Kab/Kota, 3 = PJTB Puskesmas, 4 = Kader Puskesmas');

            // Relasi ke tabel users
            $table->foreignId('user_id')
                ->constrained('users')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            // district_id: hanya untuk officer_type_id = 1 atau 2
            $table->foreignId('district_id')
                ->nullable()
                ->comment('officer_type_id = 1, 2')
                ->constrained('districts')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            // puskesmas_id: hanya untuk officer_type_id = 3 atau 4
            $table->foreignId('puskesmas_id')
                ->nullable()
                ->comment('officer_type_id = 3, 4')
                ->constrained('puskesmas')
                ->onUpdate('cascade')
                ->onDelete('cascade');

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
        Schema::dropIfExists('officers');
    }
}
