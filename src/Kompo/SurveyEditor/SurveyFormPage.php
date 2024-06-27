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
        return $this->model->id; //This form only accepts an already created survey object
    }

    public function render()
    {
        return _Rows(
            _FlexEnd(
                _Toggle('campaign.show-before-transaction')
                    ->name('show_before_transaction')
                    ->class('m-0 mr-4')
                    ->selfPost('setShowBeforeTransaction'),
                _Button('campaign.visualize-form')->selfGet('AnswerSurveyModal')->inModal()
            )->class('mb-2 mr-8'),
            _Columns(
                _Div(
                    _Html('campaign.type-of-question')->class('text-lg font-bold'),
                    _Html('campaign.click-on-a-type-to-add-it-to-your-form')->class('mb-4'),
                    _Rows(
                        Poll::pickableTypes()->map(
                            fn($labelEls, $type) => _Rows($labelEls)->class('cursor-pointer')
                                                        ->selfGet('addNewPoll', ['type' => $type])->inModal()
                        )
                    )->class('rounded-xl mb-4'),
                    /*_Html('campaign.options')->class('text-lg font-bold'),
                    new EditForm(null, [
                        'survey_id' => $this->model->id,
                    ]),*/
                )->class('bg-white rounded-2xl p-6 mb-4')->col('col-md-4'),

                _Rows(
                    new SurveyPollSectionsList([
                        'survey_id' => $this->model->id,
                    ]),
                    /*new AddSection([
                        'survey_id' => $this->model->id
                    ]),*/
                )->col('col-md-8')
            )
        );
    }

    public function setShowBeforeTransaction()
    {
        $this->model->show_before_transaction = request('show_before_transaction');
        $this->model->save();
    }

    public function addNewPoll($type) 
    {
        return new EditPollForm([
            'type_po' => $type,
            'survey_id' => $this->model->id,
        ]);
    }
}
