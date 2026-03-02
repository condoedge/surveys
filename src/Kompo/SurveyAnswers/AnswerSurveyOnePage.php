<?php

namespace Condoedge\Surveys\Kompo\SurveyAnswers;

use App\Models\Surveys\Answer;
use App\Models\Surveys\PollSection;
use Condoedge\Utils\Kompo\Common\Form;

class AnswerSurveyOnePage extends Form
{
    use HandlesInlinePollSaving;

    protected $survey;
    protected $answerable;

    protected $pollSectionsRendered = null;
    protected $cachedPollSections = null;
    protected $isActionContext = false;

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
    }

    public function createdAction()
    {
        $this->isActionContext = true;
    }

    public function createdDisplay()
    {
        $this->survey = $this->model->survey;
        $this->answerable = $this->model->answerable;
    }

    protected function getPollSections()
    {
        return $this->cachedPollSections ??= PollSection::where('survey_id', $this->survey->id)->orderPs()
            ->with('polls.choices', 'polls.condition', 'polls.pollProducts')
            ->get();
    }

    public function render()
    {
        if ($this->isActionContext) {
            return _Rows();
        }

        $this->pollSectionsRendered ??= $this->getPollSections()
            ->map(fn($ps) => $this->renderPollSection($ps));

        return _Rows(
            $this->model->getAnswererNameEls(),
            _Rows(
                $this->pollSectionsRendered,
            ),
            $this->model->getTotalAnswerCostPanel(),
            _Columns(
                $this->getBackButton()->class('w-full mb-4 excludeAutoColor'),
                $this->getNextButton()->class('w-full mb-4'),
            ),
        )->class('pt-6 px-2');
    }

    protected function renderPollSection($pollSection)
    {
        $firstPoll = $pollSection->getFirstPoll();

        $content = $this->displayPollInOnePageSurvey($firstPoll);

        if ($pollSection->isDoubleColumn()) {
            $lastPoll = $pollSection->getLastPoll();
            $content = _Columns(
                $content,
                $this->displayPollInOnePageSurvey($lastPoll),
            );
        }

        return $content;
    }

    protected function displayPollInOnePageSurvey($poll)
    {
        if (!$poll) {
            return null;
        }

        return $poll->getDisplayInputEls($this->model);
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
