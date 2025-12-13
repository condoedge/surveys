<?php

namespace Condoedge\Surveys\Models;

use App\Models\Surveys\Choice;
use App\Models\Surveys\Poll as AppPoll;
use App\Models\Surveys\Condition;
use App\Models\Surveys\PollSection;
use App\Models\Surveys\AnswerPoll;

class Poll extends ModelBaseForSurveys
{
	use \Condoedge\Surveys\Models\BelongsToSurveyTrait;

    use \Kompo\Database\HasTranslations;
    protected $translatable = [
        'body_po',
    ];
    protected $casts = [
        'type_po' => PollTypeEnum::class,
    ];

    public const CHOICES_TEXT = 1;
    public const CHOICES_AMOUNT = 2;
    public const CHOICES_QUANTITY = 3;

    public const DISPLAY_MODE_EDITING = 1;
    public const DISPLAY_MODE_INITIAL = 2;
    public const DISPLAY_MODE_CONDITION_PASSED = 3;

	/* RELATIONS */
    public function pollSection()
    {
        return $this->belongsTo(PollSection::class);
    }

    public function parentPoll()
    {
        return $this->belongsTo(AppPoll::class, 'poll_id');
    }

    public function subPolls()
    {
        return $this->hasMany(AppPoll::class, 'poll_id');
    }

    public function condition()
    {
        return $this->hasOne(Condition::class);
    }

    public function choices()
    {
        return $this->hasMany(Choice::class);
    }

    public function answerPolls()
    {
        return $this->hasMany(AnswerPoll::class);
    }

    public function pollable()
    {
        return $this->morphTo();
    }

	/* SCOPES */
    public function scopeOrderPo($query)
    {
        $query->orderByRaw('-`position_po` DESC');
    }

    public function scopeIsPositionFirst($query)
    {
        $query->where('position_po', '<>', 1);
    }

    public function scopeIsPositionLast($query)
    {
        $query->where('position_po', 1);
    }

	/* CALCULATED FIELDS */
    public function getPollInputName()
    {
        return 'poll_input_name_'.$this->id;
    }

    public function getPollAnswer()
    {
        $ptc = $this->getPollTypeClass();

        return $ptc::transformAnswer($this, request($this->getPollInputName()));
    }

    public function getPollTypeClass()
    {
        return $this->getMainPoll()->type_po->pollTypeClass();
    }

    public function isSubPoll()
    {
        return $this->poll_id;
    }

    public function getMainPoll()
    {
        return $this->isSubPoll() ? $this->parentPoll : $this;
    }

    public function getPollTitle()
    {
        $pollRequired = $this->required_po ? ('<span class="text-danger text-lg">*</span>') : '';

        $title = $this->process_urls ? $this->processTitleUrls($this->body_po) : $this->body_po;

        return $title . $pollRequired;
    }

    public function processTitleUrls($text)
    {
        return preg_replace('/https?:\/\/[^\s]+/i', '<a class="vlLink" href="$0" target="_blank">$0</a>', $text);
    }

    public function getPollRequiredPill()
    {
        return !$this->required_po ? null : _Pill('campaign.answer-required');
    }

    public function getPollExplanation()
    {
        return $this->getMainPoll()->explanation_po;
    }

    public function isOpenAnswer()
    {
        $ptc = $this->getPollTypeClass();
        return $ptc::POLL_HAS_OPEN_ANSWER;
    }

    public function hasNoAnswer()
    {
        $ptc = $this->getPollTypeClass();
        return !$ptc::POLL_IS_A_FIELD;
    }

    public function hasArrayAnswer()
    {
        $ptc = $this->getPollTypeClass();
        return $ptc::POLL_HAS_ARRAY_ANSWER;
    }

    public function hasChoicesAmounts()
    {
        return $this->choices()->whereNotNull('choice_amount')->count();
    }

    public function hasChoicesQuantities()
    {
        return $this->choices()->whereNotNull('choice_max_quantity')->count();
    }

    public function hasChoices()
    {
        return $this->choices()->count();
    }

    public function getTheCondition()
    {
        return $this->condition()->first();
    }

    public function hasConditions()
    {
        return $this->getTheCondition();
    }

    public function getDependentConditions()
    {
        return Condition::where('condition_poll_id', $this->id)->get();
    }

