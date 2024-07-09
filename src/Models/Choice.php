<?php

namespace Condoedge\Surveys\Models;

class Choice extends ModelBaseForSurveys
{
	use \Condoedge\Surveys\Models\BelongsToPollTrait;

	/* RELATIONS */

	/* SCOPES */

	/* CALCULATED FIELDS */

	/* ACTIONS */

	/* ELEMENTS */
	public function choiceLabelInHtml()
	{
		return '<div>'.
			'<div>'.$this->choice_content.'</div>'.
			'<div class="text-sm text-info">'.$this->choice_amount.'</div>'.
		'</div>';
	}
}
