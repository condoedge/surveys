<?php

namespace Condoedge\Surveys\Models\PollTypes;

class PollTypeAcceptation extends BasePollType
{
    public const DEFAULT_TRIGGER = 'onChange';

	/* DISPLAY ELEMENTS */
	protected function mainInputEl($poll)
    {
        return _Checkbox('campaign.i-accept');
    }

	/* EDIT ELEMENTS */
}
