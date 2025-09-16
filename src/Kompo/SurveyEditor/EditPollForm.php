<?php

namespace Condoedge\Surveys\Kompo\SurveyEditor;

use App\Models\Surveys\Poll;
use App\Models\Surveys\Choice;
use App\Models\Surveys\Condition;
use Condoedge\Surveys\Kompo\Common\ModalScroll;
use Illuminate\Validation\Rule;

class EditPollForm extends ModalScroll
{
	public $model = Poll::class;

    public $class = 'card-white max-w-2xl overflow-y-auto mini-scroll';
    public $style = 'height: 95vh; width: 95vw;';
	
    public $_Title = 'campaign.add-new-poll';

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
        $this->model->process_urls = request('process_urls', false);

        $ps = $this->model->pollSection;
        if ($ps->isDoubleColumn() && ($ps->polls()->where('id', '<>', $this->model->id)->count() == 2)) {
            abort(403, __('Something is wrong, please refresh and try again.'));
        }
        if (!$ps->isDoubleColumn() && ($ps->polls()->where('id', '<>', $this->model->id)->count() == 1)) {
            abort(403, __('Something is wrong, please refresh and try again.'));
        }
    }

    public function afterSave()
    {
        $this->model->setPollConditionInForm(); 
    }

    public function response() 
    {
        return redirect($this->model->survey->getEditSurveyRoute());
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

    public function checkUrlsInText($text)
    {
        return $this->model->checkUrlsInTextEl($text);
    }

    public function rules()
    {
    	return [
            'choices.*.choice_amount' => [
                Rule::requiredIf(function () {
                    return request('choices_type_temp');
                }),
                'nullable', 'numeric'
            ],
            'choices.*.choice_max_quantity' => [
                Rule::requiredIf(function () {
                    return request('quantity_type_temp');
                }),
                'nullable',
                'numeric',
            ],
            'condition_poll_id' => 'required_unless:has_conditions,0|nullable',
            'condition_type' => 'required_unless:has_conditions,0|nullable',
            'condition_choice_id' => 'required_unless:has_conditions,0|nullable'
    	];
    }
}
