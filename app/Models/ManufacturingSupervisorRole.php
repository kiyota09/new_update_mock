<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ManufacturingSupervisorRole extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'manufacturing_role'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
