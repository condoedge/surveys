<?php

namespace Condoedge\Surveys\Models;

interface SurveyableContract
{
    public function getSurveyOptionsFields($baseEls, $survey);
}