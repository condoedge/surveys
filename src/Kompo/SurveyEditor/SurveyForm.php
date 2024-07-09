<?php

namespace Condoedge\Surveys\Kompo\SurveyEditor;

use App\Models\Surveys\Survey;
use Condoedge\Surveys\Kompo\Common\ModalScroll;

class SurveyForm extends ModalScroll
{
    public $model = Survey::class;

    protected $teamId;

    public function created()
    {
        $this->teamId = currentTeamId();
    }

    public function beforeSave()
    {
        $this->model->team_id = $this->teamId;
    }

    public function response()
    {
        return redirect($this->model->getEditSurveyRoute());
    }

    public function render()
    {
        return _Rows(
            _Card(
                _Input('Survey title')->name('name_sv'),
            ),
            _SubmitButton('save'),
        )->class('p-8');
    }

    public function rules()
    {
        return [
            'name_sv' => 'required',
        ];
    }
}
