<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FormJob extends Model
{
    protected $fillable = [
        'iron_job_id',
        'machine_id',
        'quantity',
        'product_id',
        'remarks',
        'operator_id',
        'shift',
        'code',
        'processed_at',
        'status',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'processed_at' => 'datetime',
        'status' => 'string',
    ];

    public function ironJob()
    {
        return $this->belongsTo(IronJob::class);
    }

    public function machine()
    {
        return $this->belongsTo(Machine::class);
    }

    public function product()
    {
        return $this->belongsTo(InvProduct::class, 'product_id');
    }

    public function operator()
    {
        return $this->belongsTo(User::class, 'operator_id');
    }

    public function packageItems()
    {
        return $this->hasMany(PackageItem::class);
    }
}
