<?php

namespace Condoedge\Surveys\Models\PollTypes;

use Condoedge\Surveys\Kompo\SurveyEditor\ChoiceForm;

abstract class BasePollTypeWithChoices extends BasePollType
{
	/* DISPLAY ELEMENTS */
	abstract protected function mainInputElWithoutOptions($poll);

	protected function mainInputEl($poll)
    {
        return $this->mainInputElWithoutOptions($poll)->options(
        	$poll->choices()->with('poll')->get()->mapWithKeys(fn($choice) => [
                $choice->id => $choice->choiceLabelInHtml(),
            ])
        );
    }

	/* EDIT ELEMENTS */
    protected function getChoicesInfoEls($poll)
    {
    	return _Rows(
            _Toggle('campaign.toggle-to-associate-amounts-to-choices')
                ->name('choices_type_temp', false)
                ->run('() => { toggleAmountInputs() }'),
            _Toggle('campaign.toggle-to-associate-a-maximum-quantity-to-your-choices')
                ->name('quantity_type_temp', false)
                ->run('() => { toggleQuantityInputs() }'),
            $this->getChoicesMultiForm($poll),
        );
    }

    protected function getChoicesMultiForm($poll)
    {
    	$el = _MultiForm()->name('choices')
            ->addLabel("campaign.add-a-new-item")
            ->formClass(ChoiceForm::class, [
                'type_po' => $poll->type_po,
                'withAmounts' => request('choices_type_temp'),
                'withQuantities' => request('quantity_type_temp'),
            ])
            ->asTable([
                _Th('campaign.add-options'),
                _Th('campaign.amount')->class('choice_amount_input'),
                _Th('campaign.max-quantity')->class('choice_quantity_input'),
                _Th(''),
            ])->preloadIfEmpty();

        if ($poll->showChoicesAmounts()) {
            $el->class('choices_show_amount');
        }

        if ($poll->showChoicesQuantities()) {
            $el->class('choices_show_quantity');
        }

        return $el;
    }
}
