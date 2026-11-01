<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddComorbiditiesAndScreeningExtrapulmonary extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 1. Tambahkan kolom comorbidities pada tabel patients jika belum ada
        if (Schema::hasTable('patients') && !Schema::hasColumn('patients', 'comorbidities')) {
            Schema::table('patients', function (Blueprint $table) {
                $table->text('comorbidities')->nullable()->after('blood_type');
            });
        }

        // 2. Tambahkan kolom klasifikasi TB & organ ekstraparu pada tabel screenings jika belum ada
        if (Schema::hasTable('screenings')) {
            Schema::table('screenings', function (Blueprint $table) {
                if (!Schema::hasColumn('screenings', 'tb_classification')) {
                    $table->string('tb_classification', 50)->default('TB Paru')->after('screening_category_id');
                }
                if (!Schema::hasColumn('screenings', 'extrapulmonary_organ')) {
                    $table->string('extrapulmonary_organ', 255)->nullable()->after('tb_classification');
                }
            });
        }

        // 3. Pastikan treatment_visits.visit_status dapat menyimpan status 'Dibatalkan' dan 'Selesai'
        if (Schema::hasTable('treatment_visits') && Schema::hasColumn('treatment_visits', 'visit_status')) {
            try {
                if (DB::getDriverName() !== 'sqlite') {
                    DB::statement("ALTER TABLE treatment_visits MODIFY visit_status VARCHAR(50) NOT NULL DEFAULT 'Terjadwal'");
                }
            } catch (\Throwable $e) {
                // Abaikan jika database tidak mendukung DDL MODIFY atau kolom sudah varchar
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('patients') && Schema::hasColumn('patients', 'comorbidities')) {
            Schema::table('patients', function (Blueprint $table) {
                $table->dropColumn('comorbidities');
            });
        }

        if (Schema::hasTable('screenings')) {
            Schema::table('screenings', function (Blueprint $table) {
                if (Schema::hasColumn('screenings', 'extrapulmonary_organ')) {
                    $table->dropColumn('extrapulmonary_organ');
                }
                if (Schema::hasColumn('screenings', 'tb_classification')) {
                    $table->dropColumn('tb_classification');
                }
            });
        }
    }
}
