<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDateOfBirthToCloseContactsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('close_contacts', function (Blueprint $table) {
            if (!Schema::hasColumn('close_contacts', 'date_of_birth')) {
                $table->date('date_of_birth')->nullable()->after('age');
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
        Schema::table('close_contacts', function (Blueprint $table) {
            if (Schema::hasColumn('close_contacts', 'date_of_birth')) {
                $table->dropColumn('date_of_birth');
            }
        });
    }
}
