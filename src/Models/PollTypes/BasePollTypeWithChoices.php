<?php

namespace Condoedge\Surveys\Models\PollTypes;

use Condoedge\Surveys\Kompo\SurveyEditor\ChoiceForm;
use App\Models\Surveys\Choice;

abstract class BasePollTypeWithChoices extends BasePollType
{
    public const POLL_HAS_OPEN_ANSWER = false;

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
                ->value($poll->hasChoicesAmounts())
                ->run('() => { $("#choices-multi-form").toggleClass("choices_show_amount") }'),
            _Toggle('campaign.toggle-to-associate-a-maximum-quantity-to-your-choices')
                ->name('quantity_type_temp', false)
                ->value($poll->hasChoicesQuantities())
                ->run('() => { $("#choices-multi-form").toggleClass("choices_show_quantity") }'),
            $this->getChoicesMultiForm($poll),
        );
    }

    protected function getChoicesMultiForm($poll)
    {
    	$el = _MultiForm()->name('choices')->id('choices-multi-form')
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

        if ($poll->hasChoicesAmounts()) {
            $el->class('choices_show_amount');
        }

        if ($poll->hasChoicesQuantities()) {
            $el->class('choices_show_quantity');
        }

        return $el;
    }

    /* ACTIONS */
    public function validateSpecificToType($poll, $value)
    {
        if ($value && !$poll->choices()->pluck('id')->contains($value)) {
            throwValidationError($poll->getPollInputName(), 'error-translations.pick-one-of-the-choices');
        }

        if ($value && $poll->hasChoicesQuantities()) {
            $choice = Choice::findOrFail($value);
            if ($choice->remainingQuantity() <= 0) {
                throwValidationError($poll->getPollInputName(), 'Sorry, the last available item has just been reserved');
            }
        }
    }
}
