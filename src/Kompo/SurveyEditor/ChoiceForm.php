<?php

namespace Condoedge\Surveys\Kompo\SurveyEditor;

use App\Models\Surveys\Choice;
use App\Models\Surveys\Poll;
use Condoedge\Surveys\Models\PollTypeEnum;
use Condoedge\Utils\Kompo\Common\Form;

class ChoiceForm extends Form
{
	public $model = Choice::class;

    protected $withAmounts;
    protected $withQuantities;
    protected $canDelete;
    protected $targets;

    public function created()
    {
        $this->withAmounts = $this->prop('with_amounts');
        $this->withQuantities = $this->prop('with_quantities');
        $poll = Poll::find($this->prop('poll_id')) ?: $this->newDefaultPoll();
        $this->targets = $poll->survey?->managesEligibility() && $poll->branchesPerChoice() ? $poll->nextTargetOptions() : null;

        $this->canDelete = !in_array($this->prop('type_po'), [
            PollTypeEnum::PO_TYPE_BINARY,
            PollTypeEnum::PO_TYPE_RATING,
        ]);
    }

    public function render()
    {
    	return [
            _Input()->name('choice_content')->class('mb-0')->style('min-width: 10rem'),
            _Input()->name('choice_amount')->class('mb-0')->tdClass('choice_amount_input'),
            _Input()->name('choice_max_quantity')->class('mb-0')->tdClass('choice_quantity_input'),
            ...(!$this->targets ? [] : [_Rows(
                _Select()->name('choice_next')->options($this->targets)->placeholder('surveys-as-other-answers')->class('mb-0')->style('min-width: 11rem; max-width: 15rem'),
                _Input()->name('choice_result_message')->placeholder('surveys-result-message-placeholder')->class('mb-0'),
            )->class('gap-1')]),
            $this->canDelete ? $this->deleteLinkChoice() : _Html(),
    	];
    }

    protected function deleteLinkChoice()
    {
        return $this->model->id ?

            _Link()->icon(_Sax('trash', 16)->class('text-gray-500 ml-1'))->selfDelete('deleteChoice', ['id' => $this->model->id])->emitDirect('deleted') :

            _Link()->icon(_Sax('trash', 16)->class('text-gray-500 ml-1'))->emitDirect('deleted');
    }

    protected function newDefaultPoll()
    {
        $poll = new Poll;
        $poll->survey_id = $this->prop('survey_id');
        $poll->poll_section_id = $this->prop('poll_section_id');
        $poll->type_po = $this->prop('type_po');
        return $poll;
    }

    public function rules()
    {
    	return array_merge([
            'choice_content' => 'required',
            'choice_next' => 'nullable|max:20',
            'choice_result_message' => 'nullable|max:500',
    	], []);
    }
}
