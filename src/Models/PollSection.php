<?php

namespace Condoedge\Surveys\Models;

use App\Models\Surveys\Poll;

class PollSection extends ModelBaseForSurveys
{
	use \Condoedge\Surveys\Models\BelongsToSurveyTrait;

    public const PS_SINGLE_TYPE = 1;
    public const PS_DOUBLE_TYPE = 2;

	/* RELATIONS */
    public function polls()
    {
        return $this->hasMany(Poll::class)->orderPo();
    }

	/* SCOPES */
    public function scopeOrderPs($query)
    {
        $query->orderByRaw('-`order` DESC');
    }

	/* CALCULATED FIELDS */
    public function getFirstPoll()
    {
        return $this->polls()->isPositionFirst()->first();
    }

    public function getLastPoll()
    {
        return $this->polls()->isPositionLast()->first();
    }

    public function isDoubleColumn()
    {
        return $this->type_ps == PollSection::PS_DOUBLE_TYPE;
    }

    public function deletable()
    {
        return $this->survey->team_id == currentTeamId();
    }

	/* ACTIONS */
    public function delete()
    {
        $this->polls->each->delete();

        parent::delete();
    }

	/* ELEMENTS */
}
