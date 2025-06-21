<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CheckSumAnalysis extends Model
{
    use HasFactory;
    protected $table = 'check_sum_analysis';

    protected $fillable = [
        'slot_parkir',
        'checksum_aws',
        'checksum_esp32',
        'status',
    ];
}
