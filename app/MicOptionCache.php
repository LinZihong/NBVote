<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MicOptionCache extends Model
{
    //
    protected  $table = 'mic_option_cache';
    protected $fillable = ['options', 'ticket_string', 'vote_id'];
    protected $casts = [
        'options' => 'array'
    ];
}
