<?php

namespace Condoedge\Surveys\Models\PollTypes;

class PollTypeSelect extends BasePollTypeWithChoices
{
	/* DISPLAY ELEMENTS */
	protected function mainInputElWithoutOptions($poll)
    {
        return _LinkGroup()
            ->containerClass('space-y-2')
            ->optionClass('cursor-pointer radioChoice');
    }

	/* EDIT ELEMENTS */
}
