<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Utilisateur extends Authenticatable
{
    use Notifiable;

    public $timestamps = false;

    protected $table = 'UTILISATEUR';

    protected $primaryKey = 'user_id';

    protected $fillable = [
        'pseudo', 'mail', 'password_hash', 'n_credit', 'role'
    ];

    protected $hidden = [
        'password_hash'
    ];

    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    public function getEmailAttribute()
    {
        return $this->mail;
    }
}
