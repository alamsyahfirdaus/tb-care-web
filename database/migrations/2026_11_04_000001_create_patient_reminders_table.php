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
        Schema::create('patient_reminders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sender_id');
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('patient_user_id')->nullable();
            $table->string('type', 30); // 'medication' or 'visit'
            $table->string('title', 150);
            $table->text('message');
            $table->string('status', 30)->default('sent'); // 'sent', 'no_token', 'failed'
            $table->string('channel', 30)->default('fcm'); // 'fcm', 'in_app'
            $table->text('error_reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('sender_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('patient_id')->references('id')->on('patients')->onDelete('cascade');
            $table->index(['sender_id', 'type']);
            $table->index(['patient_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('patient_reminders');
    }
};
