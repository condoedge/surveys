<?php

namespace Condoedge\Surveys\Models;

use Kompo\Auth\Models\Model;
use App\Models\Surveys\PollSection;

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

	/* RELATIONS */
	public function pollSections()
	{
		return $this->hasMany(PollSection::class)->orderPs();
	}

	public function polls()
	{
		return $this->hasMany(Poll::class);
	}

    /* SCOPES */

	/* CALCULATED FIELDS */
	public function hasChoicesWithAmounts()
	{
		return Choice::forPoll($this->polls()->pluck('id'))->whereNotNull('choice_amount')->count();
	}

	/* ACTIONS */
	public function createNextPollSection($type = null)
	{
		$lastPollSection = new PollSection();
		$lastPollSection->order = ($this->pollSections()->max('order') ?: 0) + 1;
		$lastPollSection->type_ps = $type ?: PollSection::PS_SINGLE_TYPE;
		$this->pollSections()->save($lastPollSection);

		return $lastPollSection;
	}

	/* ELEMENTS */
}
