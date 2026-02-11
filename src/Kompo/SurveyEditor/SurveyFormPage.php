<?php

namespace Condoedge\Surveys\Kompo\SurveyEditor;

use App\Models\Surveys\Survey;
use Condoedge\Utils\Kompo\HttpExceptions\GenericErrorView;
use Condoedge\Utils\Kompo\Common\Form;

class SurveyFormPage extends Form
{
    public $model = Survey::class;

    // If we use authorizeBoot it breaks the whole container instead of not showing it or showing an user-friendly error
    public function authorized()
    {
        return $this->model->id && auth()->user()->can('update', $this->model); //This form only accepts an already created survey object
    }

    public function render()
    {
        if (!$this->authorized()) {
            $komponent = GenericErrorView::boot([
                'principal_message' => __('error.forbidden-title'),
                'secondary_message' => __('error.forbidden-subtitle'),
            ]);

            return $komponent;
        }

        return _Rows(
            _FlexEnd4(
                $this->model->getSurveyTopButtons(),
            ),
            _Columns(
                _CardWhiteP4(
                    !$this->model->surveyStillEditable() ? null : _Rows(
                        _Html('campaign.type-of-question')->class('text-lg font-bold'),
                        _Html('campaign.click-on-a-type-to-add-it-to-your-form')->class('mb-4 text-sm'),
                        _Panel(
                            new AddPollForm($this->model->id),
                        )->class('rounded-xl mb-4')->id('pick-poll-type-panel'),
                    ),
                    _Html('campaign.options')->class('text-lg font-bold'),
                    new EditSurveyForm($this->model->id),
                )->col('col-md-4'),

                _Rows(
                    new SurveyPollSectionsList([
                        'survey_id' => $this->model->id,
                    ]),
                    !$this->model->surveyStillEditable() ? null : new AddSectionForm($this->model->id),
                )->col('col-md-8')
            )
        )->class('-mx-8');
    }

    public function visualizeAnswerSurveyModal()
    {
        return $this->model->getSurveyDemoInModal();
    }
}
