<?php

namespace App\Http\Controllers;

use App\Answer;

use App\Events\UpdateModelIPAddress;
use App\MicOptionCache;
use App\OptionCache;
use Carbon\Carbon;
use Illuminate\Http\Response;
use App\VoteGroup;
use Illuminate\Http\Request;
use App\Ticket;
use App\Vote;
use App\Option;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Cache;


class VoteController extends Controller
{
	/**
	 * VoteController constructor.
	 */
	public function __construct()
	{
	}

	/**
	 * show all votes
	 *
	 * @TODO 研究一下预加载
	 * @return mixed
	 */
	public function index()
	{
		$votes = VoteGroup::with('votes')->orderBy('created_at', 'desc')->get();

		return JsonData($votes);
	}

	/**
	 * show vote group
	 *
	 * @param Request $request
	 * @return mixed
	 */
	public function showVoteGroup(Request $request)
	{
		$ticket = Ticket::where('string', $request->route()[2]['ticket'])->with('voteGroup.votes')->firstOrFail();
		$ticketArr = $ticket->toArray();
		foreach ($ticketArr['vote_group']['votes'] as $index => &$vote) {
			$vote['is_voted'] = $ticket->isTicketUsed($vote['id']) ? '1' : '0';
			$vote['times'] = count($ticket->voteGroup->votes[$index]->votedIds());
		}

		return JsonData($ticketArr);
	}

	/**
	 * show vote pages
	 *
	 * @param $id
	 * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
	 * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
	 */
	public function showIndividualVote(Request $request)
	{
		$id = $request->route()[2]['id'];
		// return Vote::find($id)->with('questions', 'questions.options')->first();
		$vote = Vote::with('questions.options')->find($id);
		$ticket = $request->route()[2]['ticket'];
		$vote['times'] = count($vote->votedIds());
		$vote['is_voted'] = Ticket::ticket($ticket)->isTicketUsed($id) ? '1' : '0';
		$vote['has_selected'] = $this->getCachedOptions($ticket, $id);

		return JsonData(['vote' => $vote, 'ticket' => $ticket]);
	}

	/**
	 * Vote handler :)
	 *
	 * @TODO String type Answer still Need Further Step
	 * @param Request $request
	 * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
	 */
	public function voteHandler(Request $request)
	{
		// init
		$voteId = $request->route()[2]['id'];
		$ticket = Ticket::ticket($request->route()[2]['ticket']);
		$answers = collect(json_decode($request->getContent(), true)['selected']);
//		return $answers;
		if ($answers->isEmpty()) {
			return JsonStatus('非法表单', 401);
		}
		$vote = Vote::find($voteId);
		$voteIsValid = false;

		if (!$this->checkIfAllFilled($answers, $vote)) { //并且所有的选项填完了
			return JsonStatus('有题目未填', 401);
		}
		if ($this->checkIfNoRepeatingOptions($answers) && $this->checkIfOptionsFilledMatch($answers, $vote)) {
			$voteIsValid = true;
		}

		if ($voteIsValid) {  // Safety First :)
			switch ($request['type']) {  // Start Dash!
				case 'ticket':
					foreach ($answers as $answer) {
						$modelAns = new Answer;
						$modelAns->option_id = $answer;
						$modelAns->source_id = $ticket->id;
						$modelAns->source_type = 'App\Ticket';
						$modelAns->saveOrFail();
					}
					event(new UpdateModelIPAddress('ticket', $ticket->id, 'vote.ticket', $request->ip()));

//					return redirect('/vote/id/' . $voteId . '/ticket/' . $ticket->string . '/result/');
					break;
				case 'user':
					$userId = $request->user()->id;
					foreach ($answers as $answer) {
						$modelAns = new Answer;
						$modelAns->option_id = $answer;
						$modelAns->source_id = $userId;
						$modelAns->source_type = 'App\User';
						$modelAns->saveOrFail();
					}

//					return redirect('/vote/id/' . $voteId . '/result/');
					break;
			}
			if ($vote->show_result == 0) {
				return JsonData(['result' => 'Voted Successfully', 'show_result' => 'false']);
			} else {
				return JsonData(['result' => 'Voted Successfully', 'show_result' => 'true']);//Negotiate frontend url rules
			}
		} else {
			return JsonStatus('提交的数据不合法', 401);
		}
	}

