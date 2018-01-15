<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use App\Ticket;
use App\Vote;
use Illuminate\Support\Facades\Lang;

class VerifyResult
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
	    if(CheckLogin($request) && CurrentUser($request)->role == 'admin')
        {
            return $next($request);
        }
		// @TODO Please Check
		if (empty($request->route()[2]['ticket']) && !CheckLogin($request)) {
            return JsonStatus('Unauthorized.', 401);
		}

		if (empty($vote = Vote::find($request->route()[2]['id']))) { //check if vote exists
            return JsonStatus('Vote not found', 401);
		}

		if ($vote->show_result != 1){
            return JsonStatus("Voted Successfully, but results are not shown.", 401);
		}

		if (!empty($ticket = Ticket::ticket($request->route()[2]['ticket'])) && ($vote->type == 1 || $vote->type == 2)) {
			if ($ticket->active == 0){	
                return JsonStatus('Ticket invalid', 401);
			}
			if ($ticket->isTicketUsed($vote->id)) {
				return $next($request);  // Ticket has used for this Vote !
			}
            return JsonStatus('Not voted yet!', 401);
		}

		// If user login to vote, then go with this check
		if ($user = CurrentUser($request) && ($vote->type == 0 || $vote->type == 2)) {
			if ($user->isUserVoted($vote->id)) {
				return $next($request);
			}
            return JsonStatus('Not voted yet!', 401);
		}

        return JsonStatus('Invalid credentials', 401);
	}
  
}
