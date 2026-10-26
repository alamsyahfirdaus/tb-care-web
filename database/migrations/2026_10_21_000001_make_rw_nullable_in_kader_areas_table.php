<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class MakeRwNullableInKaderAreasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE kader_areas MODIFY rw VARCHAR(5) NULL');
        } else {
            Schema::table('kader_areas', function (Blueprint $table) {
                $table->string('rw', 5)->nullable()->change();
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
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE kader_areas MODIFY rw VARCHAR(5) NOT NULL');
        }
    }
}
