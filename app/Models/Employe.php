<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Employe extends Authenticatable
{
    use Notifiable;

    protected $table = 'EMPLOYE';

    protected $primaryKey = 'employe_id';

    protected $fillable = ['mail', 'password_hash', 'name'];

    protected $hidden = ['password_hash'];
    public $timestamps = false;

    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    public function getEmailAttribute()
    {
        return $this->mail;
    }
}
