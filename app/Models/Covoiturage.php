<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Covoiturage extends Model
{
    use HasFactory;

    protected $table = 'COVOITURAGE';

    protected $primaryKey = 'covoit_id';

    public $timestamps = false;

    protected $fillable = [
        'driver_id',
        'immat',
        'departure_address',
        'add_dep_address',
        'postal_code_dep',
        'city_dep',
        'arrival_address',
        'add_arr_address',
        'postal_code_arr',
        'city_arr',
        'departure_date',
        'arrival_date',
        'departure_time',
        'arrival_time',
        'max_travel_time',
        'price',
        'n_tickets',
        'eco_travel',
        'trip_started',
        'trip_completed',
        'cancelled',
    ];

    protected $casts = [
        'departure_date' => 'date',
        'arrival_date' => 'date',
        'price' => 'integer',
        'n_tickets' => 'integer',
        'eco_travel' => 'boolean',
        'trip_started' => 'boolean',
        'trip_completed' => 'boolean',
        'cancelled' => 'boolean',
    ];

    protected $attributes = [
        'trip_started' => false,
        'trip_completed' => false,
        'cancelled' => false,
    ];

    public function chauffeur()
    {
        return $this->belongsTo(Chauffeur::class, 'driver_id');
    }

    public function voiture()
    {
        return $this->belongsTo(Voiture::class, 'immat');
    }

    public function confirmations()
    {
        return $this->hasMany(Confirmation::class, 'covoit_id');
    }

    public function satisfactions()
    {
        return $this->hasMany(Satisfaction::class, 'covoit_id');
    }

    public function getPlacesRestantesAttribute()
    {
        return $this->n_tickets - $this->confirmations()->count();
    }

    public function getEstDisponibleAttribute()
    {
        return
            !$this->trip_started &&
            !$this->trip_completed &&
            !$this->cancelled &&
            $this->departure_date->isFuture() &&
            $this->places_restantes > 0;
    }
}
