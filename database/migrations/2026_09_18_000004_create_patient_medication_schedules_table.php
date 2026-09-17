<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePatientMedicationSchedulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('patient_medication_schedules')) {
            Schema::create('patient_medication_schedules', function (Blueprint $table) {
                $table->id();
                $table->foreignId('patient_id')
                    ->constrained('patients')
                    ->onUpdate('cascade')
                    ->onDelete('cascade');
                $table->time('reminder_time');
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['patient_id', 'is_active']);
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
        Schema::dropIfExists('patient_medication_schedules');
    }
}
