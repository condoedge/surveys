<?php

namespace Condoedge\Surveys\Kompo\SurveyEditor;

use App\Models\Surveys\Choice;
use Condoedge\Surveys\Models\PollTypeEnum;
use Kompo\Form;

class ChoiceForm extends Form
{
	public $model = Choice::class;

    protected $withAmounts;
    protected $withQuantities;
    protected $canDelete;

    public function created()
    {
        $this->withAmounts = $this->prop('with_amounts');
        $this->withQuantities = $this->prop('with_quantities');
        $this->canDelete = !in_array($this->prop('type_po'), [
            PollTypeEnum::PO_TYPE_BINARY,
            PollTypeEnum::PO_TYPE_RATING,
        ]);
    }

    public function render()
    {
    	return [
            _Input()->name('choice_content')->class('mb-0'),
            _Input()->name('choice_amount')->class('mb-0')->tdClass('choice_amount_input'),
            _Input()->name('choice_max_quantity')->class('mb-0')->tdClass('choice_quantity_input'),
            $this->canDelete ? $this->deleteLinkChoice() : _Html(),
    	];
    }

    protected function deleteLinkChoice()
    {
        return $this->model->id ?

            _Link()->icon(_Sax('trash', 16)->class('text-gray-500 ml-1'))->selfDelete('deleteChoice', ['id' => $this->model->id])->emitDirect('deleted') :

            _Link()->icon(_Sax('trash', 16)->class('text-gray-500 ml-1'))->emitDirect('deleted');
    }

    public function rules()
    {
    	return array_merge([
            'choice_content' => 'required',
    	], []);
    }
}
