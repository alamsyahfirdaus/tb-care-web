<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('screening_id')->constrained('screenings')->onDelete('cascade');
            $table->string('category_name');
            // $table->timestamps();
        });

        DB::table('categories')->insert([
            // Data untuk Screening ID 1
            ['screening_id' => 1, 'category_name' => 'Usia 15 Tahun ke Atas'],
            ['screening_id' => 1, 'category_name' => 'Usia Di Bawah 15 Tahun'],
        ]);        

        DB::table('categories')->insert([
            // Data untuk Screening ID 2
            ['screening_id' => 2, 'category_name' => 'Apakah Anda pernah didiagnosis atau menjalani pengobatan TBC sebelumnya?'],
            ['screening_id' => 2, 'category_name' => 'Apakah Anda pernah menjalani pengobatan TBC tetapi tidak menyelesaikannya hingga tuntas?'],
            ['screening_id' => 2, 'category_name' => 'Apakah Anda mengalami kekurangan gizi atau berat badan yang rendah?'],
            ['screening_id' => 2, 'category_name' => 'Apakah Anda merupakan perokok aktif?'],
            ['screening_id' => 2, 'category_name' => 'Apakah Anda sering terpapar asap rokok (sebagai perokok pasif)?'],
            ['screening_id' => 2, 'category_name' => 'Apakah Anda memiliki riwayat diabetes melitus (DM) atau penyakit kencing manis?'],
            ['screening_id' => 2, 'category_name' => 'Apakah Anda merupakan orang yang hidup dengan HIV/AIDS (ODHIV)?'],
            ['screening_id' => 2, 'category_name' => 'Apakah usia Anda saat ini lebih dari 65 tahun (lansia)?'],
            ['screening_id' => 2, 'category_name' => 'Apakah Anda saat ini sedang dalam kondisi hamil?'],
        ]);

        DB::table('categories')->insert([
            // Data untuk Screening ID 3
            ['screening_id' => 3, 'category_name' => 'Apakah Anda mengalami batuk, baik ringan maupun berat?'],
            ['screening_id' => 3, 'category_name' => 'Apakah Anda mengalami batuk darah?'],
            ['screening_id' => 3, 'category_name' => 'Apakah berat badan Anda menurun tanpa sebab yang jelas, atau apakah nafsu makan Anda berkurang?'],
            ['screening_id' => 3, 'category_name' => 'Apakah Anda sering mengalami demam yang hilang timbul tanpa penyebab yang jelas?'],
            ['screening_id' => 3, 'category_name' => 'Apakah Anda sering berkeringat di malam hari tanpa adanya aktivitas fisik sebelumnya?'],
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('categories');
    }
}
