<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CrmLead extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_name',
        'contact_person',
        'email',
        'phone',
        'interest_fabric',
        'estimated_value',
        'status',
        'assigned_staff_id', // Critical for linking to the staff member
        'lost_reason',
    ];

    public function staff()
    {
        return $this->belongsTo(User::class, 'assigned_staff_id');
    }

    public function interactions()
    {
        return $this->morphMany(CrmInteraction::class, 'contactable');
    }

    public function notes()
    {
        return $this->hasMany(LeadNote::class, 'lead_id');
    }

    public function interviews()
    {
        return $this->hasMany(LeadInterview::class, 'lead_id');
    }

    public function approvalFiles()
    {
        return $this->hasMany(LeadApprovalFile::class, 'lead_id');
    }

    public function assignedStaff()
    {
        return $this->belongsTo(User::class, 'assigned_staff_id');
    }
}
