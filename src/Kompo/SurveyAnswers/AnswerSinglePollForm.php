<?php

namespace Condoedge\Surveys\Kompo\SurveyAnswers;

use App\Models\Surveys\Answer;
use App\Models\Surveys\Condition;
use App\Models\Surveys\Poll;
use Condoedge\Utils\Kompo\Common\Form;

class AnswerSinglePollForm extends Form
{
    protected $pollId;
    protected $poll;

    public $model = Answer::class;

    public function created() 
    {
        $this->pollId = $this->prop('poll_id');
        $this->poll = Poll::find($this->pollId);
    }

    public function handle()
    {
        $this->model->saveAnswerToSinglePoll($this->pollId);

        return $this->model->getTotalAnswerCostEls();
    }

    public function render() 
    {
        if (!$this->poll) {
            return;
        }

        return $this->poll->getDisplayInputEls($this->model);
    }

    public function manageDisplayForCondition()
    {
        $condition = Condition::findOrFail(request('condition_id'));

        $answerText = request($condition->conditionPoll->getPollInputName());

        if ($condition->isFulfilled($answerText)) {

            $poll = $condition->poll;
            
            return $poll->getDisplayPostConditionEls($this->model);
        }

        return null;
    }
}
