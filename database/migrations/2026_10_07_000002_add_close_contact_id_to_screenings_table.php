<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCloseContactIdToScreeningsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('screenings', function (Blueprint $table) {
            if (!Schema::hasColumn('screenings', 'close_contact_id')) {
                $table->foreignId('close_contact_id')
                    ->nullable()
                    ->after('patient_id')
                    ->constrained('close_contacts')
                    ->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('screenings', function (Blueprint $table) {
            if (Schema::hasColumn('screenings', 'close_contact_id')) {
                $table->dropForeign(['close_contact_id']);
                $table->dropColumn('close_contact_id');
            }
        });
    }
}
