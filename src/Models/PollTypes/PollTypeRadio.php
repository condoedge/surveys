<?php

namespace Condoedge\Surveys\Models\PollTypes;

class PollTypeRadio extends BasePollTypeWithChoices
{
    public const DEFAULT_TRIGGER = 'onChange';

	/* DISPLAY ELEMENTS */
	protected function mainInputElWithoutOptions($poll)
    {
        return _LinkGroup()
            ->containerClass('space-y-2')
            ->optionClass('cursor-pointer radioChoice');
    }

	/* EDIT ELEMENTS */
}
