<?php

namespace Condoedge\Surveys\Models\PollTypes;

class PollTypeInput extends BasePollType
{
    public const TEXT_SHORT = 1;
    public const TEXT_LONG = 2;
    public const TEXT_PHONE = 3;
    public const TEXT_EMAIL = 4;
    public const TEXT_ADDRESS = 5;

	/* DISPLAY ELEMENTS */
	protected function mainInputEl($poll)
    {
        $input = match($poll->text_type) {
            self::TEXT_SHORT => _Input(),
            self::TEXT_LONG => _Textarea(),
            self::TEXT_PHONE => _Input(),
            self::TEXT_EMAIL => _Input()->type('email'),
            self::TEXT_ADDRESS => _Input(),
            default => _Input(),
        };

        return $input;
    }

	/* EDIT ELEMENTS */
    protected function getQuestionOptionsEls($poll)
    {
    	return _Rows(
            _ButtonGroup('campaign.input-type')->name('text_type')->required()->vertical()->options(collect([
                self::TEXT_SHORT => __('campaign.field-short'),
                self::TEXT_LONG => __('campaign.field-long'),
                self::TEXT_PHONE => __('campaign.field-phone'),
                self::TEXT_EMAIL => __('auth.email'),
                self::TEXT_ADDRESS => __('campaign.address'),
            ])->mapWithKeys(fn($label, $key) => [
                $key => _Html($label)->class('p-2')
            ]))->comment('campaign.this-will-validate-users-input'),
            parent::getQuestionOptionsEls($poll),
        );
    }
}
