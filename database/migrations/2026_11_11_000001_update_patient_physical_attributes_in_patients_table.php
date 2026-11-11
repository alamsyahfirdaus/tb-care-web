<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdatePatientPhysicalAttributesInPatientsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->decimal('height', 5, 2)->nullable()->change();
            $table->decimal('weight', 5, 2)->nullable()->change();
            $table->string('blood_type', 5)->nullable()->change();
            $table->string('occupation', 150)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->integer('height')->nullable()->change();
            $table->integer('weight')->nullable()->change();
            $table->string('blood_type', 3)->nullable()->change();
            $table->string('occupation', 255)->nullable()->change();
        });
    }
}
