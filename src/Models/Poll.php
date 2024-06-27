<?php

namespace Condoedge\Surveys\Models;

use Kompo\Auth\Models\Model;

class Poll extends Model
{
	use \Condoedge\Surveys\Models\BelongsToSurveyTrait;

    use \Kompo\Database\HasTranslations;
    protected $translatable = [
        'body',
    ];

    public const PO_TYPE_TEXT = 1;
    public const PO_TYPE_SELECT = 2;
    public const PO_TYPE_RADIO = 3;
    public const PO_TYPE_MULTICHECKBOX = 4;
    public const PO_TYPE_DATE = 5;
    public const PO_TYPE_BINARY = 6;
    public const PO_TYPE_INPUT = 7;
    public const PO_TYPE_ACCEPTATION = 8;
    public const PO_TYPE_RATING = 9;

    public const CHOICES_TEXT = 1;
    public const CHOICES_AMOUNT = 2;
    public const CHOICES_QUANTITY = 3;

    public const TEXT_SHORT = 1;
    public const TEXT_LONG = 2;
    public const TEXT_PHONE = 3;
    public const TEXT_EMAIL = 4;
    public const TEXT_ADDRESS = 5;

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
