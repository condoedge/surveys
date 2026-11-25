<?php

namespace Condoedge\Surveys\Models;

use App\Models\Surveys\Poll;
use App\Models\Surveys\AnswerPoll;

class Answer extends ModelBaseForSurveys
{
	use \Condoedge\Surveys\Models\BelongsToSurveyTrait;

    public const SURVEY_COST_PANEL = 'survey-cost-panel';

    /** Saved as the person answers, question by question, not by « Continue ». */
    public bool $savingInline = false;

	/* RELATIONS */
    public function answerPolls()
    {
        return $this->hasMany(AnswerPoll::class);
    }


	/* SCOPES */    
    public function scopeLockedAnswer($query)
    {
        $query->where('answer_locked', 1);
    }

	/* CALCULATED FIELDS */    
	public function getTotalAnswerCost()
    {
        return $this->memoize('totalAnswerCost', function () {
            if (method_exists(AnswerPoll::class, 'preloadedForAnswer')) {
                $answerPolls = AnswerPoll::preloadedForAnswer($this->id)
                    ?? AnswerPoll::where('answer_id', $this->id)->with('poll')->get();
            } else {
                $answerPolls = AnswerPoll::where('answer_id', $this->id)->with('poll')->get();
            }

            return $answerPolls->sum(fn($ap) => $ap->poll?->shouldDisplayPoll($this) ? $ap->getChoicesCost() : 0);
        });
    }

    /** Names the files this answer sent, in a question's ZIP. */
    public function getAnswererName()
    {
        return __('surveys-answer').' '.$this->id;
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

    public function saveAnswerToSinglePoll($pollId)
    {
        $poll = Poll::findOrFail($pollId);
        $pollAnswer = $poll->getPollAnswer();
        $poll->validateAnswer($pollAnswer, $this);

        $ap = AnswerPoll::createOrGetAnswerPoll($this->id, $pollId);
        $ap->answer_text = $poll->getPollTypeClass()->answerTextToSave($ap, $pollAnswer);
        $ap->save();

        return $ap;
    }

    public function delete()
    {
        $this->answerPolls()->get()->each->delete();

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
