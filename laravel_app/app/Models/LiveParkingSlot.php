<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LiveParkingSlot extends Model
{
    use HasFactory;
protected $table = 'live_parking_slot';

    protected $fillable = [
        'date',
        'time',
        'slot',
        'freeslot',
        'aws_crc',
        'aws_data',
        'esp32_crc',
        'esp32_data',
    ];
}
