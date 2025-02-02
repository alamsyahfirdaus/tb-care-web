<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTreatmentDocumentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('medication_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_treatment_id')->constrained('patient_treatments')->onDelete('cascade')->onUpdate('cascade');
            $table->string('photo')->nullable();
            $table->datetime('taken_at');
            // $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('treatment_documents');
    }
}
