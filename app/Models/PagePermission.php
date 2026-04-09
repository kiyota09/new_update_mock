<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PagePermission extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'module', 'page'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
