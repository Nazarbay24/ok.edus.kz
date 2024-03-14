<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Student extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'mektep_students';
    public $timestamps = false;

    public function getIinAttribute() {
        return str_pad($this->attributes['iin'],12,'0',STR_PAD_LEFT);
    }
    public function class() : BelongsTo
    {
        return $this->belongsTo(ClassModel::class, 'id_class');
    }

    public function mektep(): BelongsTo
    {
        return $this->belongsTo(Mektep::class, 'id_mektep');
    }
}
