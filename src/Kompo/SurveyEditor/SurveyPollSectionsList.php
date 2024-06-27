<?php

namespace Condoedge\Surveys\Kompo\SurveyEditor;

use App\Models\Surveys\PollSection;
use Kompo\Query;

class SurveyPollSectionsList extends Query
{
	public $orderable = 'order';
    public $dragHandle = '.cursor-move';

    public $noItemsFound = '';
    public $perPage = 1000;

    protected $surveyId;

	public function created()
	{
		$this->surveyId = $this->prop('survey_id');
	}

	public function query()
	{
		return PollSection::where('survey_id', $this->surveyId)->orderBy('order');
	}

    public function getSpaceContent($pollSection, $position) 
    {
        return _Html('SEEC');
    }

    public function render($pollSection)
    {
        if($pollSection->type == PollSection::PS_DOUBLE_TYPE) {
            $content = _Columns(
                _Div($this->getSpaceContent($pollSection, 0))->class('md:pr-2'),
                _Div($this->getSpaceContent($pollSection, 1))->class('md:pl-2'),
            )->noGutters()->class('mb-2 w-full mx-auto');
        } else {
            $content = $this->getSpaceContent($pollSection, 0)->class('mb-2');
        }

        return _Flex(
            _Html()->icon(_Svg('selector')->class('w-8 h-8 text-gray-400'))->class('cursor-move'),
            $content->class('flex-1'),
            _DeleteLink()->byKey($pollSection)->class('pl-2 mb-4'),
        );
    }

    public function getPollForm($id)
    {
    	return new PollEditModifyForm($id);
    }
}
