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

        $this->currentPollId = $this->prop('current_poll_id') ?: $this->survey->pollSections()->first()->polls()->value('id');
        $this->currentPoll = Poll::findOrFail($this->currentPollId);
    }

    protected function setOtherPollIds()
    {
        $allPollIds = $this->survey->pollSections()->with('polls')->get()
            ->flatMap(fn($ps) => $ps->polls)
            ->filter(fn($po) => $po->shouldDisplayPoll(Poll::DISPLAY_MODE_INITIAL, $this->model))
            ->pluck('id');

        $currentPollKey = $allPollIds->search($this->currentPollId);

        $this->previousPollId = $allPollIds[$currentPollKey - 1] ?? null;
        $this->nextPollId = $allPollIds[$currentPollKey + 1] ?? null;
    }

    public function handle()
    {
        $this->model->saveAnswerToSinglePoll($this->currentPollId, request('poll_answer'));

        $this->setOtherPollIds(); //Has to come after answer is saved

        return new AnswerSurveyMultiPage($this->model->id, [
            'current_poll_id' => $this->nextPollId,
        ]);
    }

    public function getSinglePollAnswerFom()
    {
        $this->setOtherPollIds();

        return new AnswerSurveyMultiPage($this->model->id, [
            'current_poll_id' => $this->previousPollId,
        ]);
    }

    public function render() 
    {
        $this->setOtherPollIds();

        return _Panel(
            $this->answerableEls(),
            _Div(
                $this->currentPoll->getDisplayInputEls($this->model, true),
            ),
            $this->model->getTotalAnswerCostPanel(),
            _Columns(
                $this->getBackButton(),
                $this->getNextButton(),
            ),
        )->class('p-6')
        ->id(static::SINGLE_POLL_ANSWER_PANEL);
    }

    protected function getBackButton()
    {
        return !$this->previousPollId ? _Html() : 
            _Button('Back')->outlined()->selfGet('getSinglePollAnswerFom')->inPanel(static::SINGLE_POLL_ANSWER_PANEL);
    }

    protected function getNextButton()
    {
        return !$this->nextPollId ? _Html() : 
            _SubmitButton('Next')->inPanel(static::SINGLE_POLL_ANSWER_PANEL);
    }
}
