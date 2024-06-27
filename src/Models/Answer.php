<?php

namespace Condoedge\Surveys\Models;

use Kompo\Auth\Models\Model;

class Answer extends Model
{
	use \Condoedge\Surveys\Models\BelongsToSurveyTrait;

	/* RELATIONS */

	/* SCOPES */

	/* CALCULATED FIELDS */

	/* ACTIONS */
    public function delete()
    {
        parent::delete();
    }

	/* ELEMENTS */
}
