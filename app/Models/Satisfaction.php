<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Satisfaction extends Model
{
    use HasFactory;

    protected $table = 'SATISFACTION';

    protected $primaryKey = 'satisfaction_id';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'covoit_id',
        'feeling',
        'comment',
        'review',
        'note',
        'date',
    ];

    protected $casts = [
        'feeling' => 'boolean',
        'note' => 'integer',
        'date' => 'date',
    ];

    public function utilisateur()
    {
        return $this->belongsTo(Utilisateur::class, 'user_id');
    }

    public function covoiturage()
    {
        return $this->belongsTo(Covoiturage::class, 'covoit_id');
    }

    public function litige()
    {
        return $this->hasOne(Litige::class, 'satisfaction_id');
    }
}
