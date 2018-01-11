<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OptionCache extends Model
{
    //
    protected $fillable = ['option', 'status', 'ticket', 'update_time'];
}
