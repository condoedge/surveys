<?php

namespace Condoedge\Surveys\Models\PollTypes;

class PollTypeBinary extends BasePollTypeWithChoices
{
	/* DISPLAY ELEMENTS */
	protected function mainInputElWithoutOptions($poll)
    {
        return _LinkGroup()
            ->containerClass('flex space-x-4 mb-1')
            ->optionClass('cursor-pointer p-4 text-center border border-gray-200 rounded-2xl w-full')
            //->selectedStyle('background-color: ' . $this->survey?->campaign?->campaign_color . '!important;')
            ->selectedClass('bg-level1 font-bold text-white SURVEY_selected', 'bg-white text-level2');
    }

	/* EDIT ELEMENTS */
    protected function getChoicesMultiForm($poll)
    {
        return parent::getChoicesMultiForm($poll)->noAdding();
    }

    /* OPTIONS CHOICES */
    public function setDefaultOptionsForPollType($poll)
    {
        if (!$poll->choices()->count()) {
            $poll->preloadDefaultChoice(__('campaign.yes'));
            $poll->preloadDefaultChoice(__('campaign.no'));
        }
    }
}
