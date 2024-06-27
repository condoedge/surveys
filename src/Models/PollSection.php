<?php

namespace Condoedge\Surveys\Models;

use Kompo\Auth\Models\Model;

class PollSection extends Model
{
	use \Condoedge\Surveys\Models\BelongsToSurveyTrait;

    public const PS_SINGLE_TYPE = 1;
    public const PS_DOUBLE_TYPE = 2;

	/* RELATIONS */
    public function polls()
    {
        return $this->hasMany(Poll::class)->orderBy('position');
    }

	/* SCOPES */

	/* CALCULATED FIELDS */

	/* ACTIONS */
    public function delete()
    {
        $this->polls->each->delete();

        parent::delete();
    }

	/* ELEMENTS */
}
