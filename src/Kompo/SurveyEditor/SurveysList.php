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
        return Survey::where('team_id', $this->teamId)->latest();
    }

    public function top()
    {
        return _FlexBetween(
            _Link()->selfCreate('getItemForm')->inModal(),
        );
    }

    public function render($survey)
    {
        return _TableRow(
            _Html($survey->name_sv),
        )->href($survey->getEditSurveyRoute());
    }

    public function getItemForm()
    {
        return new SurveyForm(null, [
            'team_id' => $this->teamId,
        ]);
    }
}