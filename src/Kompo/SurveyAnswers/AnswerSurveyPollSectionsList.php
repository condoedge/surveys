<?php

namespace Condoedge\Surveys\Kompo\SurveyAnswers;

use App\Models\Surveys\PollSection;
use App\Models\Surveys\Answer;
use App\Models\Surveys\AnswerPoll;
use App\Models\Surveys\Condition;
use Kompo\Query;

class AnswerSurveyPollSectionsList extends Query
{
    public $perPage = 1000;

    protected $answerId;
    protected $answer;
    protected $surveyId;

	public function created()
	{
        $this->answerId = $this->prop('answer_id');
        $this->answer = Answer::findOrFail($this->answerId);
        $this->surveyId = $this->answer->survey_id;
	}

	public function query()
	{
		return PollSection::where('survey_id', $this->surveyId)->orderPs();
	}

    public function render($pollSection)
    {
        $firstPoll = $pollSection->getFirstPoll();

        $content = $firstPoll?->getDisplayInputEls($this->answer);

        if($pollSection->isDoubleColumn()) {
            $lastPoll = $pollSection->getLastPoll();
            $content = _Columns(
                $content,
                $lastPoll?->getDisplayInputEls($this->answer),
            );
        }

        return _Rows(
            $content
        );
    }

    public function saveAnswerForPoll()
    {
        $this->answer->saveAnswerToSinglePoll(request('poll_id'), request('poll_answer'));

        return $this->answer->getTotalAnswerCostEls();
    }

    public function manageDisplayForCondition()
    {
        $condition = Condition::findOrFail(request('condition_id'));

        $answerText = request('poll_answer');

        if ($answerText == $condition->condition_choice_id) {
            $poll = $condition->poll;
            return $poll->getDisplayPostConditionEls($this->answer);
        }

        return null;
    }
}
