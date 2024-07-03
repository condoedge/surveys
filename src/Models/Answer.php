<?php

namespace Condoedge\Surveys\Models;

use Kompo\Auth\Models\Model;
use App\Models\Surveys\Poll;

class Answer extends Model
{
	use \Condoedge\Surveys\Models\BelongsToSurveyTrait;

    public const SURVEY_COST_PANEL = 'survey-cost-panel';

	/* RELATIONS */

	/* SCOPES */

	/* CALCULATED FIELDS */    
	public function getTotalAnswerCost()
    {
        return AnswerPoll::where('answer_id', $this->id)->with('poll')->get()
        	->sum(fn($ap) => $ap->poll->shouldDisplayPoll(Poll::DISPLAY_MODE_INITIAL, $this) ? $ap->getChoicesCost() : 0);
    }

	/* ACTIONS */
    public static function createOrGetAnswerFromKompoClass($kompoClass)
    {
    	$surveyId = $kompoClass->prop('survey_id');
    	$answerableId = $kompoClass->prop('answerable_id');
    	$answerableType = $kompoClass->prop('answerable_type');

    	$answer = Answer::forSurvey($surveyId)
    		->where('answerable_id', $answerableId)
    		->where('answerable_type', $answerableType)
    		->first();

    	if (!$answer) {
	        $answer = new Answer();
	        $answer->survey_id = $surveyId;
	        $answer->answerable_id = $answerableId;
	        $answer->answerable_type = $answerableType;
	        $answer->answerer_id = $kompoClass->prop('answerer_id');
	        $answer->answerer_type = $kompoClass->prop('answerer_type');
	        $answer->save();
	    }

	    return $answer;
    }

    public function delete()
    {
        parent::delete();
    }

	/* ELEMENTS */
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
        );
    }
}
