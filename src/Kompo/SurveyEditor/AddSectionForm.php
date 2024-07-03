<?php

namespace Condoedge\Surveys\Kompo\SurveyEditor;

use App\Models\Surveys\Survey;
use App\Models\Surveys\PollSection;
use Kompo\Form;

class AddSectionForm extends Form
{
    public $model = Survey::class;

    public function render() 
    {
        return _Columns(
            _Div(
                _Html('campaign.add-single-section')->class('mb-2'),
                _Div()->class('border-dashed border-2 border-gray-400 rounded-lg p-4 w-3/4')
            )->class('flex flex-col justify-center items-center cursor-pointer hover:bg-gray-200 rounded-xl p-4 pt-3')
            ->selfPost('addSingleSection')->refresh('polls-list'),
            _Div(
                _Html('campaign.add-double-section')->class('mb-2'),
                _Columns(
                    _Div()->class('border-dashed border-2 border-gray-400 rounded-lg p-4 mx-auto'),
                    _Div()->class('border-dashed border-2 border-gray-400 rounded-lg p-4 mx-auto')
                )->class('w-full')
            )->class('flex flex-col justify-center items-center cursor-pointer hover:bg-gray-200 rounded-xl p-4 pt-3')
            ->selfPost('addDoubleSection')->refresh('polls-list'),
        )->class('border-dashed border-2 border-gray-400 text-gray-700 rounded-2xl py-2 w-full mx-auto');
    }

    public function addSingleSection() 
    {
        $this->addSection(PollSection::PS_SINGLE_TYPE);
    }

    public function addDoubleSection() 
    {
        $this->addSection(PollSection::PS_DOUBLE_TYPE);
    }

    public function addSection($type)
    {
        $this->model->createNextPollSection($type);
    }
}
