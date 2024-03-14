<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;



class Teacher extends BaseModel
{
    use HasFactory;

    protected $table = 'mektep_teacher';
    public $timestamps = false;


    public function mektep() {
        return $this->hasOne(Mektep::class, 'id_mektep', 'id');
    }
}
