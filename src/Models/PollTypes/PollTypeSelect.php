<?php

namespace Condoedge\Surveys\Models\PollTypes;

class PollTypeSelect extends BasePollTypeWithChoices
{
	/* DISPLAY ELEMENTS */
	protected function mainInputElWithoutOptions($poll)
    {
        return _Select();
    }

	/* EDIT ELEMENTS */
}
