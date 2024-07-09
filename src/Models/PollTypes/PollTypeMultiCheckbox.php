<?php

namespace Condoedge\Surveys\Models\PollTypes;

class PollTypeMultiCheckbox extends BasePollTypeWithChoices
{
	/* DISPLAY ELEMENTS */
	protected function mainInputElWithoutOptions($poll)
    {
        return _MultiCheckbox()->optionLabelClass('reset-font-size');
    }

	/* EDIT ELEMENTS */

    /* ACTIONS */
    public function validateSpecificToType($poll, $value)
    {
        if ($value) {
        	foreach ($value as $subvalue) {
        		parent::validateSpecificToType($poll, $subvalue);
        	}
        }
    }
}
