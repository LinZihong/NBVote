<?php

namespace App\Http\Controllers;

use App\Answer;

use App\Events\UpdateModelIPAddress;
use App\OptionCache;
use Illuminate\Http\Response;
use App\VoteGroup;
use Illuminate\Http\Request;
use App\Ticket;
use App\Vote;
use App\Option;
use Illuminate\Support\Facades\Lang;


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
		$ticket = Ticket::where('string',$request->route()[2]['ticket'])->with('voteGroup.votes')->firstOrFail();
		foreach($ticket['voteGroup']['votes'] as $vote)
        {
            $vote['is_voted'] = $ticket->isTicketUsed($vote->id);
            $vote['times'] = count($vote->votedIds());
        }

		return JsonData($ticket);
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
        $vote['is_voted'] = Ticket::ticket($ticket)->isTicketUsed($id);
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
        if(!array_key_exists('selected', $request))
        {
            return JsonStatus('Invalid form', 401);
        }
		$answers = collect(json_decode($request['selected']));
		$vote = Vote::find($voteId);
		$voteIsValid = false;

		if (!$this->checkIfAllFilled($answers, $vote)) { //并且所有的选项填完了
			return JsonStatus('vote.option_left_not_filled', 401);
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
            if($vote->show_result == 0)
            {
                return JsonData(['result' => 'Voted Successfully', 'show_result' => 'false']);
            }
            else
            {
                return JsonData(['result' => 'Voted Successfully', 'show_result' => 'true']);//Negotiate frontend url rules
            }
		} else {
			return JsonStatus('vote.checksum_fail', 401);
		}
	}

	public function cacheOptions(Request $request)
    {
        $option = $request['option_id'];
        $status = $request['status'];
        $time = $request['time'];
        $ticket = $request->route()[2]['ticket'];
        if(empty($cached = OptionCache::where('option', $option)->where('ticket', $ticket)->first()))
        {
            OptionCache::create([
                'option' => $option,
                'status' => $status,
                'ticket' => $ticket,
                'update_time' => $time
            ]);
            return JsonData('Saved!');
        }
        else if($time > $cached->update_time)
        {
            $cached->update_time = $time;
            $cached->status = $status;
            $cached->save();
        }
        return JsonData('Cached!');
    }

    public function getCachedOptions(Request $request)
    {
        return JsonData(OptionCache::where('ticket', $request->route()[2]['ticket'])->get());
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
		foreach($vote['questions'] as $question)
        {
            foreach($question['options'] as $option)
            {
                $option['count'] = count($option->answers);
                $option['percent'] = round(($option->getTotalNumber()/$question->getTotalNumber())*100,2);
                unset($option['answers']);
            }
        }
        return JsonData($vote);
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
        foreach ($vote->questions as $question)
        {
            if ($optionsFilled[$question->id] != $question->range) {
				return false;
			} // illegal answers :( # of options for a specific question is not match
        }
		return true;
	}
}
