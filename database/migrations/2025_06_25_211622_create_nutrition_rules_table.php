<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNutritionRulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('nutrition_rules', function (Blueprint $table) {
            $table->id();

            // Usia dalam bulan
            $table->unsignedInteger('min_age_months')->default(0);
            $table->unsignedInteger('max_age_months')->nullable();

            // Jenis indeks: BB/TB, BB/PB, IMT/U, IMT
            $table->string('index_type');

            // Kategori status gizi: Gizi Buruk, Normal, Obesitas, dll
            $table->string('category');

            // Batas nilai z-score atau IMT
            $table->float('z_score_min')->nullable();
            $table->float('z_score_max')->nullable();

            // Deskripsi tambahan (opsional)
            // $table->text('description')->nullable();

            // $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('nutrition_rules');
    }
}
