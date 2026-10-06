<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNotificationSentToEducationalMaterialsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('educational_materials', function (Blueprint $table) {
            if (!Schema::hasColumn('educational_materials', 'notification_sent')) {
                $table->boolean('notification_sent')->default(false)->after('is_publish');
            }
        });

        // Tandai materi yang sudah pernah dipublikasi sebelumnya agar tidak memicu notifikasi ulang
        \Illuminate\Support\Facades\DB::table('educational_materials')
            ->where('is_publish', 1)
            ->update(['notification_sent' => 1]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('educational_materials', function (Blueprint $table) {
            if (Schema::hasColumn('educational_materials', 'notification_sent')) {
                $table->dropColumn('notification_sent');
            }
        });
    }
}
