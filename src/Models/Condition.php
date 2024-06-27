<?php

namespace Condoedge\Surveys\Models;

use Kompo\Auth\Models\Model;

class Condition extends Model
{
	use \Condoedge\Surveys\Models\BelongsToPollTrait;

    const TYPE_IS_EQUAL_TO = 1;
    const TYPE_IS_NOT_EQUAL_TO = 2;
    const TYPE_ONLY_IF_EQUAL_TO = 3;

	/* RELATIONS */

	/* SCOPES */

	/* CALCULATED FIELDS */
    public static function getConditionTypes() 
    {
        return collect([
            self::TYPE_IS_EQUAL_TO => __('campaign.is-equal-to'),
            self::TYPE_IS_NOT_EQUAL_TO => __('campaign.is-not-equal-to'),
            self::TYPE_ONLY_IF_EQUAL_TO => __('campaign.only-if-equal-to')
        ]);
    }

	/* ACTIONS */

	/* ELEMENTS */
}
