<?php

namespace Condoedge\Surveys\Models;

use App\Models\Surveys\PollSection;
use App\Models\Surveys\Poll;
use App\Models\Surveys\AnswerPoll;
use App\Models\Teams\Team;

class Survey extends ModelBaseForSurveys
{
	public const SURVEY_ANSWER_PANELID = 'SURVEY_ANSWER_PANELID';
	public const PATH_PANEL = 'survey-path-panel';

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

	/* BRANCHING */
	/** An answer's arrow can lead to one of these ends instead of a question. */
	public const END_YES = 'yes';
	public const END_NO = 'no';
	public const END_WARN = 'warn';
	public const ENDS = [self::END_YES => 1, self::END_NO => 2, self::END_WARN => 3];

	public static function isEnd($target)
	{
		return isset(static::ENDS[$target]);
	}

	/** Set once any answer or question has an arrow: the arrows then replace the display conditions. */
	public function usesBranching()
	{
		return $this->memoize('usesBranching', fn() => $this->getOrderedPolls()->contains(fn($poll) =>
			$poll->poll_next_default || $poll->choices->contains(fn($c) => $c->choice_next)
		));
	}

	/** Without any arrow to an end, going through every question ends nowhere: a plain form with jumps. */
	public function usesEnds()
	{
		return $this->memoize('usesEnds', fn() => $this->getOrderedPolls()->contains(fn($poll) =>
			static::isEnd($poll->poll_next_default) || $poll->choices->contains(fn($c) => static::isEnd($c->choice_next))
		));
	}

	/**
	 * The questions a person goes through, following each answer's arrow, and the end reached (null while unanswered).
	 * $concrete gives the poll actually answered for a question (a course's own copy), or null to skip it.
	 */
	public function walk($answer, ?callable $concrete = null, string $cacheKey = 'main')
	{
		return $this->memoize('walk:'.($answer?->id ?? 'none').':'.$cacheKey, fn() => $this->walkThrough($answer, $concrete ?? fn($poll) => $poll));
	}

	/** Answers just saved: what was read before (answers, walks, displayed questions) is read again. */
	public static function forgetAnswerReads()
	{
		foreach ([static::class, Poll::class, AnswerPoll::class, \App\Models\Surveys\Answer::class, \App\Models\Surveys\Choice::class] as $class) {
			$class::forgetAllMemos();
		}
	}

	protected function walkThrough($answer, callable $concrete)
	{
		$polls = $this->getOrderedPolls()->values();
		$seen = collect();
		$i = 0;

		while ($i < $polls->count()) {
			$main = $polls[$i];
			$poll = $main->hide_poll ? null : $concrete($main);

			if (!$poll) {
				$i++;
				continue;
			}

			$seen->push($poll->id);
			$target = $main->poll_next_default ?: 'next';
			$message = null;

			if (!$main->hasNoAnswer()) {
				$ap = $answer ? AnswerPoll::onlyGetAnswerPoll($answer->id, $poll->id) : null;

				if (!$ap || blank($ap->answer_text)) {
					return ['polls' => $seen, 'end' => null, 'message' => null];
				}

				$choice = $main->branchesPerChoice() ? $main->choices->firstWhere('id', $ap->getChoiceIdsAsArray()[0] ?? $ap->answer_text) : null;

				if ($choice?->choice_next) {
					$target = $choice->choice_next;
					$message = $choice->choice_result_message;
				}
			}

			if (static::isEnd($target)) {
				return ['polls' => $seen, 'end' => $target, 'message' => $message];
			}

			// Arrows only point forward: one pointing back or to a deleted question goes on to the next
			$jump = $target === 'next' ? false : $polls->search(fn($p) => $p->id == $target);
			$i = $jump !== false && $jump > $i ? $jump : $i + 1;
		}

		// Through every question without an end: eligible, unless there was no question to go through
		return ['polls' => $seen, 'end' => $this->usesEnds() && $seen->isNotEmpty() ? static::END_YES : null, 'message' => null];
	}

	/** Where an answer's arrow points in the builder: "next" read as the following question, or the end of the form. */
	public function resolveTarget($poll, $target)
	{
		if ($target && $target !== 'next') {
			return $target;
		}

		$polls = $this->getOrderedPolls()->values();
		$i = $polls->search(fn($p) => $p->id == $poll->id);

		return $polls[$i + 1]->id ?? ($this->usesEnds() ? static::END_YES : null);
	}

