<?php

namespace Condoedge\Surveys\Models;

use App\Models\Surveys\Choice;

/** A question's arrows, in a survey that manages eligibility: where each answer leads, and where they come from. */
trait HasArrows
{
    public static function bootHasArrows()
    {
        // Arrows to a deleted question go on to the next one, as the walk already read them
        static::deleting(function ($poll) {
            if (!$poll->poll_id) {
                Choice::where('choice_next', (string) $poll->id)->update(['choice_next' => 'next']);
                static::where('poll_next_default', (string) $poll->id)->update(['poll_next_default' => null]);
            }
        });
    }

    /** One arrow per answer for the single-answer types; the others have one arrow, after the question. */
    public function branchesPerChoice()
    {
        return in_array($this->type_po, [PollTypeEnum::PO_TYPE_SELECT, PollTypeEnum::PO_TYPE_RADIO, PollTypeEnum::PO_TYPE_BINARY, PollTypeEnum::PO_TYPE_RATING]);
    }

    /** [label, target] for each arrow leaving this question; an answer without its own arrow follows "other answers". */
    public function exitArrows()
    {
        $default = $this->poll_next_default ?: 'next';

        if (!$this->branchesPerChoice()) {
            return [[__('surveys-after-this-question'), $default]];
        }

        $own = $this->choices->filter(fn($c) => $c->choice_next);

        return $own->map(fn($c) => [$c->choice_content, $c->choice_next])
            ->when($own->count() < $this->choices->count(), fn($arrows) => $arrows->push([__('surveys-other-answers'), $default]))
            ->values()->all();
    }

    /** What an arrow can point to: the following question, any later question, or an end. */
    public function nextTargetOptions($withOtherAnswers = false)
    {
        return collect($withOtherAnswers ? ['' => __('surveys-as-other-answers')] : [])
            ->put('next', __('surveys-next-question'))
            ->union($this->laterPolls()->mapWithKeys(fn($p) => [$p->id => $this->survey->pollNumber($p->id).' · '.\Str::limit(strip_tags($p->body_po), 50)]))
            ->union(collect(Survey::ENDS)->mapWithKeys(fn($n, $end) => [$end => __('surveys-end-'.$end)]));
    }

    /** The questions after this one; not saved yet, those of the sections after its own (none for a new section). */
    protected function laterPolls()
    {
        $polls = $this->survey->getOrderedPolls()->values();

        if ($this->id) {
            $i = $polls->search(fn($p) => $p->id == $this->id);

            return $i === false ? collect() : $polls->slice($i + 1);
        }

        $sectionIds = $this->survey->pollSections()->pluck('id');
        $i = $sectionIds->search($this->poll_section_id);

        return $i === false ? collect() : $polls->filter(fn($p) => $sectionIds->search($p->poll_section_id) > $i);
    }

    /** On the walk of this answer; a course's own copy of a question is checked by the app. */
    public function isOnPath($answer)
    {
        return $this->survey->walk($answer)['polls']->contains($this->id);
    }

	/* ELEMENTS */
    /** Under the question in the builder: the answers that lead here, then where each answer goes. */
    public function getArrowsSummaryEls()
    {
        $survey = $this->survey;
        $from = collect($survey->incomingArrows()[$this->id] ?? []);
        $isFirst = $survey->getOrderedPolls()->first()?->id == $this->id;
        $colors = [Survey::END_YES => 'bg-positive bg-opacity-20', Survey::END_NO => 'bg-danger bg-opacity-10 text-danger', Survey::END_WARN => 'bg-warning bg-opacity-20'];

        return _Rows(
            _Html($isFirst ? __('surveys-first-question') : ($from->isEmpty() ? __('surveys-never-reached')
                : __('surveys-arrives-from').' '.$from->map(fn($f) => $survey->pollNumber($f[0]->id).' · '.e(\Str::limit(strip_tags($f[1]), 30)))->join(', ')))
                ->class('text-xs '.(!$isFirst && $from->isEmpty() ? 'text-danger font-medium' : 'text-gray-600')),
            _Flex(
                collect($this->exitArrows())->map(function ($arrow) use ($survey, $colors) {
                    $target = $survey->resolveTarget($this, $arrow[1]);

                    return _Html(e(\Str::limit(strip_tags($arrow[0]), 30)).' → '.$survey->arrowTargetLabel($target))
                        ->class('text-xs font-medium rounded-lg px-2 py-1 '.($colors[$target] ?? 'bg-gray-100'));
                })->all()
            )->class('flex-wrap gap-1'),
        )->class('gap-1 mt-3');
    }
}
