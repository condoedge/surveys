<?php

namespace Condoedge\Surveys\Kompo\SurveyEditor;

use App\Models\Surveys\Poll;
use Kompo\Auth\Common\ModalScroll;

class EditPollForm extends ModalScroll
{
	public $model = Poll::class;

	public $_Title = 'add-new-poll';

    public function created()
    {
        $this->model->survey_id = $this->model->survey_id ?: $this->prop('survey_id');
        $this->model->type_po = $this->model->type_po ?: $this->prop('type_po');
    }

    public function beforeSave()
    {
        $this->model->poll_section_id = $this->model->survey->createNextPollSection()->id;

    }

    public function afterSave() 
    {

    }

    public function headerButtons()
    {
        return _SubmitButton('campaign.save')->closeModal()->refresh('survey-form-page');
    }

    public function body()
    {
        return _Rows(
            $this->model->getEditInputs(),
        );
    }

    protected function getPosition() {
        if($this->section === null) {
            $this->getPresetPosition();

            $poll_sections = PollSection::where('survey_id', $this->surveyId)->orderBy('order')->get();

            if($this->section === null) {
                $poll_section_id = PollSection::create(['survey_id' => $this->surveyId, 'order' => $poll_sections->max('order') + 1])->id;
            }

            $this->section = $poll_section_id;
            $this->position = 0;
        }
    }

    public function getChoices($value) {
        $poll = Poll::with('choices')->where('id', $value)->first();
        if($this->condition_poll_id != null && $value == null) {
            return Poll::with('choices')->where('id', $this->condition_poll_id)->first()->choices->pluck('content', 'id');
        } else {
            return $poll != null ? $poll->choices->pluck('content', 'id') : collect([]);
        }
    }

    public function rules()
    {
    	return [
            'choices.*.amount' => 'required_unless:choices_type_temp,0|nullable|numeric',
            'choices.*.quantity' => 'required_unless:quantity_type_temp,0|nullable|numeric',
            'condition_poll_id' => 'required_unless:has_conditions,0|nullable',
            'condition_type' => 'required_unless:has_conditions,0|nullable',
            'condition_choice_id' => 'required_unless:has_conditions,0|nullable'
    	];
    }

    public function deleteChoice($id)
    {
        Choice::findOrFail($id)->delete();
    }
}
