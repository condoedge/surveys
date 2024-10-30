<?php

namespace Condoedge\Surveys\Kompo\SurveyEditor;

use App\Models\Surveys\PollSection;
use App\Models\Surveys\Survey;
use Kompo\Query;

class SurveyPollSectionsList extends Query
{
	public $id = 'polls-list';

    public $orderable;
    public $dragHandle = '.cursor-move';

    public $noItemsFound = '';
    public $perPage = 1000;

    protected $surveyId;
    protected $survey;
    protected $surveyStillEditable;

	public function created()
	{
		$this->surveyId = $this->prop('survey_id');
        $this->survey = Survey::findOrFail($this->surveyId);
        $this->surveyStillEditable = $this->survey->surveyStillEditable();

        $this->orderable = $this->surveyStillEditable ? 'order' : null;
	}

	public function query()
	{
		return PollSection::where('survey_id', $this->surveyId)->orderPs();
	}

    public function render($pollSection)
    {
        $content = $this->getPollEditEls($pollSection);

        if($pollSection->isDoubleColumn()) {
            $content = _Columns(
                $content,
                $this->getPollEditEls($pollSection, 1),
            );
        }

        return _Flex(
            !$this->surveyStillEditable ? null : _Html()->icon(_Svg('selector')->class('w-8 h-8 text-gray-400'))->class('cursor-move'),
            $content?->class('flex-1 mb-2'),
            !$this->surveyStillEditable ? null : _Delete()->byKey($pollSection)->class('pl-2 mb-4'),
        );
    }

    protected function getPollEditEls($pollSection, $position = 0)
    {
        $poll = $position == 0 ? $pollSection->getFirstPoll() : $pollSection->getLastPoll();

        if (!$poll) {
            return !$this->surveyStillEditable ? null : _CardGray200(
                _Html('campaign.empty-section')->class('uppercase'),
                _Html('campaign.add-a-type-of-question'),
            )->class('p-6 justify-center items-center')
            ->selfPost('getAddPollForm', [
                'id' => $pollSection->id,
                'position_po' => $position,
            ])->inPanel('pick-poll-type-panel');
        }

        return  _CardWhiteP4(
            _FlexBetween(
                $poll->getPreviewInputEls(),
                !$this->surveyStillEditable ? null : _FlexEnd2(
                    _Link()->icon(_Sax('edit',20)->class('text-gray-600'))
                        ->selfUpdate('getPollForm', [
                            'id' => $poll->id,
                        ])->inModal(),
                    _Delete()->byKey($poll),
                ),
            ),
            !$poll->hasConditions() ? null : 
                _Pill('campaign.display-condition')->class('absolute right-4 top-2 bg-warning text-white'),
        )->class('relative');
    }

    public function getPollForm($id)
    {
        return new EditPollForm($id);
    }

    public function getAddPollForm()
    {
        return new AddPollForm($this->surveyId, [
            'poll_section_id' => request('id'),
            'position_po' => request('position_po'),
        ]);
    }
}
