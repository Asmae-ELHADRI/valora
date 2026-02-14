<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GradeAttribution extends Model
{
    use HasFactory;

    protected $fillable = [
        'prestataire_id',
        'grade_id',
        'sys_user_id',
        'type'
    ];

    public function prestataire()
    {
        return $this->belongsTo(Prestataire::class);
    }

    public function grade()
    {
        return $this->belongsTo(Grade::class);
    }

    public function assigner()
    {
        return $this->belongsTo(User::class, 'sys_user_id');
    }
}
