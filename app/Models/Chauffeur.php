<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Chauffeur extends Model
{
    use HasFactory;

    protected $table = 'CHAUFFEUR';

    protected $primaryKey = 'driver_id';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'pref_smoke',
        'pref_pet',
        'pref_libre',
        'moy_note',
    ];

    protected $casts = [
        'moy_note' => 'float',
    ];

    public function utilisateur()
    {
        return $this->belongsTo(Utilisateur::class, 'user_id');
    }

    public function voitures()
    {
        return $this->hasMany(Voiture::class, 'driver_id');
    }

    public function covoiturages()
    {
        return $this->hasMany(Covoiturage::class, 'driver_id');
    }
}
