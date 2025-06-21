<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('check_sum_analysis', function (Blueprint $table) {
            $table->id();
            $table->string('slot_parkir');
            $table->unsignedBigInteger('checksum_aws');
            $table->unsignedBigInteger('checksum_esp32');
            $table->boolean('status'); // true jika sama, false jika berbeda
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('check_sum_analysis');
    }
};
