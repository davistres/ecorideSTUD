<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Utilisateur extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'UTILISATEUR';

    protected $primaryKey = 'user_id';

    protected $fillable = [
        'pseudo',
        'mail',
        'password_hash',
        'n_credit',
        'role',
    ];

    protected $hidden = [
        'password_hash',
    ];

    protected $casts = [
        'n_credit' => 'integer',
    ];

    public function chauffeur()
    {
        return $this->hasOne(Chauffeur::class, 'user_id');
    }

    public function confirmations()
    {
        return $this->hasMany(Confirmation::class, 'user_id');
    }

    public function satisfactions()
    {
        return $this->hasMany(Satisfaction::class, 'user_id');
    }

    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    public function getEmailForPasswordReset()
    {
        return $this->mail;
    }

    public function getEmailAttribute()
    {
        return $this->mail;
    }

    public function getNameAttribute()
    {
        return $this->pseudo;
    }
}