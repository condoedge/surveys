<?php

namespace Condoedge\Surveys\Models;

trait BelongsToPollTrait
{
    /* RELATIONS */
    public function poll()
    {
        return $this->belongsTo(config('condoedge-surveys.poll-model-namespace'));
    }

    /* CALCULATED FIELDS */

    /* ACTIONS */

    /* SCOPES */
    public function scopeForPoll($query, $idOrIds)
    {
        scopeWhereBelongsTo($query, 'poll_id', $idOrIds);
    }

}
