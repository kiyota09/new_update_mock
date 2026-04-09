<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Client extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'company_name',
        'business_type',
        'tin_number',
        'contact_person',
        'email',
        'password',
        'phone',
        'company_address',
        'city',
        'province',
        'postal_code',
        'status',
        'credit_limit',
        'payment_terms_days',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'credit_limit' => 'decimal:2',
        'payment_terms_days' => 'integer',
    ];

    /**
     * Scope a query to only include active clients.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function meetings()
    {
        return $this->hasMany(CrmMeeting::class);
    }

    public function feedback()
    {
        return $this->hasMany(CrmFeedback::class);
    }

    public function creditAccount()
    {
        // Adjust 'CreditAccount::class' to your actual Credit model name
        return $this->hasOne(CreditAccount::class);
    }

    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class);
    }
}
