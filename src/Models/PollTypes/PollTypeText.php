<?php

namespace Condoedge\Surveys\Models\PollTypes;

class PollTypeText extends BasePollType
{
    public const POLL_IS_A_FIELD = false;

	/* DISPLAY ELEMENTS */
	protected function mainInputEl($poll)
    {
        return _Html($poll->getPollTitle())->class('ckEditorContent');
    }

    protected function titleExplanationEls($poll)
    {
    	//Nothing
    }

	/* EDIT ELEMENTS */
    protected function getQuestionInfoEls($poll)
    {
        $pollTextEditor = function_exists('_PollTextEditor') ? _PollTextEditor() : _CKEditor();
        
    	return $pollTextEditor->name('body_po')->withoutHeight();
    }

    protected function getQuestionOptionsEls($poll)
    {
        return _HtmlField()->name('required_po')->value(0); //to force a 0 in required_po
    }
}
