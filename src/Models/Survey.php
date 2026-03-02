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

	public static function boot()
	{
		parent::boot();

		static::creating(function ($model) {
			if (!in_array(SurveyableContract::class, class_implements($model->surveyable))) {
				throw new \Exception('SurveyableContract not implemented in the surveyable model.');

				\Log::error('SurveyableContract not implemented in the surveyable model.', [
					'surveyable' => $model->surveyable,
					'model' => get_class($model),
				]);
			}
		});
	}

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

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\MorphTo<SurveyableContract>
	 */
	public function surveyable()
	{
		return $this->morphTo();
	}

    /* SCOPES */

	/* CALCULATED FIELDS */
	public function hasChoicesWithAmounts()
	{
		return $this->memoize('hasChoicesWithAmounts', fn() =>
			$this->getOrderedPolls()->contains(fn($poll) =>
				$poll->choices->contains(fn($c) => $c->choice_amount !== null)
			)
		);
	}

	public function getOrderedPolls() //Only base Polls are linked to PollSections
	{
		return $this->memoize('orderedPolls', fn() =>
			$this->pollSections()->with(['polls.choices', 'polls.condition', 'polls.pollProducts'])->get()->flatMap(fn($ps) => $ps->polls)
		);
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

	public function hasAmountsAssociated()
	{
		return true;
	}

	public function hasMaxQuantitiesAssociated()
	{
		return true;
	}

	public function hasAskQuestionOnce()
	{
		return true;
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

	public function delete()
	{
		$this->pollSections->each->delete();

		parent::delete();
	}

	/* ELEMENTS */
	public function getSurveyOptionsFields()
	{
		return $this->surveyable->getSurveyOptionsFields(_Rows(
			_Toggle()->name('one_page')->label('campaign.is-on-one-page')->default(1)->submit(),
		), $this);
	}

	public function getSurveyTopButtons()
	{
		return [
			_Button('campaign.visualize-form')->selfGet('visualizeAnswerSurveyModal')->inModal()->class('mb-4'),
		];
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
        )->class('max-w-xl')->style('width: 98vw');
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
