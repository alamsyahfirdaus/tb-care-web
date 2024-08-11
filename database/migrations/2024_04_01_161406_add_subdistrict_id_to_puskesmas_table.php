<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSubdistrictIdToPuskesmasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('puskesmas', function (Blueprint $table) {
            $table->foreignId('subdistrict_id')
                    ->nullable()
                    ->constrained('subdistricts')
                    ->onUpdate('cascade')
                    ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('puskesmas', function (Blueprint $table) {
            $table->dropForeign(['subdistrict_id']);
            $table->dropColumn('subdistrict_id');
        });
    }
}
