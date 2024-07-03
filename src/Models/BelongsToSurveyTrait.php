<?php

namespace Condoedge\Surveys\Models;

use App\Models\Surveys\Survey;

trait BelongsToSurveyTrait
{
    /* RELATIONS */
    public function survey()
    {
        return $this->belongsTo(Survey::class);
    }

    /* CALCULATED FIELDS */
    public function getSurveyName()
    {
        return $this->survey->name_sv;
    }

    /* ACTIONS */

    /* SCOPES */
    public function scopeForSurvey($query, $idOrIds)
    {
        scopeWhereBelongsTo($query, 'survey_id', $idOrIds);
    }

}
