<?php

namespace Condoedge\Surveys\Models;

use App\Models\Surveys\AnswerPoll;
use App\Models\Surveys\Poll;

/**
 * A survey that manages eligibility, turned on per survey: each answer's arrow leads to a later question
 * or to an end (eligible, not eligible, to be checked), and the arrows replace the display conditions.
 */
trait ManagesEligibility
{
	public const PATH_PANEL = 'survey-path-panel';

	/** An answer's arrow can lead to one of these ends instead of a question. */
	public const END_YES = 'yes';
	public const END_NO = 'no';
	public const END_WARN = 'warn';
	public const ENDS = [self::END_YES => 1, self::END_NO => 2, self::END_WARN => 3];

	public static function isEnd($target)
	{
		return isset(static::ENDS[$target]);
	}

	/** Turned on in the builder: the arrows then replace the display conditions. */
	public function managesEligibility()
	{
		return $this->manages_eligibility;
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
				$answered = $ap && !blank($ap->answer_text);

				// A required question waits for its answer; an optional one left blank follows "other answers"
				if (!$answered && $main->required_po) {
					return ['polls' => $seen, 'end' => null, 'message' => null];
				}

				$choice = $answered && $main->branchesPerChoice() ? $main->choices->firstWhere('id', $ap->getChoiceIdsAsArray()[0] ?? $ap->answer_text) : null;

				if ($choice?->choice_next) {
					$target = $choice->choice_next;
					$message = $choice->choice_result_message;
				}
			}

			if (static::isEnd($target)) {
				return ['polls' => $seen, 'end' => $target, 'message' => $message];
			}

			$i = $this->indexAfter($polls, $i, $target);
		}

		// Through every question without an end: eligible, unless there was no question to go through
		return ['polls' => $seen, 'end' => $this->usesEnds() && $seen->isNotEmpty() ? static::END_YES : null, 'message' => null];
	}

	/** Arrows only point forward: one pointing back or to a deleted question goes on to the next. */
	protected function indexAfter($polls, $i, $target)
	{
		$jump = $target && $target !== 'next' ? $polls->search(fn($p) => $p->id == $target) : false;

		return $jump !== false && $jump > $i ? $jump : $i + 1;
	}

	/** Where an answer's arrow lands in the builder, read as the walk reads it: a question, an end, or the end of the form. */
	public function resolveTarget($poll, $target)
	{
		if (static::isEnd($target)) {
			return $target;
		}

		$polls = $this->getOrderedPolls()->values();
		$i = $polls->search(fn($p) => $p->id == $poll->id);

		return $polls[$this->indexAfter($polls, $i, $target)]->id ?? ($this->usesEnds() ? static::END_YES : null);
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

	/* ELEMENTS */
	/** Where the person's answers lead: eligible, not eligible, or to be checked with the contact. */
	public function endCardEls($end, $ownMessage = null)
	{
		$colors = [static::END_YES => 'bg-positive bg-opacity-10', static::END_NO => 'bg-danger bg-opacity-10', static::END_WARN => 'bg-warning bg-opacity-20'];

		return _Rows(
			_Html(__('surveys-result-'.$end))->class('font-semibold'),
			_Html(e($this->endMessage($end, $ownMessage))),
			$end != static::END_WARN || !$this->end_contact ? null : _Html(e($this->end_contact))->class('font-medium'),
		)->class('rounded-2xl p-4 gap-1 my-4 '.$colors[$end])->style('overflow-wrap: anywhere');
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

	/** Every path at a glance: questions in columns by how deep they sit, each with where its answers go, the ends below. */
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

		$ends = _Flex(
			...collect(static::ENDS)->keys()->map(fn($end) => $this->endCardEls($end)->class('w-52 !my-0'))->all(),
		)->class('gap-3 flex-wrap');

		return _Rows(_Flex(
			...$columns->map(fn($column) => _Rows(
				...$column->map(fn($poll) => _Rows(
					_Html($this->pollNumber($poll->id))->class('font-bold text-level3'),
					_Html(e(\Str::limit(strip_tags($poll->body_po), 60)))->class('text-sm mb-1'),
					...collect($poll->exitArrows())->map(fn($arrow) => _Html(e(\Str::limit(strip_tags($arrow[0]), 24)).' → '
						.$this->arrowTargetLabel($this->resolveTarget($poll, $arrow[1])))->class('text-xs text-gray-600'))->all(),
				)->class('bg-white border border-gray-200 rounded-xl p-3 w-52'))->all(),
			)->class('gap-3'))->values()->all(),
		)->class('gap-6 items-start flex-wrap'), $ends)->class('gap-6');
	}

	/** "→ Q8", "→ Admissible": where an arrow lands, for the builder. */
	public function arrowTargetLabel($target)
	{
		return static::isEnd($target) ? __('surveys-result-'.$target) : ($target ? $this->pollNumber($target) : __('surveys-end-of-form'));
	}
}
