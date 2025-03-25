<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Voiture extends Model
{
    use HasFactory;

    protected $table = 'VOITURE';

    protected $primaryKey = 'immat';


    protected $keyType = 'string';

    public $timestamps = false;

    public $incrementing = false;

    protected $fillable = [
        'immat',
        'driver_id',
        'date_first_immat',
        'brand',
        'model',
        'color',
        'n_place',
        'energie',
    ];

    protected $casts = [
        'date_first_immat' => 'date',
        'n_place' => 'integer',
    ];

    public function chauffeur()
    {
        return $this->belongsTo(Chauffeur::class, 'driver_id');
    }

    public function covoiturages()
    {
        return $this->hasMany(Covoiturage::class, 'immat');
    }
}
