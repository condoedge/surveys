<?php

namespace Condoedge\Surveys\Models\PollTypes;

class PollTypeAcceptation extends BasePollType
{
    public const DEFAULT_TRIGGER = 'onChange';
    public const POLL_HAS_OPEN_ANSWER = false;

	/* DISPLAY ELEMENTS */
	protected function mainInputEl($poll)
    {
        return _Checkbox('campaign.i-accept');
    }

	/* EDIT ELEMENTS */
}
