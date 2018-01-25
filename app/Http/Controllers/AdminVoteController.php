<?php

namespace App\Http\Controllers;

use App\Answer;
use App\Ticket;
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

    function clearTicketAnswers(Request $request)
    {
        $ticket = Ticket::ticket($request->route()[2]['ticket']);
        Answer::where('source_type', 'ticket')->where('source_id', $ticket->id)->get()->each(function ($item, $key) {
            $item->delete();
        });
    }
}
