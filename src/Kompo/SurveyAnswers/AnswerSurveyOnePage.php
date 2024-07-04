<?php

namespace Condoedge\Surveys\Kompo\SurveyAnswers;

use App\Models\Surveys\Answer;
use Kompo\Form;

class AnswerSurveyOnePage extends Form
{
    protected $survey;
    protected $answerable;

    public $model = Answer::class;

    public function created() 
    {
        if (!$this->model->id) {
            $this->model(Answer::createOrGetAnswerFromKompoClass($this));
        }

        $this->survey = $this->model->survey;
        $this->answerable = $this->model->answerable;
    }

    public function render() 
    {
        return _Rows(
            $this->answerableEls(),
            _Div(
                new AnswerSurveyPollSectionsList([
                    'answer_id' => $this->model->id,
                ]),
            ),
            $this->model->getTotalAnswerCostPanel(),
        )->class('p-6');
    }

    protected function answerableEls()
    {
        return !$this->model->answerable?->id ? _Html() : _Html($this->model->answerable->full_name);
    }
}
