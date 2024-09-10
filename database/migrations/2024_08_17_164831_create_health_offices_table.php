<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHealthOfficesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('health_offices', function (Blueprint $table) {
            $table->id();
            $table->integer('office_type_id')->nullable();
            $table->text('office_address')->nullable();
            $table->foreignId('district_id')->nullable()->constrained('districts')->onUpdate('cascade')->onDelete('cascade');
            $table->string('telephone')->nullable();
            $table->string('email')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('health_offices');
    }
}
