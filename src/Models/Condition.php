<?php

namespace Condoedge\Surveys\Models;

class Condition extends ModelBaseForSurveys
{
	use \Condoedge\Surveys\Models\BelongsToPollTrait;

    const TYPE_IS_EQUAL_TO = 1;
    const TYPE_IS_NOT_EQUAL_TO = 2;
    const TYPE_ONLY_IF_EQUAL_TO = 3;

	/* RELATIONS */
    public function conditionPoll()
    {
        return $this->belongsTo(Poll::class, 'condition_poll_id');
    }

	/* SCOPES */

	/* CALCULATED FIELDS */
    public static function getConditionTypes() 
    {
        return collect([
            self::TYPE_IS_EQUAL_TO => __('campaign.is-equal-to'),
            self::TYPE_IS_NOT_EQUAL_TO => __('campaign.is-not-equal-to'),
            self::TYPE_ONLY_IF_EQUAL_TO => __('campaign.only-if-equal-to')
        ]);
    }

    public function isFulfilled($answer) 
    {
        $answer = is_array($answer) ? $answer : [$answer];

        if (!count($answer) || is_null($answer[0])) {
            return false;
        }

        switch($this->condition_type) {
            case self::TYPE_IS_EQUAL_TO:
                return in_array($this->condition_choice_id, $answer);
            case self::TYPE_ONLY_IF_EQUAL_TO:
                return in_array($this->condition_choice_id, $answer) && count($answer) === 1;
            case self::TYPE_IS_NOT_EQUAL_TO:
                return !in_array($this->condition_choice_id, $answer);
            default:
                return true;
        }
    }

	/* ACTIONS */

	/* ELEMENTS */
}
