<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    use HasFactory;

    protected $fillable = [
        'reporter_id',
        'reported_id',
        'reason',
        'description',
        'status',
        'priority',
        'admin_action',
        'admin_action_at',
        'admin_notes',
    ];

    protected $casts = [
        'admin_action_at' => 'datetime',
    ];

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function reported()
    {
        return $this->belongsTo(User::class, 'reported_id');
    }
}
