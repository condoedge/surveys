<?php

namespace Condoedge\Surveys\Models;

use App\Models\Surveys\PollSection;
use App\Models\Surveys\Poll;
use App\Models\Teams\Team;

class Survey extends ModelBaseForSurveys
{
	public const SURVEY_ANSWER_PANELID = 'SURVEY_ANSWER_PANELID';

	protected $casts = [

	];

	/* RELATIONS */
	public function team()
    {
        return $this->belongsTo(Team::class);
    }

	public function pollSections()
	{
		return $this->hasMany(PollSection::class)->orderPs();
	}

	public function polls()
	{
		return $this->hasMany(Poll::class);
	}

	public function surveyable()
	{
		return $this->morphTo();
	}

    /* SCOPES */

	/* CALCULATED FIELDS */
	public function hasChoicesWithAmounts()
	{
		return Choice::forPoll($this->polls()->pluck('id'))->whereNotNull('choice_amount')->count();
	}

	public function getOrderedPolls()
	{
		return $this->pollSections()->with('polls')->get()->flatMap(fn($ps) => $ps->polls);
	}

	public function getVisibleOrderedPollsForAnswer($answer)
	{
		return $this->getOrderedPolls()->filter(fn($po) => $po->shouldDisplayPoll($answer));
	}

	public function getEditSurveyRoute()
	{
		return route('survey.edit', ['id' => $this->id]);
	}

	public function surveyStillEditable()
	{
		return true; //Override in your package with your own logic
	}

	/* ACTIONS */
	public function checkIfSurveyEditableOrAbort()
	{
		if (!$this->surveyStillEditable()) {
            abort(403, __('You cannot change the survey anymore'));
        }
	}

	public function createNextPollSection($type = null)
	{
		$lastPollSection = new PollSection();
		$lastPollSection->order = ($this->pollSections()->max('order') ?: 0) + 1;
		$lastPollSection->type_ps = $type ?: PollSection::PS_SINGLE_TYPE;
		$this->pollSections()->save($lastPollSection);

		return $lastPollSection;
	}

	/* ELEMENTS */
	public function getSurveyOptionsFields()
	{
		return _Rows(
			_Toggle()->name('one_page')->label('campaign.is-on-one-page')->submit(),
		);
	}

	public function getSurveyOptionsRules()
	{
		return [
		];
	}

	public function getSurveyAnsweredInModal($payload)
	{
		$onePageForm = config('condoedge-surveys.answer-one-page-form');
		$multiPageForm = config('condoedge-surveys.answer-multi-page-form');

		return _Rows(
            $this->one_page ? 
                new $onePageForm(null, $payload) : 
                new $multiPageForm(null, $payload),
        )->class('max-w-lg')->style('width: 95vw');
	}

	public function getSurveyDemoInModal()
	{
		return $this->getSurveyAnsweredInModal([
            'survey_id' => $this->id,
            'answerable_id' => auth()->id(),
            'answerable_type' => 'user',
            'answerer_id' => auth()->id(),
            'answerer_type' => 'user',
			
			'is_demo_mode' => 1,
        ]);
	}
}
