<?php

namespace Condoedge\Surveys\Models\PollTypes;

use App\Models\Surveys\Condition;
use App\Models\Surveys\AnswerPoll;
use App\Models\Surveys\Answer;
use App\Models\Surveys\Poll;

abstract class BasePollType
{
    public const POLL_IS_A_FIELD = true;

    /* OPTIONS CHOICES */
    public function setDefaultOptionsForPollType($poll)
    {
        //overriden
    }

	/* DISPLAY ELEMENTS */
	public function getDisplayInputs($poll, $displayMode, $answer = null)
    {
        $inputEl = $this->mainInputEl($poll)->class('mb-0');

        if (static::POLL_IS_A_FIELD && ($displayMode != Poll::DISPLAY_MODE_EDITING)) {
            $inputEl = $inputEl->name('poll_answer', false)
                ->selfPost('saveAnswerForPoll', [
                    'poll_id' => $poll->id,
                ])->inPanel(Answer::SURVEY_COST_PANEL);

            if ($answer) {//Set value
                $ap = AnswerPoll::onlyGetAnswerPoll($answer->id, $poll->id);
                $inputEl = $inputEl->value($ap?->answer_text);
            }

            foreach ($poll->getDependentConditions() as $condition) {
                $inputEl = $inputEl->onChange(
                    fn($e) => $e->selfPost('manageDisplayForCondition', [
                        'condition_id' => $condition->id,
                    ])->inPanel('poll-wrapper-'.$condition->poll_id)
                );
            }

            //Last optional Action
            $inputEl = $inputEl->alert('Answer saved!');
        }

        return _Panel(
            !$poll->shouldDisplayPoll($displayMode, $answer) ? null : _Rows(
                $this->titleExplanationEls($poll),
                $inputEl,
            ),
        )->id('poll-wrapper-'.$poll->id);
    }

    protected function mainInputEl($poll)
    {
        //OVERRIDE
    }

    protected function titleExplanationEls($poll)
    {
        return _Rows(
            _FlexBetween(
                _Html($poll->body)->class('font-semibold'),
                !$poll->explanation ? null :
                    _Link()->icon('question-mark-circle')->toggleId('explanation-div-'.$poll->id)
            )->class('mb-1'),
            !$poll->explanation ? null : 
                _Html($poll->explanation)->class('text-sm text-gray-400 mb-2')->id('explanation-div-' . $poll->id),
        );
    }

	/* EDIT ELEMENTS */
	public function getEditInputs($poll)
    {
        return _Rows(
            $this->getQuestionInfoEls($poll),
            $this->getQuestionOptionsEls($poll),
            $this->getConditionsBox($poll),
            $this->getChoicesInfoEls($poll),
        );
    }

    protected function getQuestionInfoEls($poll)
    {
    	return _Rows(
            _Input('campaign.question')->name('body'),
            _Input('campaign.question-sub1')->name('explanation'),
        );
    }

    protected function getQuestionOptionsEls($poll)
    {
        return _Rows(
            _Toggle('campaign.answer-required')->name('required')->default(1),
            /* TODO uncomment later 
                currentCampaign()->isMembership() ? null : _Toggle('campaign.ask-question-once')
                ->name('ask_question_once')->value($this->ask_question_once),
            _Toggle('campaign.show-before-transaction')
                ->name('show_before_transaction')
                ->class('m-0 mr-4')
                ->selfPost('setShowBeforeTransaction'),
            */
        );
    }

    protected function getConditionsBox($poll)
    {
        $condition = $poll->getTheCondition();

        return _Div(
            _Toggle('campaign.toggle-to-add-a-condition-for-display')
                ->name('has_conditions', false)->value($poll->hasConditions())
                ->toggleId('condition-inputs', !$poll->hasConditions()),
            _CardGray100P4(
                _Columns(
                    _Select('campaign.for-which-question')
                        ->name('condition_poll_id', false)
                        ->options($poll->getPreviousPollsWithChoices()->pluck('body', 'id'))
                        ->value($condition?->condition_poll_id)
                    ,
                    _Select('campaign.condition-question')
                        ->name('condition_type', false)
                        ->options(Condition::getConditionTypes())
                        ->value($condition?->condition_type)
                ),
                _Select('campaign.answer-for-the-condition')
                    ->name('condition_choice_id', false)
                    ->optionsFromField('condition_poll_id', 'searchConditionPollChoices', 'retrieveConditionPollChoice')
                    ->value($condition?->condition_choice_id)
            )
            ->id('condition-inputs')
        );
    }

    protected function getChoicesInfoEls($poll)
    {
        //OVERRIDE
    }
}
