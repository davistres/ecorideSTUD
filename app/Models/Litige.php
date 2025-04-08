<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Litige extends Model
{
    use HasFactory;

    protected $table = 'LITIGE';

    protected $primaryKey = 'litige_id';

    public $timestamps = false;

    protected $fillable = [
        'satisfaction_id',
        'statut_litige',
    ];

    public function satisfaction()
    {
        return $this->belongsTo(Satisfaction::class, 'satisfaction_id');
    }
}