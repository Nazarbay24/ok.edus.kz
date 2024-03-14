<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MessageGroupRead extends Model
{
    protected $table = 'ok_edus_message_group_read';

    public $timestamps = false;

    protected $fillable = [
        'to_id',
        'to_class_id',
        'message_id',
        'read_status',
        'read_at',
    ];
}
