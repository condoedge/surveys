<?php

namespace Condoedge\Surveys\Models\PollTypes;

use App\Models\Surveys\Condition;
use App\Models\Surveys\Choice;
use App\Models\Surveys\AnswerPoll;
use App\Models\Surveys\Answer;
use App\Models\Surveys\Poll;
use App\Models\Surveys\Survey;

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

        $pollPanelId = 'poll-wrapper-'.$poll->id;
        $eligibility = $mainPoll->survey?->managesEligibility();
        // With arrows, the whole path re-renders after each answer: no condition panel waits empty
        $isConditionalDisplay = !$eligibility && $displayMode == Poll::DISPLAY_MODE_INITIAL && $mainPoll->getTheCondition();

        if ($isConditionalDisplay) {
            return _Panel(

            )->id($pollPanelId);
        }

        return _Panel(
            $this->questionEls($poll, $displayMode, $answer, $multiPage)
        )->id($pollPanelId);
    }

    /** The question's title and its field, which saves the answer as soon as it is given. */
    protected function questionEls($poll, $displayMode, $answer = null, $multiPage = false)
    {
        $inputEl = $this->mainInputEl($poll->getMainPoll());

        if (static::POLL_IS_A_FIELD && ($displayMode != Poll::DISPLAY_MODE_EDITING)) {
            $inputEl = $inputEl->name($poll->getPollInputName(), false)
                ->id($poll->getPollInputName().'-'.uniqid()); //added to avoid an unsolved bug in new setStableIdForElement

            if ($answer) {//Set value
                $ap = AnswerPoll::onlyGetAnswerPoll($answer->id, $poll->id);
                $inputEl = $inputEl->value($this->fieldValue($ap));
            }

            if (!$multiPage) {
                $inputEl = $this->savesWhenAnswered($inputEl, $poll);
            }
        }

        return _Rows(
            $this->titleExplanationEls($poll),
            $inputEl,
        );
    }

    protected function savesWhenAnswered($inputEl, $poll)
    {
        if ($poll->getMainPoll()->survey?->managesEligibility()) {
            // The path refresh saves the answer and is the confirmation: a toast would cover the button below
            return $inputEl->onChange(
                fn($e) => $e->selfPost('refreshSurveyPath', ['poll_id' => $poll->id])->withAllFormValues()->inPanel(Survey::PATH_PANEL)
            );
        }

        $defaultTrigger = static::DEFAULT_TRIGGER;

        $inputEl = $inputEl->{$defaultTrigger}(
            fn($e) => $e->selfPost('saveInlinePollAnswer', [
                'poll_id' => $poll->id,
            ])->withAllFormValues()->inPanel(Answer::SURVEY_COST_PANEL),
        );

        foreach ($poll->getDependentConditions() as $condition) {
            $inputEl = $inputEl->onChange(
                fn($e) => $e->selfPost('manageDisplayForCondition', [
                    'condition_id' => $condition->id,
                ])->inPanel('poll-wrapper-'.$condition->poll_id)
            );
        }

        //Last optional Action
        return $inputEl->alert('survey.answer-saved');
    }

    protected function mainInputEl($poll)
    {
        //OVERRIDE
    }

    protected function fieldValue($answerPoll)
    {
        return $answerPoll?->getAnswerTextForFieldValue();
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
            $poll->survey->managesEligibility() ? $this->getNextDefaultBox($poll) : $this->getConditionsBox($poll),
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
            _Toggle('campaign.hide-question')->name('hide_poll')->class('mb-2'),
            !$poll->survey->hasAskQuestionOnce() ? null : _Toggle('campaign.ask-question-once')->name('ask_question_once')->class('mb-2'),
        );
    }

    /** In a survey that manages eligibility, in place of the display condition: where to go after this question. */
    protected function getNextDefaultBox($poll)
    {
        return _Select($poll->branchesPerChoice() ? 'surveys-other-answers' : 'surveys-after-this-question')
            ->name('poll_next_default')->options($poll->nextTargetOptions())->placeholder('surveys-next-question')
            ->comment($poll->branchesPerChoice() ? 'surveys-other-answers-hint' : 'surveys-after-this-question-hint')
            ->class('mb-4');
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
                    // The answer may have been deleted since the condition was set: the field then opens empty.
                    ->value(Choice::find($condition?->condition_choice_id)?->id)
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
    /** Saved as soon as the person answers, from the question's field. */
    public function saveInline($poll, $answer)
    {
        $answer->saveAnswerToSinglePoll($poll->id);

        return $answer->getTotalAnswerCostEls();
    }

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

        if ($mainPoll->isAnswerRequired() && !$value) {
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

    /** What the answer keeps once the value is valid. */
    public function answerTextToSave($answerPoll, $value)
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
