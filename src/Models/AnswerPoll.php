<?php

namespace Condoedge\Surveys\Models;

use Kompo\Auth\Models\Model;
use App\Models\Surveys\Choice;

class AnswerPoll extends Model
{
	use \Condoedge\Surveys\Models\BelongsToPollTrait;

	/* RELATIONS */

	/* SCOPES */

	/* CALCULATED FIELDS */
	public function getAnswerTextAsArray()
	{
		if ((substr($this->answer_text, 0, 2) == '["') && (substr($this->answer_text, 0, -2) == '"]')) {
			return json_decode($this->answer_text, true);
		}

		return [$this->answer_text];
	}

	public function getChoicesCost()
	{
		return Choice::whereIn('id', $this->getAnswerTextAsArray())->sum('choice_amount');
	}

	/* ACTIONS */
    public static function createOrGetAnswerPoll($answerId, $pollId)
    {
    	$ap = static::onlyGetAnswerPoll($answerId, $pollId);

    	if (!$ap) {
	        $ap = new AnswerPoll();
	        $ap->poll_id = $pollId;
	        $ap->answer_id = $answerId;
	    }

        $ap->save();

	    return $ap;
    }

    public static function onlyGetAnswerPoll($answerId, $pollId)
    {
    	return AnswerPoll::forPoll($pollId)
    		->where('answer_id', $answerId)
    		->first();
    }

    public function delete()
    {
        parent::delete();
    }

	/* ELEMENTS */
}
