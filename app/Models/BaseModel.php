<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

abstract class BaseModel extends Model
{
    public function init($id_mektep)
    {
        $this->table_name = Str::replaceArray('?', [$id_mektep, config('mektep_config.year')], $this->table_name);
        $this->setTable($this->table_name);
    }
}
