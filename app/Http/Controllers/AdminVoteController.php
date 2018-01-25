<?php

namespace App\Http\Controllers;

use App\Answer;
use Illuminate\Http\Request;

class AdminVoteController extends Controller
{
    //
    function clearAllAnswers(Request $request)
    {
        Answer::all()->each(function ($item, $key) {
           $item->delete();
        });
    }
}
