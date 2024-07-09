<?php

namespace Condoedge\Surveys\Kompo\SurveyAnswers;

use App\Models\Surveys\Answer;
use App\Models\Surveys\Poll;
use Kompo\Form;

class AnswerSurveyMultiPage extends AnswerSurveyOnePage
{
    protected $currentPollId;
    protected $currentPoll;

    public const SINGLE_POLL_ANSWER_PANEL = 'SINGLE_POLL_ANSWER_PANEL';

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
        return $this->survey->getVisibleOrderedPollsForAnswer($this->model)->first()->id;
    }

    protected function setOtherPollIds()
    {
        $allPollIds = $this->survey->getVisibleOrderedPollsForAnswer($this->model)->pluck('id');

        $currentPollKey = $allPollIds->search($this->currentPollId);

        $this->previousPollId = $allPollIds[$currentPollKey - 1] ?? null;
        $this->nextPollId = $allPollIds[$currentPollKey + 1] ?? null;
    }

    public function handle()
    {
        $this->model->saveAnswerToSinglePoll($this->currentPollId, request($this->currentPoll->getPollInputName()));

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
        return _Panel(
            $this->model->getAnswererNameEls(),
            _Div(
                $this->currentPoll->getDisplayInputEls($this->model, true),
            ),
            $this->model->getTotalAnswerCostPanel(),
            _Columns(
                $this->getBackButton()->class('w-full mb-4'),
                $this->getNextButton()->class('w-full mb-4'),
            ),
        )
        ->id(static::SINGLE_POLL_ANSWER_PANEL);
    }

    protected function getBackButton()
    {
        return _Button('Back')->outlined()->selfGet('getBackActionPollAnswerForm')->inPanel(static::SINGLE_POLL_ANSWER_PANEL);
    }

    protected function getNextButton()
    {
        return _SubmitButton('Next')->inPanel(static::SINGLE_POLL_ANSWER_PANEL);
    }
}
