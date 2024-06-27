<?php

namespace Condoedge\Surveys\Models;

trait BelongsToSurveyTrait
{
    /* RELATIONS */
    public function survey()
    {
        return $this->belongsTo(config('condoedge-surveys.survey-model-namespace'));
    }

    /* CALCULATED FIELDS */
    public function getSurveyName()
    {
        return $this->survey->name_sv;
    }

    /* ACTIONS */

    /* SCOPES */
    public function scopeForEvent($query, $idOrIds)
    {
        scopeWhereBelongsTo($query, 'survey_id', $idOrIds);
    }

}
