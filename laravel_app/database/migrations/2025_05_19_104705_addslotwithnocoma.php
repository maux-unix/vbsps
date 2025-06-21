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
	    Schema::table('live_parking_slot', function (Blueprint $table) {
        	$table->string('esp32_data');
            $table->bigInteger('esp32_crc');
            $table->string('aws_data');
            $table->bigInteger('aws_crc');
    	});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('live_parking_slot', function (Blueprint $table) {
            $table->dropColumn([
                'esp32_data',
                'esp32_crc',
                'aws_data',
                'aws_crc',
            ]);
        });
    }
    
};
