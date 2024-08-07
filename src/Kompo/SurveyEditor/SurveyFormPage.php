<?php

namespace Condoedge\Surveys\Kompo\SurveyEditor;

use App\Models\Surveys\Survey;
use Condoedge\Surveys\Models\Poll;
use Kompo\Form;

class SurveyFormPage extends Form
{
    public $model = Survey::class;

    public function authorizeBoot()
    {
        return $this->model->id && auth()->user()->can('update', $this->model); //This form only accepts an already created survey object
    }

    public function render()
    {
        return _Rows(
            _FlexEnd4(
                _Button('campaign.visualize-form')->selfGet('visualizeAnswerSurveyModal')->inModal()->class('mb-4'),
            ),
            _Columns(
                _CardWhiteP4(
                    _Html('campaign.type-of-question')->class('text-lg font-bold'),
                    _Html('campaign.click-on-a-type-to-add-it-to-your-form')->class('mb-4 text-sm'),
                    _Panel(
                        new AddPollForm($this->model->id),
                    )->class('rounded-xl mb-4')->id('pick-poll-type-panel'),
                    _Html('campaign.options')->class('text-lg font-bold'),
                    new EditSurveyForm($this->model->id),
                )->col('col-md-4'),

                _Rows(
                    new SurveyPollSectionsList([
                        'survey_id' => $this->model->id,
                    ]),
                    new AddSectionForm($this->model->id),
                )->col('col-md-8')
            )
        )->class('-mx-8');
    }

    public function visualizeAnswerSurveyModal()
    {
        return $this->model->getSurveyDemoInModal();
    }
}
