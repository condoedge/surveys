<?php

namespace Condoedge\Surveys\Models;

use Kompo\Auth\Models\Model;

class Choice extends Model
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
