<?php

namespace Condoedge\Surveys\Models\PollTypes;

class PollTypeDate extends BasePollType
{
	/* DISPLAY ELEMENTS */
	protected function mainInputEl($poll)
    {
        return _Date();
    }

	/* EDIT ELEMENTS */
}
