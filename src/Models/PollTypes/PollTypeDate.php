<?php

namespace Condoedge\Surveys\Models\PollTypes;

class PollTypeDate extends BasePollType
{
    public const DEFAULT_TRIGGER = 'onChange';

	/* DISPLAY ELEMENTS */
	protected function mainInputEl($poll)
    {
        return _Date(); 
    }

	/* EDIT ELEMENTS */

    /* ACTIONS */
    public function validateSpecificToType($poll, $value, $answer)
    {
        if ($value && (\DateTime::createFromFormat('Y-m-d', $value) == false)) 
        {
            throwValidationError($poll->getPollInputName(), 'error-translations.fill-field');
        }
    }
}
