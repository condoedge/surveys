<?php

namespace Condoedge\Surveys\Kompo\SurveyEditor;

use App\Models\Surveys\Survey;
use Condoedge\Utils\Kompo\Common\Form;

/**
 * Left of the builder, under the options: the form's ends and their messages, what the arrows leave unreachable,
 * and the plan of every path. Before the first arrow, a line saying how to start.
 */
class SurveyBranchingCard extends Form
{
    public $model = Survey::class;

    public function render()
    {
        if (!$this->model->usesBranching()) {
            return _Rows(
                _Html('surveys-form-ends')->class('text-lg font-bold'),
                _Html('surveys-branching-intro')->class('text-sm text-gray-600'),
            )->class('mt-6 gap-1');
        }

        $issues = $this->model->branchingIssues();

        return _Rows(
            _FlexBetween(
                _Html('surveys-form-ends')->class('text-lg font-bold'),
                _Link('surveys-plan')->icon(_Sax('hierarchy-2', 18))->class('text-sm font-medium min-h-11 flex items-center')
                    ->selfGet('getPlanEls')->inModal(),
            ),
            _Html('surveys-form-ends-hint')->class('text-sm text-gray-600 mb-2'),
            _Rows(...collect(Survey::ENDS)->keys()->map(fn($end) => _Textarea(__('surveys-result-'.$end))
                ->name('end_'.$end.'_message')->placeholder(__('surveys-end-'.$end.'-default'))->rows(2))->all()),
            _Input('surveys-end-contact')->name('end_contact')->placeholder('surveys-end-contact-placeholder'),
            _SubmitButton('campaign.save')->alert('surveys-ends-saved')->class('w-full md:w-auto'),
            _Html('surveys-checks')->class('font-semibold mt-4'),
            !$issues ? _Html('surveys-checks-ok')->class('text-sm text-positive')
                : _Rows(collect($issues)->map(fn($issue) => _Html(e($issue))->class('text-sm text-danger'))->all())->class('gap-1'),
        )->class('mt-6 gap-2');
    }

    public function getPlanEls()
    {
        return _Rows(
            _Html('surveys-plan-title')->class('text-xl font-bold mb-4'),
            $this->model->planEls(),
        )->class('p-6 overflow-x-auto');
    }

    public function rules()
    {
        return [
            'end_yes_message' => 'nullable|max:1000',
            'end_no_message' => 'nullable|max:1000',
            'end_warn_message' => 'nullable|max:1000',
            'end_contact' => 'nullable|max:255',
        ];
    }
}