	public function cacheOptions(Request $request)
	{
		$ticket = $request->route()[2]['ticket'];
		$vote_id = $request->route()[2]['id'];
		$answers = json_decode($request->getContent(), true)['selected'];
		if(empty($cached = MicOptionCache::where('ticket_string', $ticket)->where('vote_id', $vote_id)->first()))
        {
            MicOptionCache::create([
               'vote_id' => $vote_id,
               'ticket_string' => $ticket,
               'options' => $answers
            ]);
        }
        else
        {
            $cached->options = $answers;
            $cached->save();
        }

		return JsonStatus('Cached!');
	}

	protected function getCachedOptions($ticket, $id)
	{
		return MicOptionCache::where('ticket_string', $ticket)->where('vote_id', $id)->first();
	}

	/**
	 * Show Vote Result :)
	 *
	 * @param Request $request
	 * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
	 */
	public function showVoteResult(request $request)
	{
		$voteId = $request->route()[2]['id'];

		$vote = Vote::with('questions.options')->find($voteId);
		$voteArr = $vote->toArray();
		$ticketAns = Ticket::ticket($request->route()[2]['ticket'])->answers->map(function ($item, $key) {
			return $item->option_id;
		});
		foreach ($voteArr['questions'] as $i => $question) {
			foreach ($question['options'] as $j => $option) {
				$voteArr['questions'][$i]['options'][$j]['count'] = count($vote['questions'][$i]['options'][$j]->answers);
				$voteArr['questions'][$i]['options'][$j]['percent'] = round(($vote['questions'][$i]['options'][$j]->getTotalNumber() / $vote['questions'][$i]->getTotalNumber()) * 100, 2);
				$voteArr['questions'][$i]['options'][$j]['is_chosen'] = in_array($vote['questions'][$i]['options'][$j]->id, $ticketAns->toArray()) ? 1 : 0;
				unset($option['answers']);
			}
		}

		return JsonData($voteArr);
	}

	/**
	 * @param $answers
	 */
	private function checkIfNoRepeatingOptions($answers)
	{
		$answers = $answers->toArray();
		$origin = $answers;
		$answers = array_unique($answers);
		if (count($origin) == count($answers)) { //答案中没有重复： If two arrays have the same number of values, this means that there is no repetition within answers.
			return true;
		} else { //答案中有重复: 如果两个数组的有不同数量的元素，说明array_unique()函数压缩了一些元素，也就是证明答案中有重复。
			return false;
		}
	}

	/**
	 * @param $answers
	 * @param $vote
	 */
	private function checkIfAllFilled($answers, $vote)
	{
		$filled = $answers->map(function ($answer) {
			return Option::Id($answer)->question->id;
		})->unique();// Get all filled questions
		$required = collect($vote->questions->where('optional', 0)->map(function ($question) {
			return $question->id;
		}));
		if ($required->diff($filled)->isEmpty()) {
			return true;
		}

		return false;
	}

	/**
	 * @param $answers
	 * @param $vote
	 */

	private function checkIfOptionsFilledMatch($answers, $vote)
	{
		$optionsFilled = array_count_values($answers->map(function ($answer) {
			return Option::Id($answer)->question->id;
		})->flatten()->toArray());
//		$vote->questions->each(function ($question) use ($optionsFilled) {
//			if ($optionsFilled[$question->id] != $question->range) {
//				return false;
//			} // illegal answers :( # of options for a specific question is not match
//		}); 垃圾玩意出了啥毛病
		foreach ($vote->questions as $question) {
			if ($optionsFilled[$question->id] > $question->range) {
				return false;
			} // illegal answers :( # of options for a specific question is not match
		}

		return true;
	}
}
