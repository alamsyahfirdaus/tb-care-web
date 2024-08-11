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
            $table->string('nik')->nullable()->unique(); 
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null')->onUpdate('cascade'); 
            $table->foreignId('subdistrict_id')->nullable()->constrained('subdistricts')->onDelete('set null')->onUpdate('cascade'); 
            $table->date('diagnosis_date')->nullable(); 
            $table->foreignId('treatment_status_id')->nullable()->constrained('treatment_status')->onDelete('set null')->onUpdate('cascade'); 
            $table->date('treatment_start_date')->nullable(); 
            $table->date('treatment_end_date')->nullable(); 
            $table->foreignId('puskesmas_id')->nullable()->constrained('puskesmas')->onDelete('set null')->onUpdate('cascade'); 
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
        Schema::dropIfExists('patients'); // Drops the patients table if it exists
    }
}
