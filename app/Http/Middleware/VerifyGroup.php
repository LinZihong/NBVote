<?php

namespace App\Http\Middleware;

use Closure;
use App\Ticket;
use Illuminate\Support\Facades\Lang;

class VerifyGroup
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
		if (empty($ticket = Ticket::ticket($request->route()[2]['ticket'])) || $ticket->active != 1) {
			return JsonStatus('我们似乎不认识这张票...', 401);
		}
		return $next($request);
	}
}
