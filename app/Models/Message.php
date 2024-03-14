<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $table = 'ok_edus_message';

    public $timestamps = false;

    protected $fillable = [
        'from_id',
        'to_id',
        'to_class_id',
        'content',
        'read_status',
        'read_at',
        'created_at'
    ];
}
