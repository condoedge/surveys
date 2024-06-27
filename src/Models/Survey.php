<?php

namespace Condoedge\Surveys\Models;

use Kompo\Auth\Models\Model;

class Survey extends Model
{
	use \Kompo\Auth\Models\Teams\BelongsToTeamTrait;
	use \Kompo\Auth\Models\Files\MorphManyFilesTrait;

	use \Condoedge\Crm\Models\HasQrCodeTrait;
	public const QRCODE_LENGTH = 8;
	public const QRCODE_COLUMN_NAME = 'qrcode_sv';

	protected $casts = [

	];

	public function save(array $options = [])
    {
        $this->setQrCodeIfEmpty();

        parent::save();
    }

	/* ABSTRACT */

	/* RELATIONS */

    /* SCOPES */

	/* ACTIONS */

	/* ELEMENTS */
}
