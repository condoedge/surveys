<?php

namespace Condoedge\Surveys\Kompo\SurveyEditor;

use Condoedge\Surveys\Models\PollTypeEnum;
use App\Models\Surveys\Survey;
use Condoedge\Utils\Kompo\Common\Form;

class AddPollForm extends Form
{
    protected $pollSectionId;
    protected $positionPo;

    public $model = Survey::class;

    public function created() 
    {
        $this->pollSectionId = $this->prop('poll_section_id');
        $this->positionPo = $this->prop('position_po');
    }

    public function render() 
    {
        return _Cardwhite(
            PollTypeEnum::optionsWithLabels()->map(
                fn($labelEls, $type) => $this->getPollTypeCta($labelEls, $type)
            )
        );
    }

    protected function getPollTypeCta($labelEls, $type)
    {
        $els = _Rows($labelEls)->class('cursor-pointer');

        return PollTypeEnum::isPollsCombo($type) ? 
            $els->selfGet('createCombo', ['type' => $type])->redirect() : 
            $els->selfGet('addNewPoll', ['type' => $type])->inModal();
    }

    public function createCombo($type) 
    {
        $pollTypeClass = PollTypeEnum::getTypeClassFrom($type);

        $pollTypeClass::createPollsCombo($this->model->id, $this->pollSectionId, $this->positionPo);

        return redirect($this->model->getEditSurveyRoute());
    }

    public function addNewPoll($type) 
    {
        return new EditPollForm([
            'type_po' => $type,
            'survey_id' => $this->model->id,
            'poll_section_id' => $this->pollSectionId,
            'position_po' => $this->positionPo,
        ]);
    }
}
