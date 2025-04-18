<?php

namespace Condoedge\Surveys\Kompo\SurveyEditor;

use App\Models\Surveys\Survey;
use Condoedge\Utils\Kompo\Common\Form;

class EditSurveyForm extends Form
{
    public $model = Survey::class;

    public function created() 
    {

    }

    public function render() 
    {
        return _Rows(
        	$this->model->getSurveyOptionsFields(),
        );
    }

    public function rules()
    {
        return $this->model->getSurveyOptionsRules();
    }
}
