<?php

namespace Condoedge\Surveys\Kompo\SurveyEditor;

use App\Models\Surveys\Poll;
use App\Models\Surveys\Choice;
use App\Models\Surveys\Condition;
use Condoedge\Surveys\Kompo\Common\ModalScroll;

class EditPollForm extends ModalScroll
{
	public $model = Poll::class;

	public $_Title = 'add-new-poll';

    public function created()
    {
        $this->model->poll_section_id = $this->model->poll_section_id ?: $this->prop('poll_section_id');
        $this->model->survey_id = $this->model->survey_id ?: $this->prop('survey_id');

        if (!$this->model->survey) {
            $this->model->survey_id = $this->model->pollSection->survey_id;
        }

        $this->model->type_po = $this->model->type_po ?: $this->prop('type_po');
        $this->model->position_po = $this->model->position_po ?: $this->prop('position_po');

        $this->model->setDefaultOptions();
    }

    public function beforeSave()
    {
        $this->model->poll_section_id = $this->model->poll_section_id ?: $this->model->survey->createNextPollSection()->id;
        $this->model->position_po = $this->model->position_po ?: 0;
    }

    public function afterSave()
    {
        if(request('has_conditions')) {
            $cond = $this->model->getOrNewTheCondition();
            $cond->condition_poll_id = request('condition_poll_id');
            $cond->condition_choice_id = request('condition_choice_id');
            $cond->condition_type = request('condition_type');
            $cond->save();
        } else {
            $this->model->getTheCondition()?->delete();
        }
    }

    public function response() 
    {
        return redirect()->route('survey.edit', ['id' => $this->model->survey_id]);
    }

    public function headerButtons()
    {
        return _SubmitButton('campaign.save');
    }

    public function body()
    {
        return _Rows(
            $this->model->getEditInputs(),
        );
    }

    public function deleteChoice($id)
    {
        Choice::findOrFail($id)->delete();
    }

    public function searchConditionPollChoices($conditionPollId) 
    {
        $poll = Poll::find($conditionPollId);

        if (!$poll) {
            return [];
        }

        return $poll->choices()->pluck('choice_content', 'id');
    }

    public function retrieveConditionPollChoice($conditionChoiceId) 
    {
        $choice = Choice::find($conditionChoiceId);

        return [
            $choice->id => $choice->choice_content,
        ];
    }

    public function rules()
    {
    	return [
            'choices.*.choice_amount' => 'required_unless:choices_type_temp,0|nullable|numeric',
            'choices.*.choice_max_quantity' => 'required_unless:quantity_type_temp,0|nullable|numeric',
            'condition_poll_id' => 'required_unless:has_conditions,0|nullable',
            'condition_type' => 'required_unless:has_conditions,0|nullable',
            'condition_choice_id' => 'required_unless:has_conditions,0|nullable'
    	];
    }
}