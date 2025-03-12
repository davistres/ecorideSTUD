<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Message extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'messages';

    protected $fillable = [
        'nom',
        'mail',
        'sujet',
        'message',
        'date_envoi',
        'statut'
    ];

    protected $casts = [
        'date_envoi' => 'datetime',
    ];

    public static function rules()
    {
        return [
            'nom' => 'required|string',
            'mail' => 'required|email',
            'sujet' => 'required|in:Support technique,Problème réservation,Autre',
            'message' => 'required|string|max:1800',
            'statut' => 'in:Non-traité,En cours,Résolu',
        ];
    }
}
