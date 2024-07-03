<?php

namespace Condoedge\Surveys\Models\PollTypes;

class PollTypeRating extends BasePollTypeWithChoices
{
	/* DISPLAY ELEMENTS */
	protected function mainInputElWithoutOptions($poll)
    {
        return _ButtonGroup()
            ->optionClass('px-4 py-2 text-center cursor-pointer')
            ->selectedClass('bg-level3 text-white font-medium', 'bg-gray-200 text-level3 font-medium');
    }

	/* EDIT ELEMENTS */

    /* OPTIONS CHOICES */
    public function setDefaultOptionsForPollType($poll)
    {
    	if (!$poll->choices()->count()) {
    		for ($i=1; $i <= 5; $i++) { 
    			$poll->preloadDefaultChoice($i);
    		}
    	}
    }
}
