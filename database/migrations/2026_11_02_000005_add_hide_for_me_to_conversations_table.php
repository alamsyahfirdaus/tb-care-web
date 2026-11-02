<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddHideForMeToConversationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('conversations', function (Blueprint $table) {
            if (!Schema::hasColumn('conversations', 'deleted_for_participant_one_at')) {
                $table->timestamp('deleted_for_participant_one_at')->nullable()->after('participant_two_id');
            }
            if (!Schema::hasColumn('conversations', 'deleted_for_participant_two_at')) {
                $table->timestamp('deleted_for_participant_two_at')->nullable()->after('deleted_for_participant_one_at');
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
        Schema::table('conversations', function (Blueprint $table) {
            if (Schema::hasColumn('conversations', 'deleted_for_participant_two_at')) {
                $table->dropColumn('deleted_for_participant_two_at');
            }
            if (Schema::hasColumn('conversations', 'deleted_for_participant_one_at')) {
                $table->dropColumn('deleted_for_participant_one_at');
            }
        });
    }
}
