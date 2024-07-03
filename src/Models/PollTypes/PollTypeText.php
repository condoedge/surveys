<?php

namespace Condoedge\Surveys\Models\PollTypes;

class PollTypeText extends BasePollType
{
    public const POLL_IS_A_FIELD = false;

	/* DISPLAY ELEMENTS */
	protected function mainInputEl($poll)
    {
        return _Html($poll->body)->class('ckEditorContent');
    }

    protected function titleExplanationEls($poll)
    {
    	//Nothing
    }

	/* EDIT ELEMENTS */
    protected function getQuestionInfoEls($poll)
    {
    	return _CKEditor()->name('body');
    }
}
