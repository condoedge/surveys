<?php

namespace Condoedge\Surveys\Kompo\SurveyAnswers;

use App\Models\Surveys\Answer;
use App\Models\Surveys\Condition;

trait HandlesInlinePollSaving
{
    public function saveInlinePollAnswer()
    {
        $pollId = request('poll_id');

        $this->model->saveAnswerToSinglePoll($pollId);

        return $this->model->getTotalAnswerCostEls();
    }

    public function manageDisplayForCondition()
    {
        $condition = Condition::findOrFail(request('condition_id'));

        $answerText = request($condition->conditionPoll->getPollInputName());

        if ($condition->isFulfilled($answerText)) {
            return $condition->poll->getDisplayPostConditionEls($this->model);
        }

        return null;
    }
}
