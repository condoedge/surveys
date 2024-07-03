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
}
