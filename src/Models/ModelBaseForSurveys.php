<?php

namespace Condoedge\Surveys\Models;

use Condoedge\Utils\Models\Model;
use Condoedge\Utils\Models\Traits\MemoizesResults;

class ModelBaseForSurveys extends Model
{
	use MemoizesResults;

	/** Empties this class's memo storage; a class that uses the trait again keeps a storage of its own. */
	public static function forgetAllMemos()
	{
		static::$_memoizedStatic = [];
	}
	/* RELATIONS */

	/* SCOPES */

	/* CALCULATED FIELDS */

	/* ACTIONS */

	/* ELEMENTS */
}
