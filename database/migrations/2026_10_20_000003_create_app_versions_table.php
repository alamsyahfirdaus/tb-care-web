<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('app_versions')) {
            Schema::create('app_versions', function (Blueprint $table) {
                $table->id();
                $table->string('platform', 30)->default('android');
                $table->string('version_name', 50); // e.g. "1.0.1"
                $table->integer('version_code'); // e.g. 2
                $table->integer('min_supported_version_code')->default(1);
                $table->string('title', 150)->nullable();
                $table->text('release_notes')->nullable();
                $table->string('apk_path', 255)->nullable();
                $table->string('download_url', 500)->nullable();
                $table->unsignedBigInteger('file_size')->nullable();
                $table->string('sha256_checksum', 64)->nullable();
                $table->boolean('is_force_update')->default(false);
                $table->boolean('is_active')->default(true);
                $table->timestamp('published_at')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->index(['platform', 'is_active', 'version_code']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_versions');
    }
};
