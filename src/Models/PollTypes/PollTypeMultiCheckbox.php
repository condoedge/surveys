<?php

namespace Condoedge\Surveys\Models\PollTypes;

class PollTypeMultiCheckbox extends BasePollTypeWithChoices
{
    public const POLL_HAS_ARRAY_ANSWER = true;

    public const DEFAULT_TRIGGER = 'onChange';

	/* DISPLAY ELEMENTS */
	protected function mainInputElWithoutOptions($poll)
    {
        return _MultiCheckbox()->optionLabelClass('reset-font-size');
    }

	/* EDIT ELEMENTS */

    /* ACTIONS */
    public function validateSpecificToType($poll, $value, $answer)
    {
        if ($value) {
        	foreach ($value as $subvalue) {
        		parent::validateSpecificToType($poll, $subvalue, $answer);
        	}
        }
    }
}
