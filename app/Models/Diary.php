<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Query\Builder;

class Diary extends BaseModel
{
    use HasFactory;

    public $table_name = "mektep_diary_?_?";
}
