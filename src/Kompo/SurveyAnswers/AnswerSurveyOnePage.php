<?php

namespace Condoedge\Surveys\Kompo\SurveyAnswers;

use App\Models\Surveys\Answer;
use Kompo\Form;

class AnswerSurveyOnePage extends Form
{
    protected $answerableId;
    protected $answerableType;

    protected $answererId;
    protected $answererType;

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
            !$this->model->answerable?->id ? 
                _Html() : 
                _Html($this->model->answerable->full_name),
            _Div(
                new AnswerSurveyPollSectionsList([
                    'answer_id' => $this->model->id,
                ]),
            ),
            $this->model->getTotalAnswerCostPanel(),
            _Columns(
                $this->getBackButton(),
                $this->getNextButton(),
            ),
        )->class('p-6');
    }

    protected function getBackButton()
    {
        return;
        return !$this->prevRecipientId ? ($this->model->prevOrder ? $this->backToPrevExperienceButton() : _Html()) : $this->backButton();
    }

    protected function getNextButton()
    {
        return;
        return $this->isPartOfExperience ? $this->paymentButton() : $this->nextButton();
    }
}
