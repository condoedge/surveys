<?php

namespace Condoedge\Surveys\Models;

use App\Models\Surveys\Poll;

trait BelongsToPollTrait
{
    /* RELATIONS */
    public function poll()
    {
        return $this->belongsTo(Poll::class);
    }

    /* CALCULATED FIELDS */

    /* ACTIONS */

    /* SCOPES */
    public function scopeForPoll($query, $idOrIds)
    {
        scopeWhereBelongsTo($query, 'poll_id', $idOrIds);
    }

}
