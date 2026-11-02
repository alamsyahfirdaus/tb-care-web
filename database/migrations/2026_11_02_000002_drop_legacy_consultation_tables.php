<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropLegacyConsultationTables extends Migration
{
    /**
     * Run the migrations.
     *
     * Drops the legacy consultations and consultation_replies tables.
     * Historical data was migrated to conversations and messages via
     * 2026_11_02_000001_create_conversations_and_messages_tables.
     *
     * @return void
     */
    public function up()
    {
        // 1. Drop consultation_replies first (child table with foreign key to consultations)
        if (Schema::hasTable('consultation_replies')) {
            Schema::dropIfExists('consultation_replies');
        }

        // 2. Drop consultations table
        if (Schema::hasTable('consultations')) {
            Schema::dropIfExists('consultations');
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (!Schema::hasTable('consultations')) {
            Schema::create('consultations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete()->cascadeOnUpdate();
                $table->foreignId('recipient_id')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
                $table->string('title');
                $table->text('message');
                $table->string('attachment')->nullable();
                $table->boolean('is_answered')->default(false);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('consultation_replies')) {
            Schema::create('consultation_replies', function (Blueprint $table) {
                $table->id();
                $table->foreignId('consultation_id')->constrained('consultations')->cascadeOnDelete()->cascadeOnUpdate();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete()->cascadeOnUpdate();
                $table->text('message');
                $table->string('attachment')->nullable();
                $table->boolean('is_read')->default(false);
                $table->timestamps();
            });
        }
    }
}
