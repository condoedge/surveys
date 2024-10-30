<?php

namespace Condoedge\Surveys\Kompo\SurveyAnswers;

use App\Models\Surveys\Answer;
use App\Models\Surveys\Poll;
use App\Models\Surveys\Survey;
use Kompo\Form;

class AnswerSurveyMultiPage extends AnswerSurveyOnePage
{
    protected $currentPollId;
    protected $currentPoll;

    public function created() 
    {
        parent::created();

        $this->currentPollId = $this->prop('current_poll_id') ?: $this->getFirstPollIdForAnswer();
        $this->currentPoll = Poll::find($this->currentPollId);
        if (!$this->currentPoll) {
            abort(403, __('This survey is incomplete!'));
        }
    }

    protected function getFirstPollIdForAnswer()
    {
        return $this->survey->getVisibleOrderedPollsForAnswer($this->model)->first()?->id;
    }

    protected function setOtherPollIds()
    {
        $allPollIds = $this->survey->getVisibleOrderedPollsForAnswer($this->model)->pluck('id');

        $currentPollKey = $allPollIds->search($this->currentPollId);

        $this->previousPollId = $allPollIds[$currentPollKey - 1] ?? null;
        $this->nextPollId = $allPollIds[$currentPollKey + 1] ?? null;
    }

    protected function handleSavingAnswer()
    {
        $this->model->saveAnswerToSinglePoll($this->currentPollId);
    }

    public function handle()
    {
        $this->handleSavingAnswer();

        $this->setOtherPollIds(); //Has to come after answer is saved

        if ($this->nextPollId) {
            return $this->getMultiPageFormForPollId($this->nextPollId);
        }

        return $this->nextResponse();
    }

    protected function nextResponse()
    {
        //
    }

    public function getBackActionPollAnswerForm()
    {
        $this->setOtherPollIds();

        if ($this->previousPollId) {
            return $this->getMultiPageFormForPollId($this->previousPollId);
        }

        return $this->backResponse();
    }

    protected function backResponse()
    {
        //
    }

    protected function getMultiPageFormForPollId($pollId)
    {
        $multiPageForm = config('condoedge-surveys.answer-multi-page-form');

        return new $multiPageForm($this->model->id, array_merge($this->basePayload(), [
            'current_poll_id' => $pollId,
        ]));
    }

    protected function basePayload()
    {
        return []; //To pass data when overriden
    }

    public function render() 
    {
        return _Rows(
            $this->model->getAnswererNameEls(),
            _Div(
                $this->currentPoll->getDisplayInputEls($this->model, true),
            ),
            $this->model->getTotalAnswerCostPanel(),
            _Columns(
                $this->getBackButton()->class('w-full mb-4'),
                $this->getNextButton()->class('w-full mb-4'),
            ),
        )->class('p-4 md:p-6');
    }

    protected function getBackButton()
    {
        return _Button('survey-back')->outlined()->selfGet('getBackActionPollAnswerForm')->inPanel(Survey::SURVEY_ANSWER_PANELID);
    }

    protected function getNextButton()
    {
        return _SubmitButton('survey-next')->inPanel(Survey::SURVEY_ANSWER_PANELID);
    }
}
