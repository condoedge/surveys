<?php

namespace Condoedge\Surveys\Kompo\SurveyAnswers;

use App\Models\Surveys\Answer;
use App\Models\Surveys\Condition;
use App\Models\Surveys\Poll;
use Illuminate\Validation\ValidationException;

trait HandlesInlinePollSaving
{
    public function saveInlinePollAnswer()
    {
        $poll = Poll::findOrFail(request('poll_id'));

        return $this->savedOrRefused(fn () => $poll->getPollTypeClass()->saveInline($poll, $this->model));
    }

    /** A form with arrows: save the answer, then show the path it now leads to. */
    public function refreshSurveyPath()
    {
        return $this->savedOrRefused(function () {
            $this->model->saveAnswerToSinglePoll(request('poll_id'));
            \App\Models\Surveys\Survey::forgetAnswerReads();

            return $this->pathEls();
        });
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

    /** A refused answer is said in an alert, not as an error: Kompo leaves « Continue » spinning after a failed request. */
    protected function savedOrRefused(callable $save)
    {
        $this->model->savingInline = true;

        try {
            return $save();
        } catch (ValidationException $e) {
            return response()->kompoAlert('', options: ['alertClass' => 'vlAlertError', 'message' => collect($e->errors())->flatten()->first()]);
        }
    }
}
