<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IronJob extends Model
{
    protected $fillable = [
        'squeezer_job_id',
        'remarks',
        'operator_id',
        'shift',
        'code',
        'processed_at',
    ];

    protected $casts = [
        'processed_at' => 'datetime',
    ];

    public function squeezerJob()
    {
        return $this->belongsTo(SqueezerJob::class);
    }

    public function operator()
    {
        return $this->belongsTo(User::class, 'operator_id');
    }

    public function formJob()
    {
        return $this->hasOne(FormJob::class);
    }
}
