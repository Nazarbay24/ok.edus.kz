<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Query\Builder;

class DistanceFeedback extends BaseModel
{
    use HasFactory;

    public $table_name = "mektep_distance_feedback_?";

    protected $fillable = [
        'diary_id',
        'student_id',
        'class_id',
        'material_id',
        'predmet_id',
        'teacher_id',
        'mark',
        'student_comment',
        'teacher_comment',
        'filename',
        'created_at',
        'answered_at',
        'views',
    ];

    protected $guarded = [];
}
