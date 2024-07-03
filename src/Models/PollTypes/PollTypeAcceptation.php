<?php

namespace Condoedge\Surveys\Models\PollTypes;

class PollTypeAcceptation extends BasePollType
{
	/* DISPLAY ELEMENTS */
	protected function mainInputEl($poll)
    {
        return _Checkbox('campaign.i-accept');
    }

	/* EDIT ELEMENTS */
}
