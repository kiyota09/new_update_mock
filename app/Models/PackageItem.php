<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PackageItem extends Model
{
    protected $fillable = [
        'package_id',
        'form_job_id',
        'quantity',
    ];

    protected $casts = [
        'quantity' => 'integer',
    ];

    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    public function formJob()
    {
        return $this->belongsTo(FormJob::class);
    }
}
