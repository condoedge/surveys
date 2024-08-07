<?php

namespace Condoedge\Surveys\Models\PollTypes;

use App\Models\Surveys\Condition;
use App\Models\Surveys\AnswerPoll;
use App\Models\Surveys\Answer;
use App\Models\Surveys\Poll;

abstract class BasePollType
{
    public const POLL_IS_A_FIELD = true;
    public const POLL_HAS_OPEN_ANSWER = true;
    public const POLL_HAS_ARRAY_ANSWER = false;

    /* OPTIONS CHOICES */

    /* OPTIONS CHOICES */
    public function setDefaultOptionsForPollType($poll)
    {
        //overriden
    }

	/* DISPLAY ELEMENTS */
	public function getDisplayInputs($poll, $displayMode, $answer = null, $multiPage = false)
    {
        $mainPoll = $poll->getMainPoll();

        $inputEl = $this->mainInputEl($mainPoll);

        if (static::POLL_IS_A_FIELD && ($displayMode != Poll::DISPLAY_MODE_EDITING)) {
            $inputEl = $inputEl->name($poll->getPollInputName(), false);

            if ($answer) {//Set value
                $ap = AnswerPoll::onlyGetAnswerPoll($answer->id, $poll->id);
                $inputEl = $inputEl->value($ap?->getAnswerTextForFieldValue());
            }

            if (!$multiPage) {
                $inputEl = $inputEl->submit()->inPanel(Answer::SURVEY_COST_PANEL);

                foreach ($mainPoll->getDependentConditions() as $condition) {
                    $inputEl = $inputEl->onChange(
                        fn($e) => $e->selfPost('manageDisplayForCondition', [
                            'condition_id' => $condition->id,
                        ])->inPanel('poll-wrapper-'.$condition->poll_id)
                    );
                }

                //Last optional Action
                $inputEl = $inputEl->alert('Answer saved!');
            }
        }

        return _Panel(
            !$mainPoll->shouldDisplayPoll($answer, $displayMode) ? null : _Rows(
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
        $explanation = $poll->getPollExplanation();

        return _Rows(
            _FlexBetween(
                _Html($poll->getPollTitle())->class('font-semibold'),
                !$explanation ? null :
                    _Link()->icon('question-mark-circle')->toggleId('explanation-div-'.$poll->id)
            )->class('mb-1'),
            !$explanation ? null : 
                _Html($explanation)->class('text-sm text-gray-400 mb-2')->id('explanation-div-' . $poll->id),
        );
    }

	/* EDIT ELEMENTS */
	public function getEditInputs($poll)
    {
        return _Rows(
            $this->getQuestionInfoEls($poll),
            $this->getQuestionOptionsEls($poll),
            $poll->getPollableBox(),
            $this->getConditionsBox($poll),
            $this->getChoicesInfoEls($poll),
        );
    }

    protected function getQuestionInfoEls($poll)
    {
    	return _Rows(
            _Input('campaign.question')->name('body_po'),
            _Input('campaign.question-sub1')->name('explanation_po'),
        );
    }

    protected function getQuestionOptionsEls($poll)
    {
        return _Rows(
            _Toggle('campaign.answer-required')->name('required_po')->default(1),
            _Toggle('campaign.ask-question-once')->name('ask_question_once'),
        );
    }

    protected function getConditionsBox($poll)
    {
        $condition = $poll->getTheCondition();

        return _Div(
            _Toggle('campaign.toggle-to-add-a-condition-for-display')
                ->name('has_conditions', false)->value($poll->hasConditions())
                ->toggleId('condition-inputs', !$poll->hasConditions()),
            _CardWhiteP4(
                _Columns(
                    _Select('campaign.for-which-question')
                        ->name('condition_poll_id', false)
                        ->options($poll->getPreviousPollsWithChoices()->pluck('body_po', 'id'))
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
            )->class('border border-gray-100')
            ->id('condition-inputs')
        );
    }

    protected function getChoicesInfoEls($poll)
    {
        //OVERRIDE
    }

    /* ACTIONS */
    public function validatePollAnswer($poll, $value)
    {
        if (!static::POLL_IS_A_FIELD) {
            return;
        }

        $this->validateIfRequired($poll, $value);

        $this->validateSpecificToType($poll, $value);
    }

    protected function validateIfRequired($poll, $value)
    {
        $mainPoll = $poll->getMainPoll();

        if ($mainPoll->required_po && !$value) {
            throwValidationError($poll->getPollInputName(), 'This poll is required');
        }  
    }

    protected function validateSpecificToType($poll, $value)
    {
        //OVERRIDE         
    }
}
