<?php

namespace Condoedge\Surveys\Models;

use Kompo\Auth\Models\Model;

class Poll extends Model
{
	use \Condoedge\Surveys\Models\BelongsToSurveyTrait;
    use \Condoedge\Surveys\Models\PollConditionRelatedTrait;

    use \Kompo\Database\HasTranslations;
    protected $translatable = [
        'body',
    ];

    public const PO_TYPE_TEXT = 1;
    public const PO_TYPE_SELECT = 2;
    public const PO_TYPE_RADIO = 3;
    public const PO_TYPE_MULTICHECKBOX = 4;
    public const PO_TYPE_DATE = 5;
    public const PO_TYPE_BINARY = 6;
    public const PO_TYPE_INPUT = 7;
    public const PO_TYPE_ACCEPTATION = 8;
    public const PO_TYPE_RATING = 9;

    public const CHOICES_TEXT = 1;
    public const CHOICES_AMOUNT = 2;
    public const CHOICES_QUANTITY = 3;

    public const TEXT_SHORT = 1;
    public const TEXT_LONG = 2;
    public const TEXT_PHONE = 3;
    public const TEXT_EMAIL = 4;
    public const TEXT_ADDRESS = 5;

	/* RELATIONS */
    public function conditions()
    {
        return $this->hasMany(Condition::class);
    }

	/* SCOPES */

	/* CALCULATED FIELDS */
    public static function pickableTypes()
    {
        return collect([
            static::PO_TYPE_TEXT => static::pollTypeLabel('campaign.text', 'textalign-left', 'campaign.text-sub1'),
            static::PO_TYPE_INPUT => static::pollTypeLabel('campaign.text-input', 'row-vertical', 'campaign.text-input-sub1'),
            static::PO_TYPE_SELECT => static::pollTypeLabel('campaign.selection', 'textalign-justifycenter', 'campaign.selection-sub1'),
            static::PO_TYPE_RADIO => static::pollTypeLabel('campaign.simple-choice', 'record-circle', 'campaign.simple-choice-sub1'),
            static::PO_TYPE_MULTICHECKBOX => static::pollTypeLabel('campaign.multiple-choice', 'tick-square', 'campaign.multiple-choice-sub1'),
            static::PO_TYPE_DATE => static::pollTypeLabel('campaign.date', 'calendar', 'campaign.date-sub1'),
            static::PO_TYPE_BINARY => static::pollTypeLabel('campaign.binary-or-yes-no', 'like-dislike', 'campaign.binary-or-yes-no-sub1'),
            static::PO_TYPE_RATING => static::pollTypeLabel('campaign.rating', 'star-1', 'campaign.rating-sub1'),
            static::PO_TYPE_ACCEPTATION => static::pollTypeLabel('campaign.acceptation', 'tick-square', 'campaign.acceptation-sub1'),
        ]);
    }

	/* ACTIONS */
    public function delete()
    {


        parent::delete();
    }

	/* ELEMENTS */
    public static function pollTypeLabel($text, $icon, $description)
    {
        return _Flex4(
            _Sax($icon, 30)->class('text-gray-500'),
            _Rows(
                _Html($text)->class('text-lg font-medium text-level2'),
                _Html($description)->class('text-xs text-gray-500'),
            )
        )->class('px-4 py-2');
    }

    public function getEditInputs()
    {
        switch ($this->type_po) {
            case Poll::PO_TYPE_INPUT:
                return _Rows(
                    $this->getConditionsBox(),
                    _Input('campaign.question')->name('body'),
                    _Input('campaign.question-sub1')->name('explanation'),
                    _ButtonGroup('campaign.input-type')->name('text_type')->required()->vertical()->options(collect([
                        Poll::TEXT_SHORT => __('campaign.field-short'),
                        Poll::TEXT_LONG => __('campaign.field-long'),
                        Poll::TEXT_PHONE => __('campaign.field-phone'),
                        Poll::TEXT_EMAIL => __('auth.email'),
                        Poll::TEXT_ADDRESS => __('campaign.address'),
                    ])->mapWithKeys(fn($label, $key) => [
                        $key => _Html($label)->class('p-2')
                    ]))->comment('campaign.this-will-validate-users-input')
                );
            case Poll::PO_TYPE_TEXT:
                return _Rows(
                    $this->getConditionsBox(),
                    _CKEditor()->name('body')->class('whiteField -mt-16'),
                );
            case Poll::PO_TYPE_DATE:
                return _Rows(
                    $this->getConditionsBox(),
                    _Input('campaign.question')->name('body'),
                    _Input('campaign.question-sub1')->name('explanation'),
                );
            case Poll::PO_TYPE_ACCEPTATION:
                return _Rows(
                    $this->getConditionsBox(),
                    _Input('campaign.question')->name('body'),
                    _Input('campaign.question-sub1')->name('explanation'),
                );
            case Poll::PO_TYPE_RATING:
                $strictOptions = $this->model->getStrictOptions();

                if (!$this->model->choices->count()) {
                    $this->model->preloadChoices($strictOptions);
                }

                return _Rows(
                    $this->getConditionsBox(),
                    _Input('campaign.question')->name('body'),
                    _Input('campaign.question-sub1')->name('explanation'),

                    _MultiForm()->name('choices')->class('hidden')
                        ->formClass(ChoiceForm::class, [
                            'type' => $this->model->type,
                        ]),

                    _Toggle()->name('choices_type_temp', false)->value(0)->class('hidden'),
                    _Toggle()->name('quantity_type_temp', false)->value(0)->class('hidden'),
                );

            default: 
                $optionsClass = $this->model->hasAmountInChoices() ? ' show_amount ' : '';
                $optionsClass .= $this->model->hasQuantityInChoices() ? ' show_quantity ' : '';

                $multiForm = _MultiForm()->name('choices')
                    ->addLabel("campaign.add-a-new-item")
                    ->formClass(ChoiceForm::class, [
                        'type' => $this->model->type,
                        'withAmounts' => request('choices_type_temp'),
                        'withQuantities' => request('quantity_type_temp')
                    ])
                    ->asTable(array_merge
                        (
                            [_Th('campaign.add-options'),],
                            [_Th('campaign.amount')->class('amount_input')],
                            [_Th('campaign.max-quantity')->class('quantity_input')],
                            [_Th(''),]
                        )
                    )->class($optionsClass)->id('mf');

                if($this->model->type == Poll::TYPE_BINARY) {
                    if (!$this->model->choices->count()) {
                        $this->model->preloadChoices([
                            'campaign.yes',
                            'campaign.no'
                        ]);
                    }
                    $multiForm->noAdding();
                } else {
                    $multiForm->preloadIfEmpty();
                }


                return _Rows(
                    $this->getConditionsBox(),
                    _Input('campaign.question')->name('body'),
                    _Input('campaign.question-sub1')->name('explanation'),
                    _Toggle('campaign.toggle-to-associate-amounts-to-choices')
                        ->name('choices_type_temp', false)->value($this->model->hasAmountInChoices())
                        ->run('() => { toggleAmountInputs() }'),
                    _Toggle('campaign.toggle-to-associate-a-maximum-quantity-to-your-choices')
                        ->name('quantity_type_temp', false)->value($this->model->hasQuantityInChoices())
                        ->run('() => { toggleQuantityInputs() }'),
                    $multiForm
                );
        }
    }


}
