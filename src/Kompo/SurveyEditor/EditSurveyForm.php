<?php

namespace Condoedge\Surveys\Kompo\SurveyEditor;

use App\Models\Surveys\Survey;
use Kompo\Form;

class EditSurveyForm extends Form
{
    public $model = Survey::class;

    public function created() 
    {

    }

    public function render() 
    {
        return _Rows(

        );
    }
}
