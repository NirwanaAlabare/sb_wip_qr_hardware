<?php

namespace App\Models\SignalBit;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\UserPassword as Authenticatable;

class UserSbWip extends Authenticatable
{
    use HasFactory;

    protected $connection = 'mysql_sb';

    protected $table = 'user_sb_wip';

    protected $primaryKey = 'id';

    protected $fillable = [
        'id',
        'name',
        'username',
        'password',
        'line_id',
        'remember_token',
        'created_at',
        'updated_at'
    ];

    public function line()
    {
        return $this->belongsTo(UserPassword::class, 'line_id', 'line_id');
    }

    public function masterPlans()
    {
        return $this->hasManyThrough(
            MasterPlan::class,
            UserPassword::class,
            'username', // Foreign key on the environments table...
            'sewing_line', // Foreign key on the deployments table...
            'line_id', // Local key on the projects table...
            'line_id' // Local key on the environments table...
        );
    }
}