    public function getPreviousPollsWithChoices()
    {
        return $this->survey->pollSections()
            ->when($this->pollSection()->first()?->order,  //If null, we are appending a new pollSection
                fn($q) => $q->where('order', '<=', $this->pollSection()->first()?->order)
            )
            ->with('polls')->get()
            ->flatMap->polls
            ->filter(fn($poll) => $poll->hasChoices())
            ->reject(fn($poll) => $poll->id == $this->id)
            ->reject(fn($poll) => ($poll->poll_section_id == $this->poll_section_id) && ($poll->position_po == 1));
    }

    public function shouldDisplayPoll($answer = null, $displayMode = null)
    {
        if ($displayMode && ($displayMode != Poll::DISPLAY_MODE_INITIAL)) {
            return true;
        }

        if ($answer && ($condition = $this->getTheCondition())) {
            $ap = AnswerPoll::onlyGetAnswerPoll($answer->id, $condition->condition_poll_id);
            if ($condition->isFulfilled($ap?->getChoiceIdsAsArray())) {
                return true;
            } else {
                return false;
            }
        }

        return true;
    }

    public function deletable()
    {
        return $this->pollSection->deletable();
    }

	/* ACTIONS */
    public function delete()
    {
        $this->deleteSubPolls();

        $this->getTheCondition()?->delete();
        $this->getDependentConditions()->each->delete();
        $this->choices->each->delete();
        $this->answerPolls()->delete();

        parent::delete();
    }

    public function deleteSubPolls()
    {
        $this->subPolls->each->delete();
    }

    public function validateAnswer($value, $answer)
    {
        return $this->getPollTypeClass()->validatePollAnswer($this, $value, $answer);        
    }

    public function setDefaultOptions()
    {
        return $this->getPollTypeClass()->setDefaultOptionsForPollType($this);        
    }

    public function preloadDefaultChoice($content)
    {
        $choice = new Choice();
        $choice->choice_content = $content;
        $this->setRelation('choices', $this->choices->push($choice));      
    }

    public function getOrNewTheCondition()
    {
        $condition = $this->getTheCondition();

        if (!$condition) {
            $condition = new Condition();
            $condition->poll_id = $this->id;
        }

        return $condition;
    }

    public function setPollConditionInForm()
    {
        $this->setOrUnsetCondition(request('condition_poll_id'));
    }

    protected function setOrUnsetCondition($conditionPollId)
    {
        if(request('has_conditions')) {
            $cond = $this->getOrNewTheCondition();
            $cond->condition_poll_id = $conditionPollId;
            $cond->condition_choice_id = request('condition_choice_id');
            $cond->condition_type = request('condition_type');
            $cond->save();
        } else {
            $this->getTheCondition()?->delete();
        }
    }

	/* ELEMENTS */
    public function getDisplayPostConditionEls($answer = null)
    {
        return $this->getPollTypeClass()->getDisplayInputs($this, Poll::DISPLAY_MODE_CONDITION_PASSED, $answer);
    }

    public function getDisplayInputEls($answer = null, $multiPage = false)
    {
        return $this->getPollTypeClass()->getDisplayInputs($this, Poll::DISPLAY_MODE_INITIAL, $answer, $multiPage);
    }

    public function getPreviewInputEls()
    {
        return $this->getPollTypeClass()->getDisplayInputs($this, Poll::DISPLAY_MODE_EDITING);
    }

    public function getEditInputs()
    {
        return $this->getPollTypeClass()->getEditInputs($this);
    }

    public function getPollableBox()
    {
        //OVERRIDE
    }

    public function checkUrlsInTextEl($text)
    {
        $hasUrls = $this->processTitleUrls($text) != $text;

        if ($hasUrls) {
            return _Card(
                _Html('⚠️')->class('text-2xl mr-2 text-[#E69500]'),
                _Rows(
                    _Html('campaign.urls-detected-in-question')->class('font-semibold mb-1 text-[#E69500]'),

                    _Html('campaign.you-can-enable-the-option-to-process-them-in-the-question-text-if-you-want-them-to-be-clickable-links')->class('text-sm'),

                    _FlexEnd(
                        _Toggle()->class('mt-4 !mb-0 [&>label>span]:!text-warning')
                            ->name('process_urls')->value(1)
                            ->dotColor('#fffad2ff')->checkColor('#FFC94D', '##CCCDD2'),
                    ),
                ),
            )->class('flex-row p-4 border bg-[#FFF4D7] border-[#FFC94D] text-[#4B4B4B] mb-1 mt-2');
        }

        return null;
    }

}
