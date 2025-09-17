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
    public const POLLTYPE_IS_COMBO = false;

    public const DEFAULT_TRIGGER = 'onBlur';

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

                $defaultTrigger = static::DEFAULT_TRIGGER;

                $inputEl = $inputEl->{$defaultTrigger}(
                    fn($e) => $e->submit()->inPanel(Answer::SURVEY_COST_PANEL),
                );

                foreach ($poll->getDependentConditions() as $condition) {
                    $inputEl = $inputEl->onChange(
                        fn($e) => $e->selfPost('manageDisplayForCondition', [
                            'condition_id' => $condition->id,
                        ])->inPanel('poll-wrapper-'.$condition->poll_id)
                    );
                }

                //Last optional Action
                $inputEl = $inputEl->alert('survey.answer-saved');
            }
        }

        return _Panel(
            !$poll->shouldDisplayPoll($answer, $displayMode) ? null : _Rows(
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
            _Input('campaign.question')->name('body_po')->class('mb-2')
                ->onChange->selfGet('checkUrlsInText')->inPanel('urls-check-results'),

            _Panel(
                $poll->checkUrlsInTextEl($poll->body_po) ?? _Hidden()->name('process_urls'),
            )->id('urls-check-results')->class('mb-2'),
            
            _Input('campaign.question-sub1')->name('explanation_po')->class('mb-2'),
        )->class('mb-6');
    }

    protected function getQuestionOptionsEls($poll)
    {
        return _Rows(
            _Toggle('campaign.answer-required')->name('required_po')->class('mb-2'),
            !$poll->survey->hasAskQuestionOnce() ? null : _Toggle('campaign.ask-question-once')->name('ask_question_once')->class('mb-2'),
        );
    }

    protected function getConditionsBox($poll)
    {
        $condition = $poll->getTheCondition();

        return _Div(
            _Toggle('campaign.toggle-to-add-a-condition-for-display')
                ->name('has_conditions', false)->value($poll->hasConditions())
                ->toggleId('condition-inputs', !$poll->hasConditions())
                ->class('mb-2'),
            _Card(
                _Columns(
                    _Select('campaign.for-which-question')
                        ->name('condition_poll_id', false)
                        ->options($poll->getPreviousPollsWithChoices()->pluck('body_po', 'id'))
                        ->value($condition?->condition_poll_id)
                        ->class('whiteField mb-2')
                    ,
                    _Select('campaign.condition-question')
                        ->name('condition_type', false)
                        ->options(Condition::getConditionTypes())
                        ->value($condition?->condition_type)
                        ->class('whiteField mb-2')
                ),
                _Select('campaign.answer-for-the-condition')
                    ->name('condition_choice_id', false)
                    ->optionsFromField('condition_poll_id', 'searchConditionPollChoices', 'retrieveConditionPollChoice')
                    ->value($condition?->condition_choice_id)
                    ->class('whiteField mb-2')
            )->class('bg-gray-200 py-4 px-6 !mb-2')
            ->id('condition-inputs')
        );
    }

    protected function getChoicesInfoEls($poll)
    {
        //OVERRIDE
    }

    /* ACTIONS */
    public function validatePollAnswer($poll, $value, $answer)
    {
        if (!static::POLL_IS_A_FIELD) {
            return;
        }

        $this->validateIfRequired($poll, $value);

        $this->validateSpecificToType($poll, $value, $answer);
    }

    protected function validateIfRequired($poll, $value)
    {
        $mainPoll = $poll->getMainPoll();

        if ($mainPoll->required_po && !$value) {
            throwValidationError($poll->getPollInputName(), 'error.this-poll-is-required');
        }  
    }

    protected function validateSpecificToType($poll, $value, $answer)
    {
        //OVERRIDE         
    }

    public static function transformAnswer($poll, $value)
    {
        return $value;
    }

    public static function initializePollForCombo($surveyId, $pollSectionId = null, $position = null)
    {
        $poll = new Poll();
        $poll->survey_id = $surveyId;
        $poll->poll_section_id = $pollSectionId ?: $poll->survey->createNextPollSection()->id;
        $poll->position_po = $position ?: 0;

        return $poll;
    }
}
