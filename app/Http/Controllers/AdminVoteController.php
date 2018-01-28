<?php

namespace App\Http\Controllers;

use App\Answer;
use App\MicOptionCache;
use App\Ticket;
use App\Vote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminVoteController extends Controller
{
    //
    function clearAllAnswers(Request $request)
    {
        Answer::all()->each(function ($item, $key) {
            $item->delete();
        });
        MicOptionCache::all()->each(function ($item, $key) {
            $item->delete();
        });
        return JsonStatus('Done');
    }

    function clearTicketAnswers(Request $request)
    {
        $ticket = Ticket::ticket($request->route()[2]['ticket']);
        Answer::where('source_type', 'App\Ticket')->where('source_id', $ticket->id)->get()->each(function ($item, $key) {
            $item->delete();
        });
        return JsonStatus('Done');
    }

    function operateTickets(Request $request)
    {
        $op = $request->route()[2]['operation'];
        $from = $request->route()[2]['from'];
        $to = $request->route()[2]['to'];
        for ($i = $from; $i <= $to; $i++) {
            $ticket = Ticket::find($i);
            $ticket->active = $op == 'enable' ? 1 : 0;
            $ticket->save();
        }
        return JsonStatus('Done');
    }

    function calcTimes(Request $request)
    {
        Vote::all()->each(function ($item, $key){
            $item->voted_count = count($item->votedIds());
            $item->save();
        });
        return JsonStatus('Done');
    }
}
