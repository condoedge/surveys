<?php

namespace Condoedge\Surveys\Models;

use App\Models\Surveys\Choice;
use App\Models\Surveys\Answer;

class AnswerPoll extends ModelBaseForSurveys
{
	use \Condoedge\Surveys\Models\BelongsToPollTrait;

	/* RELATIONS */
    public function answer()
    {
        return $this->belongsTo(Answer::class);
    }

	/* SCOPES */

	/* CALCULATED FIELDS */
	public function getChoiceIdsAsArray()
	{
		if ($this->is_open_answer) {
			return [];
		}

		if (is_array($this->answer_text)) {
			return $this->answer_text;
		}

		if (static::isJsonEncodedAnswer($this->answer_text)) {
			return json_decode($this->answer_text, true);
		}

		return [$this->answer_text];
	}

	public function getAnswerTextForFieldValue()
	{
		return static::isJsonEncodedAnswer($this->answer_text) ? json_decode($this->answer_text, true) : $this->answer_text;
	}

	public static function isJsonEncodedAnswer($answerText)
	{
		return $answerText && (substr($answerText, 0, 2) == '["') && (substr($answerText, -2) == '"]');
	}

	public function getChoices()
	{
		return Choice::whereIn('id', $this->getChoiceIdsAsArray())->get();
	}

	public function getChoicesCost()
	{
		return $this->getChoices()->sum('choice_amount');
	}

	public function getAnswerDescriptionText()
	{
		return $this->is_open_answer ? $this->answer_text : 
			$this->getChoices()->map(fn($choice) => $choice->choice_content)->implode(', ');
	}


	/* ACTIONS */
    public static function createOrGetAnswerPoll($answerId, $pollId)
    {
    	$ap = static::onlyGetAnswerPoll($answerId, $pollId);

    	if (!$ap) {
	        $ap = new static();
	        $ap->poll_id = $pollId;
	        $ap->answer_id = $answerId;
	        $ap->is_open_answer = $ap->poll->isOpenAnswer() ? 1 : 0;
	    }

        $ap->save();

	    return $ap;
    }

    public static function onlyGetAnswerPoll($answerId, $pollId)
    {
    	return static::forPoll($pollId)
    		->where('answer_id', $answerId)
    		->first();
    }

    public function delete()
    {
        parent::delete();
    }

	/* ELEMENTS */
}
