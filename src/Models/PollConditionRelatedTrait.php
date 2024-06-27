<?php

namespace Condoedge\Surveys\Models;

trait PollConditionRelatedTrait
{
    /* RELATIONS */

    /* CALCULATED FIELDS */

    /* ACTIONS */

    /* ELEMENTS */
    public function getConditionsBox() 
    {
        return _Div(
            _Toggle('campaign.answer-required')
                ->name('required')->default(1)->class('mb-2'),
            _Toggle('campaign.toggle-to-add-a-condition-for-display')
                ->name('has_conditions', false)->value($this->conditions->count() > 0)
                ->run('() => { $(\'#condition-inputs\').toggle();  }'),
            /* TODO uncomment later 
                currentCampaign()->isMembership() ? null : _Toggle('campaign.ask-question-once')
                ->name('ask_question_once')->value($this->ask_question_once),*/
            _Rows(
                _Columns(
                    _Select('campaign.for-which-question')
                        ->name('condition_poll_id', false)
                        ->options(/*$this->getPreviousSectionsPolls()*/)
                        ->value($this->condition_poll_id)
                    ,
                    _Select('campaign.condition-question')
                        ->name('condition_type', false)
                        ->options(Condition::getConditionTypes())
                        ->value($this->condition_type)
                ),
                _Select('campaign.answer-for-the-condition')
                    ->name('condition_choice_id', false)
                    ->optionsFromField('condition_poll_id', 'getChoices')
                    ->value($this->condition_choice_id)
            )
            ->id('condition-inputs')
            ->class('p-4 card-gray-100 whiteField ' . ($this->conditions->count() == 0 ? ' hidden ' : ''))
        );
    }

    public function getPreviousSectionsPolls() 
    {
        $this->getPresetPosition();

        if($this->section == null) {
            $polls = Survey::find($this->model->survey_id)->polls;
        } else {
            $poll_section = PollSection::find($this->section);
            $poll_sections = Survey::find($this->model->survey_id)->poll_sections()->with('polls')->where('order', '<', $poll_section->order)->get();
            $polls = $poll_sections->pluck('polls')->flatten();

            if($this->position == 1) {
                $poll = Poll::where('poll_section_id', $this->section)->where('position', 0)->first();
                if($poll != null) {
                    $polls->push($poll);
                }
            }

        }

        return $polls->filter(fn ($p) => $p->hasChoices())->pluck('body', 'id');
    }

    protected function getPresetPosition() 
    {
        $poll_sections = PollSection::where('survey_id', $this->surveyId)->orderBy('order')->get();

        $poll_section_id = null;
        $poll_section_position = 0;

        if($this->model->poll_section_id != null) {
            $poll_section_id = $this->model->poll_section_id;
            $poll_section_position = $this->model->position;
        } else {
            // give selected position if available
            if(session('poll_section') !== null && session('position') !== null) {
                $poll_section = $poll_sections->find(session('poll_section'));
                if($poll_section != null && $poll_section->positionAvailable(session('position'))) {
                    $poll_section_id = $poll_section->id;
                    $poll_section_position = session('position');
                }
            }

            // try to find empty spot
            foreach($poll_sections as $poll_section) {
                if($poll_section_id === null) {
                    if($poll_section->positionAvailable(0)) {
                        $poll_section_id = $poll_section->id;
                        $poll_section_position = 0;
                    } elseif($poll_section->positionAvailable(1)) {
                        $poll_section_id = $poll_section->id;
                        $poll_section_position = 1;
                    }
                }
            }

        }

        $this->section = $poll_section_id;
        $this->position = $poll_section_position;
    }

}
