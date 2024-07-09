<?php

namespace Condoedge\Surveys\Models;

use App\Models\Surveys\Poll;
use App\Models\Surveys\AnswerPoll;

class Answer extends ModelBaseForSurveys
{
	use \Condoedge\Surveys\Models\BelongsToSurveyTrait;

    public const SURVEY_COST_PANEL = 'survey-cost-panel';

	/* RELATIONS */
    public function answerPolls()
    {
        return $this->hasMany(AnswerPoll::class);
    }


	/* SCOPES */

	/* CALCULATED FIELDS */    
	public function getTotalAnswerCost()
    {
        return AnswerPoll::where('answer_id', $this->id)->with('poll')->get()
        	->sum(fn($ap) => $ap->poll->shouldDisplayPoll($this) ? $ap->getChoicesCost() : 0);
    }

    public static function answerPayloadColumns()
    {
        return [
            'survey_id',
            'answerable_id',
            'answerable_type',
            'answerer_id',
            'answerer_type',
        ];
    }

	/* ACTIONS */
    public static function createOrGetAnswerFromKompoClass($answerPayload)
    {
    	$answer = static::query();
        foreach (static::answerPayloadColumns() as $col) {
            $answer = $answer->where($col, $answerPayload[$col]);
        }
        $answer = $answer->first();

    	if (!$answer) {
	        $answer = new static();
            foreach (static::answerPayloadColumns() as $col) {
                $answer->{$col} = $answerPayload[$col];
            }
	        $answer->save();
	    }

	    return $answer;
    }

    public function saveAnswerToSinglePoll($pollId, $pollAnswer)
    {
        $poll = Poll::findOrFail($pollId);
        $poll->validateAnswer($pollAnswer);

        $ap = AnswerPoll::createOrGetAnswerPoll($this->id, $pollId);
        $ap->answer_text = $pollAnswer;
        $ap->save();

        return $ap;
    }

    public function delete()
    {
        $this->answerPolls()->delete();

        parent::delete();
    }

	/* ELEMENTS */
	public function getAnswererNameEls()
    {
        //Override
    }

    public function getTotalAnswerCostPanel()
    {
        return !$this->survey->hasChoicesWithAmounts() ? _Html() : 
            _Panel(
                $this->getTotalAnswerCostEls()
            )->id(static::SURVEY_COST_PANEL);
    }

	public function getTotalAnswerCostEls()
    {
        return _FlexBetween(
        	_Html('Total'),
        	_Currency($this->getTotalAnswerCost()),
        )->class('font-bold text-2xl my-8');
    }
}
