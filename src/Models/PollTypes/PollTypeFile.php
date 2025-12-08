<?php

namespace Condoedge\Surveys\Models\PollTypes;

use Condoedge\Utils\Facades\FileModel;

class PollTypeFile extends BasePollType
{
    public const DEFAULT_TRIGGER = 'onChange';
    public const POLL_HAS_OPEN_ANSWER = true;
    public const POLL_IS_A_FIELD = true;

	/* DISPLAY ELEMENTS */
	protected function mainInputEl($poll)
    {
        return _File();
    }

    public static function transformAnswer($poll, $answerText)
    {
        return FileModel::uploadMultipleFiles([
            $answerText
        ])->first();
    }

	/* EDIT ELEMENTS */
}
