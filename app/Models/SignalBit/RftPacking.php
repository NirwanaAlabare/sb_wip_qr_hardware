<?php

namespace App\Models\SignalBit;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RftPacking extends Model
{
    use HasFactory;

    protected $connection = 'mysql_sb';

    protected $table = 'output_rfts_packing';

    protected $fillable = [
        'id',
        'master_plan_id',
        'so_det_id',
        'no_cut_size',
        'kode_numbering',
        'status',
        'rework_id',
        'created_at',
        'updated_at',
        'created_by',
    ];
}
