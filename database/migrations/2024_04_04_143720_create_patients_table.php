<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePatientsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->string('nik')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('subdistrict_id')->nullable()->constrained('subdistricts')->onUpdate('cascade')->onDelete('cascade');
            $table->date('tanggal_diagnosis')->nullable();
            $table->foreignId('status_pengobatan_id')->nullable()->constrained('status_pengobatan')->onDelete('cascade')->onUpdate('cascade');
            $table->date('tanggal_mulai_pengobatan')->nullable();
            $table->date('tanggal_selesai_pengobatan')->nullable();
            $table->foreignId('puskesmas_id')->nullable()->constrained('puskesmas')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('patients');
    }
}
