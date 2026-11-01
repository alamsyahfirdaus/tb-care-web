<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddVillageAndRtRwToPatientsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('patients', function (Blueprint $table) {
            if (!Schema::hasColumn('patients', 'village_id')) {
                $table->foreignId('village_id')
                    ->nullable()
                    ->after('subdistrict_id')
                    ->constrained('villages')
                    ->onUpdate('cascade')
                    ->onDelete('set null');
            }

            if (!Schema::hasColumn('patients', 'rw')) {
                $table->string('rw', 5)->nullable()->after('village_id');
            }

            if (!Schema::hasColumn('patients', 'rt')) {
                $table->string('rt', 5)->nullable()->after('rw');
            }
        });

        // Add index only if it does not already exist
        try {
            $indexes = collect(\Illuminate\Support\Facades\DB::select("SHOW INDEX FROM patients"))->pluck('Key_name')->all();
            if (!in_array('patients_village_id_rw_rt_index', $indexes) && !in_array('patients_territory_idx', $indexes)) {
                Schema::table('patients', function (Blueprint $table) {
                    $table->index(['village_id', 'rw', 'rt'], 'patients_village_id_rw_rt_index');
                });
            }
        } catch (\Exception $e) {
            // Silently continue if index inspect fails
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropIndex(['village_id', 'rw', 'rt']);
            if (Schema::hasColumn('patients', 'rt')) {
                $table->dropColumn('rt');
            }
            if (Schema::hasColumn('patients', 'rw')) {
                $table->dropColumn('rw');
            }
            if (Schema::hasColumn('patients', 'village_id')) {
                $table->dropForeign(['village_id']);
                $table->dropColumn('village_id');
            }
        });
    }
}
