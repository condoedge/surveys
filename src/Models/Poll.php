<?php

namespace Condoedge\Surveys\Models;

use App\Models\Surveys\Choice;
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

    public function condition()
    {
        return $this->hasOne(Condition::class);
    }

    public function choices()
    {
        return $this->hasMany(Choice::class);
    }

	/* SCOPES */
    public function scopeOrderPo($query)
    {
        $query->orderByRaw('-`position_po` DESC');
    }

	/* CALCULATED FIELDS */
    public function getPollInputName()
    {
        return 'poll_input_name_'.$this->id;
    }

    public function isOpenAnswer()
    {
        $ptc = $this->type_po->pollTypeClass();

        return $ptc::POLL_HAS_OPEN_ANSWER;
    }

    public function showChoicesAmounts()
    {
        return $this->choices()->whereNotNull('choice_amount')->count();
    }

    public function showChoicesQuantities()
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
            ->when($this->poll_section_id,  //If null, we are appending a new pollSection
                fn($q) => $q->where('order', '<=', $this->pollSection->order)
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

        parent::delete();
    }

    public function validateAnswer($value)
    {
        return $this->type_po->pollTypeClass()->validatePollAnswer($this, $value);        
    }

    public function setDefaultOptions()
    {
        return $this->type_po->pollTypeClass()->setDefaultOptionsForPollType($this);        
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

	/* ELEMENTS */
    public function getDisplayPostConditionEls($answer = null)
    {
        return $this->type_po->pollTypeClass()->getDisplayInputs($this, Poll::DISPLAY_MODE_CONDITION_PASSED, $answer);
    }

    public function getDisplayInputEls($answer = null, $multiPage = false)
    {
        return $this->type_po->pollTypeClass()->getDisplayInputs($this, Poll::DISPLAY_MODE_INITIAL, $answer, $multiPage);
    }

    public function getPreviewInputEls()
    {
        return $this->type_po->pollTypeClass()->getDisplayInputs($this, Poll::DISPLAY_MODE_EDITING);
    }

    public function getEditInputs()
    {
        return $this->type_po->pollTypeClass()->getEditInputs($this);
    }

}
