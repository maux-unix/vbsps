<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('parking_prediction', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('freeslots'); // Format: "15,15,6,12,...", panjang 15 angka
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('parking_prediction');
    }
};
