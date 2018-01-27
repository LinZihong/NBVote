<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use App\Vote;
use App\Ticket;

class VerifyVote
{
	/**
	 * Handle an incoming request.
	 *
	 * @param  \Illuminate\Http\Request $request
	 * @param  \Closure $next
	 * @return mixed
	 */
	public function handle($request, Closure $next)
	{
		// general Checking
        $ticketStr = $request->route()[2]['ticket'];
        $voteId = $request->route()[2]['id'];//@TODO check if out-of-index error
		if (empty($ticketStr) && !CheckLogin($request)) {
            return JsonStatus('Need to login or a ticket.', 401);
		}

		if (empty($vote = Vote::find($voteId))) { //check if vote exists
            return JsonStatus('您似乎来到了投票的荒原...这里什么也没有', 401);
		}

		if (strtotime($vote->ended_at) - strtotime('now') < 0) {
            return JsonStatus('该投票已结束啦', 401);
		}

		if (strtotime($vote->started_at) - strtotime('now') > 0) {
		    return JsonStatus('该投票尚未开始哦', 401);
		}

		// Categorize

		// If user use ticket to vote, then go with this check
		if ((!empty($ticket = Ticket::ticket($ticketStr))) && ($vote->type == 1 || $vote->type == 2)) {
			if ($ticket->active == 1) { // check if ticket is valid
				if (!$ticket->isTicketUsed($vote->id)) {
					$request->merge(['type' => 'ticket']); //将该请求归类到Ticket类型
					return $next($request);
				}
                return JsonStatus('该票卷已经投过这个投票啦', 401);
			}
            return JsonStatus('该票卷无效', 401);
		}

		// If user login to vote, then go with this check
		if (CheckLogin($request) && ($vote->type == 0 || $vote->type == 2)) {
			$user = CurrentUser($request);
			if (!$user->isUserVoted($vote->id)) {
				$request->merge(['type' => 'user']); //将该请求归类到User类型
				return $next($request);
			}
            return JsonStatus('User voted', 401);
		}

        return JsonStatus('票据验证未通过...', 401);
	}
}
