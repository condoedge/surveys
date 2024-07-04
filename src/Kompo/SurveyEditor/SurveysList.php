<?php

namespace Condoedge\Surveys\Kompo\SurveyEditor;

use App\Models\Surveys\Survey;
use Kompo\Table;

class SurveysList extends Table
{
    protected $teamId;

    public function created()
    {
        $this->teamId = currentTeamId();
    }

    public function query()
    {
        return Survey::forTeam($this->teamId)->latest();
    }

    public function top()
    {
        return _FlexBetween(
            _Link()->iconCreate()->selfCreate('getItemForm')->inModal(),
        );
    }

    public function render($survey)
    {
        return _TableRow(
            _Html($survey->name_sv),
            _Link('One page preview')->selfGet('getOnePage', ['id' => $survey->id])->inModal(),
            _Link('Multi page preview')->selfGet('getMultiPage', ['id' => $survey->id])->inModal(),
        )->href('survey.edit', ['id' => $survey->id]);
    }

    public function getItemForm()
    {
        return new SurveyForm(null, [
            'team_id' => $this->teamId,
        ]);
    }

    public function getOnePage($id)
    {
        return new \Condoedge\Surveys\Kompo\SurveyAnswers\AnswerSurveyOnePage(null, [
            'survey_id' => $id,
            'answerable_id' => auth()->id(),
            'answerable_type' => 'user',
            'answerer_id' => auth()->id(),
            'answerer_type' => 'user',
        ]);
    }

    public function getMultiPage($id)
    {
        return new \Condoedge\Surveys\Kompo\SurveyAnswers\AnswerSurveyMultiPage(null, [
            'survey_id' => $id,
            'answerable_id' => auth()->id(),
            'answerable_type' => 'user',
            'answerer_id' => auth()->id(),
            'answerer_type' => 'user',
        ]);
    }
}