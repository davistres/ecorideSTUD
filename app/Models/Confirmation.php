<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Confirmation extends Model
{
    use HasFactory;

    protected $table = 'CONFIRMATION';

    protected $primaryKey = 'conf_id';

    public $timestamps = false;

    protected $fillable = [
        'covoit_id',
        'user_id',
    ];

    public function utilisateur()
    {
        return $this->belongsTo(Utilisateur::class, 'user_id');
    }

    public function covoiturage()
    {
        return $this->belongsTo(Covoiturage::class, 'covoit_id');
    }
}