	/** "Q3", by the question's place in the form. */
	public function pollNumber($pollId)
	{
		$i = $this->getOrderedPolls()->values()->search(fn($p) => $p->id == $pollId);

		return $i === false ? '' : 'Q'.($i + 1);
	}

	/** For each question or end, the answers that lead to it: the builder's « Arrive de ». */
	public function incomingArrows()
	{
		return $this->memoize('incomingArrows', function () {
			$incoming = [];

			foreach ($this->getOrderedPolls() as $poll) {
				foreach ($poll->exitArrows() as [$label, $target]) {
					$incoming[$this->resolveTarget($poll, $target)][] = [$poll, $label];
				}
			}

			return $incoming;
		});
	}

	public function endMessage($end, $ownMessage = null)
	{
		return $ownMessage ?: ($this->{'end_'.$end.'_message'} ?: __('surveys-end-'.$end.'-default'));
	}

	/* BRANCHING ELEMENTS */
	/** Where the person's answers lead: eligible, not eligible, or to be checked with the contact. */
	public function endCardEls($end, $ownMessage = null)
	{
		$colors = [static::END_YES => 'bg-positive bg-opacity-10', static::END_NO => 'bg-danger bg-opacity-10', static::END_WARN => 'bg-warning bg-opacity-20'];

		return _Rows(
			_Html(__('surveys-result-'.$end))->class('font-semibold'),
			_Html(e($this->endMessage($end, $ownMessage))),
			$end != static::END_WARN || !$this->end_contact ? null : _Html(e($this->end_contact))->class('font-medium'),
		)->class('rounded-2xl p-4 gap-1 my-4 '.$colors[$end]);
	}

	/** What the arrows leave broken: a question no answer leads to, or no way to reach "eligible". */
	public function branchingIssues()
	{
		$incoming = $this->incomingArrows();
		$issues = $this->getOrderedPolls()->slice(1)->reject(fn($poll) => isset($incoming[$poll->id]))
			->map(fn($poll) => __('surveys-check-never-asked', ['q' => $this->pollNumber($poll->id)]))->values()->all();

		if ($this->usesEnds() && !isset($incoming[static::END_YES])) {
			$issues[] = __('surveys-check-no-yes');
		}

		return $issues;
	}

	/** Every path at a glance: questions in columns by how deep they sit, each with where its answers go, the ends last. */
	public function planEls()
	{
		$polls = $this->getOrderedPolls()->values();
		$depth = [$polls->first()?->id => 0];

		foreach ($polls as $poll) {
			foreach ($poll->exitArrows() as [, $target]) {
				$to = $this->resolveTarget($poll, $target);
				if ($to && !static::isEnd($to)) {
					$depth[$to] = max($depth[$to] ?? 0, ($depth[$poll->id] ?? 0) + 1);
				}
			}
		}

		$columns = $polls->groupBy(fn($poll) => $depth[$poll->id] ?? 0)->sortKeys();

		$ends = _Rows(
			...collect(static::ENDS)->keys()->map(fn($end) => $this->endCardEls($end)->class('w-52 !my-0'))->all(),
		)->class('gap-3');

		return _Flex(
			...$columns->map(fn($column) => _Rows(
				...$column->map(fn($poll) => _Rows(
					_Html($this->pollNumber($poll->id))->class('font-bold text-level3'),
					_Html(e(\Str::limit(strip_tags($poll->body_po), 60)))->class('text-sm mb-1'),
					...collect($poll->exitArrows())->map(fn($arrow) => _Html(e(\Str::limit(strip_tags($arrow[0]), 24)).' → '
						.$this->arrowTargetLabel($this->resolveTarget($poll, $arrow[1])))->class('text-xs text-gray-600'))->all(),
				)->class('bg-white border border-gray-200 rounded-xl p-3 w-52'))->all(),
			)->class('gap-3'))->values()->push($ends)->all(),
		)->class('gap-6 items-start');
	}

	/** "→ Q8", "→ Admissible": where an arrow lands, for the builder. */
	public function arrowTargetLabel($target)
	{
		return static::isEnd($target) ? __('surveys-result-'.$target) : ($target ? $this->pollNumber($target) : __('surveys-end-of-form'));
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
