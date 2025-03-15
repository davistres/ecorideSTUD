<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Admin extends Authenticatable
{
    use Notifiable;

    protected $table = 'ADMIN';

    protected $primaryKey = 'admin_id';

    protected $fillable = ['mail', 'password_hash'];

    protected $hidden = ['password_hash'];

    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    public function getEmailAttribute()
    {
        return $this->mail;
    }
}
