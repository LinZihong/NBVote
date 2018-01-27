<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MicOptionCache extends Model
{
    //
    protected $fillable = ['options', 'ticket_string', 'vote_id'];
    protected $casts = [
        'options' => 'array'
    ];
}
