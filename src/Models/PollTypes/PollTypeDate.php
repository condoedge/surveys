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

    /* ACTIONS */
    public function validateSpecificToType($poll, $value)
    {
        if (\DateTime::createFromFormat('Y-m-d', $value) == false) 
        {
            throwValidationError($poll->getPollInputName(), 'error-translations.fill-field');
        }
    }
}
