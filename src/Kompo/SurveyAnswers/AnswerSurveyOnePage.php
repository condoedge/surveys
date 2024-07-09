<?php

namespace Condoedge\Surveys\Kompo\SurveyAnswers;

use App\Models\Surveys\Answer;
use App\Models\Surveys\PollSection;
use Kompo\Form;

class AnswerSurveyOnePage extends Form
{
    protected $survey;
    protected $answerable;

    public $model = Answer::class;

    public function created() 
    {
        if (!$this->model->id) {
            $answerPayload = [];
            foreach (Answer::answerPayloadColumns() as $col) {
                $answerPayload[$col] = $this->prop($col);
            }
            $this->model(Answer::createOrGetAnswerFromKompoClass($answerPayload));
        }

        $this->survey = $this->model->survey;
        $this->answerable = $this->model->answerable;
    }

    public function render() 
    {
        return _Rows(
            $this->model->getAnswererNameEls(),
            _Div(
                PollSection::where('survey_id', $this->survey->id)->orderPs()->get()->map(
                    fn($ps) => $this->renderPollSection($ps)
                )
            ),
            $this->model->getTotalAnswerCostPanel(),
            _Columns(
                $this->getBackButton(),
                $this->getNextButton(),
            ),
        )->class('p-6');
    }

    protected function renderPollSection($pollSection)
    {
        $firstPoll = $pollSection->getFirstPoll();

        $content = new AnswerSinglePollForm($this->model->id, ['poll_id' => $firstPoll?->id]);

        if($pollSection->isDoubleColumn()) {
            $lastPoll = $pollSection->getLastPoll();
            $content = _Columns(
                $content,
                new AnswerSinglePollForm($this->model->id, ['poll_id' => $lastPoll?->id]),
            );
        }

        return _Rows(
            $content
        );
    }

    protected function getBackButton()
    {
        return _Html();
    }

    protected function getNextButton()
    {
        return _Html();
    }
}
