<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('live_parking_slot', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->time('time');
            $table->string('slot'); // Format: "0,1,1,0,..." (panjang 16 elemen)
            $table->unsignedTinyInteger('freeslot'); // Maksimum 16
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('live_parking_slot');
    }
};
