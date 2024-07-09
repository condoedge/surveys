<?php

namespace Condoedge\Surveys\Kompo\SurveyAnswers;

use App\Models\Surveys\Answer;
use App\Models\Surveys\Condition;
use App\Models\Surveys\Poll;
use Kompo\Form;

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
        $inputName = $this->poll->getPollInputName();

        $this->model->saveAnswerToSinglePoll($this->pollId, request($inputName));

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

        if ($answerText == $condition->condition_choice_id) {

            $poll = $condition->poll;
            
            return $poll->getDisplayPostConditionEls($this->model);
        }

        return null;
    }
}
