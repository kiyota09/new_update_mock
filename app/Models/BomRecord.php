<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BomRecord extends Model
{
    protected $table = 'bom_records';
    protected $fillable = ['client_id', 'product_id', 'yarn_type', 'dye_color', 'weave_design', 'materials'];
    protected $casts = ['materials' => 'array'];

    public function client() { return $this->belongsTo(Client::class); }
    public function product() { return $this->belongsTo(Product::class); }
}