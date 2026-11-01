<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RebuildTbcareAdminSchema extends Migration
{
    public function up()
    {
        // 1. Upgrade table 'screening' into full 'screenings' or upgrade existing 'screening' table
        if (!Schema::hasTable('screenings')) {
            // Rename 'screening' to 'screenings' if it exists and is empty
            if (Schema::hasTable('screening')) {
                Schema::rename('screening', 'screenings');
            } else {
                Schema::create('screenings', function (Blueprint $table) {
                    $table->id();
                    $table->timestamps();
                });
            }
        }

        Schema::table('screenings', function (Blueprint $table) {
            if (!Schema::hasColumn('screenings', 'code')) {
                $table->string('code', 50)->nullable()->unique()->after('id');
            }
            if (!Schema::hasColumn('screenings', 'user_id')) {
                $table->foreignId('user_id')->nullable()->after('code')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('screenings', 'patient_id')) {
                $table->foreignId('patient_id')->nullable()->after('user_id')->constrained('patients')->nullOnDelete();
            }
            if (!Schema::hasColumn('screenings', 'person_name')) {
                $table->string('person_name', 255)->nullable()->after('patient_id');
            }
            if (!Schema::hasColumn('screenings', 'nik')) {
                $table->string('nik', 30)->nullable()->after('person_name');
            }
            if (!Schema::hasColumn('screenings', 'phone')) {
                $table->string('phone', 30)->nullable()->after('nik');
            }
            if (!Schema::hasColumn('screenings', 'age')) {
                $table->integer('age')->default(0)->after('phone');
            }
            if (!Schema::hasColumn('screenings', 'gender')) {
                $table->enum('gender', ['L', 'P'])->default('L')->after('age');
            }
            if (!Schema::hasColumn('screenings', 'province_id')) {
                $table->foreignId('province_id')->nullable()->after('gender')->constrained('provinces')->nullOnDelete();
            }
            if (!Schema::hasColumn('screenings', 'district_id')) {
                $table->foreignId('district_id')->nullable()->after('province_id')->constrained('districts')->nullOnDelete();
            }
            if (!Schema::hasColumn('screenings', 'subdistrict_id')) {
                $table->foreignId('subdistrict_id')->nullable()->after('district_id')->constrained('subdistricts')->nullOnDelete();
            }
            if (!Schema::hasColumn('screenings', 'village_id')) {
                $table->foreignId('village_id')->nullable()->after('subdistrict_id')->constrained('villages')->nullOnDelete();
            }
            if (!Schema::hasColumn('screenings', 'address')) {
                $table->text('address')->nullable()->after('village_id');
            }
            if (!Schema::hasColumn('screenings', 'puskesmas_id')) {
                $table->foreignId('puskesmas_id')->nullable()->after('address')->constrained('puskesmas')->nullOnDelete();
            }
            if (!Schema::hasColumn('screenings', 'screening_category_id')) {
                $table->foreignId('screening_category_id')->nullable()->after('puskesmas_id')->constrained('screening_categories')->nullOnDelete();
            }
            if (!Schema::hasColumn('screenings', 'total_score')) {
                $table->integer('total_score')->default(0)->after('screening_category_id');
            }
            if (!Schema::hasColumn('screenings', 'risk_level')) {
                $table->enum('risk_level', ['Risiko Rendah', 'Risiko Sedang', 'Risiko Tinggi'])->default('Risiko Rendah')->after('total_score');
            }
            if (!Schema::hasColumn('screenings', 'status')) {
                $table->enum('status', ['Perlu Tindak Lanjut', 'Dalam Pemantauan', 'Selesai'])->default('Selesai')->after('risk_level');
            }
            if (!Schema::hasColumn('screenings', 'symptoms_count')) {
                $table->integer('symptoms_count')->default(0)->after('status');
            }
            if (!Schema::hasColumn('screenings', 'has_critical_symptom')) {
                $table->boolean('has_critical_symptom')->default(false)->after('symptoms_count');
            }
            if (!Schema::hasColumn('screenings', 'recommendation')) {
                $table->text('recommendation')->nullable()->after('has_critical_symptom');
            }
            if (!Schema::hasColumn('screenings', 'notes')) {
                $table->text('notes')->nullable()->after('recommendation');
            }
            if (!Schema::hasColumn('screenings', 'screened_at')) {
                $table->timestamp('screened_at')->nullable()->after('notes');
            }
        });

        // 2. Table screening_answers
        if (!Schema::hasTable('screening_answers')) {
            Schema::create('screening_answers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('screening_id')->constrained('screenings')->onDelete('cascade');
                $table->foreignId('screening_question_id')->nullable()->constrained('screening_questions')->nullOnDelete();
                $table->text('question_text')->nullable();
                $table->string('group_name', 100)->nullable();
                $table->string('answer', 255)->default('0'); // '1' = Ya, '0' = Tidak, or custom text
                $table->boolean('is_critical')->default(false);
                $table->integer('score')->default(0);
                $table->integer('duration_days')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        // 3. Table clinical_examinations
        if (!Schema::hasTable('clinical_examinations')) {
            Schema::create('clinical_examinations', function (Blueprint $table) {
                $table->id();
                $table->string('examination_code', 50)->unique();
                $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
                $table->foreignId('puskesmas_id')->nullable()->constrained('puskesmas')->nullOnDelete();
                $table->foreignId('officer_id')->nullable()->constrained('officers')->nullOnDelete();
                $table->date('examination_date');
                $table->string('examination_type', 100)->default('Tes Cepat Molekuler (TCM)');
                $table->string('result', 100)->default('Negatif');
                $table->string('diagnosis', 255)->nullable();
                $table->enum('status', ['Menunggu Hasil', 'Selesai', 'Perlu Pemeriksaan Ulang'])->default('Selesai');
                $table->text('laboratory_notes')->nullable();
                $table->string('attachment', 255)->nullable();
                $table->timestamps();
            });
        }

        // 4. Table close_contacts
        if (!Schema::hasTable('close_contacts')) {
            Schema::create('close_contacts', function (Blueprint $table) {
                $table->id();
                $table->string('contact_code', 50)->unique();
                $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
                $table->string('name', 255);
                $table->string('nik', 30)->nullable();
                $table->string('relationship', 100)->default('Keluarga Serumah');
                $table->enum('gender', ['L', 'P'])->default('L');
                $table->integer('age')->default(0);
                $table->string('phone', 30)->nullable();
                $table->text('address')->nullable();
                $table->date('screening_date')->nullable();
                $table->string('screening_result', 100)->default('Sehat / Tidak Bergejala');
                $table->string('tpt_status', 100)->default('Tidak Perlu');
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        // 5. Table system_notifications
        if (!Schema::hasTable('system_notifications')) {
            Schema::create('system_notifications', function (Blueprint $table) {
                $table->id();
                $table->string('title', 255);
                $table->text('message');
                $table->string('type', 100)->default('Pengumuman');
                $table->string('target_role', 50)->default('Semua');
                $table->foreignId('target_puskesmas_id')->nullable()->constrained('puskesmas')->nullOnDelete();
                $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
                $table->integer('sent_count')->default(0);
                $table->enum('status', ['Terkirim', 'Draft', 'Dijadwalkan'])->default('Terkirim');
                $table->timestamps();
            });
        }

        // 6. Table activity_logs
        if (!Schema::hasTable('activity_logs')) {
            Schema::create('activity_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('user_name', 255)->nullable();
                $table->string('activity', 255);
                $table->string('module', 100);
                $table->text('description')->nullable();
                $table->string('ip_address', 50)->nullable();
                $table->text('user_agent')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('system_notifications');
        Schema::dropIfExists('close_contacts');
        Schema::dropIfExists('clinical_examinations');
        Schema::dropIfExists('screening_answers');
        // Do not drop screenings to prevent data loss
    }
}
