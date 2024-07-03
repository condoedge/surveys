<?php

namespace Condoedge\Surveys\Kompo\SurveyEditor;

use Condoedge\Surveys\Models\PollTypeEnum;
use App\Models\Surveys\Survey;
use Kompo\Form;

class AddPollForm extends Form
{
    protected $pollSectionId;
    protected $position;

    public $model = Survey::class;

    public function created() 
    {
        $this->pollSectionId = $this->prop('poll_section_id');
        $this->position = $this->prop('position');
    }

    public function render() 
    {
        return _Rows(
            PollTypeEnum::optionsWithLabels()->map(
                fn($labelEls, $type) => _Rows($labelEls)->class('cursor-pointer')
                    ->selfGet('addNewPoll', ['type' => $type])->inModal()
            )
        )->class('border-dashed border-2 border-gray-400 text-gray-700 p-4 rounded-2xl');
    }

    public function addNewPoll($type) 
    {
        return new EditPollForm([
            'type_po' => $type,
            'survey_id' => $this->model->id,
            'poll_section_id' => $this->pollSectionId,
            'position' => $this->position,
        ]);
    }
}
