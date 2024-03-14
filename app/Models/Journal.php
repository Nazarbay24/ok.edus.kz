<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Journal extends BaseModel
{
    use HasFactory;

    public $table_name = "mektep_jurnal_?_?";

    protected $primaryKey = 'jurnal_id';
    public $timestamps = false;

    protected $fillable = [
        'jurnal_date',
        'jurnal_predmet',
        'jurnal_student_id',
        'jurnal_mark'	,
        'jurnal_teacher_id'	,
        'jurnal_lesson',
        'jurnal_class_id',
    ];
}
