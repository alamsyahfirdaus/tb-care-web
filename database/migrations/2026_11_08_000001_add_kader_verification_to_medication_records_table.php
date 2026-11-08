<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('medication_records', function (Blueprint $table) {
            if (!Schema::hasColumn('medication_records', 'status')) {
                $table->string('status', 30)->default('Diminum')->nullable()->after('is_verified');
            }
            if (!Schema::hasColumn('medication_records', 'confirmation_source')) {
                $table->string('confirmation_source', 30)->default('patient')->nullable()->after('status');
            }
            if (!Schema::hasColumn('medication_records', 'verified_by')) {
                $table->unsignedBigInteger('verified_by')->nullable()->after('confirmation_source');
                $table->foreign('verified_by')->references('id')->on('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('medication_records', 'verified_at')) {
                $table->timestamp('verified_at')->nullable()->after('verified_by');
            }
            if (!Schema::hasColumn('medication_records', 'verification_note')) {
                $table->text('verification_note')->nullable()->after('verified_at');
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
        Schema::table('medication_records', function (Blueprint $table) {
            if (Schema::hasColumn('medication_records', 'verified_by')) {
                $table->dropForeign(['verified_by']);
                $table->dropColumn('verified_by');
            }
            if (Schema::hasColumn('medication_records', 'confirmation_source')) {
                $table->dropColumn('confirmation_source');
            }
            if (Schema::hasColumn('medication_records', 'status')) {
                $table->dropColumn('status');
            }
            if (Schema::hasColumn('medication_records', 'verified_at')) {
                $table->dropColumn('verified_at');
            }
            if (Schema::hasColumn('medication_records', 'verification_note')) {
                $table->dropColumn('verification_note');
            }
        });
    }
};
