<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Query\Builder;

class DistanceMaterial extends BaseModel
{
    use HasFactory;

    public $table_name = "mektep_distance_materials_?";
}
