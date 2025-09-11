<?php

namespace Condoedge\Surveys\Models\PollTypes;

use Condoedge\Surveys\Models\PollTypeEnum;

class PollComboPersonInfo extends BasePollType
{
    public const POLLTYPE_IS_COMBO = true;

	/* DISPLAY ELEMENTS */

	/* EDIT ELEMENTS */

	/*ACTIONS */
	public static function createPollsCombo($surveyId, $pollSectionId, $position)
	{
		//Full name
		$poll = static::initializePollForCombo($surveyId, $pollSectionId, $position); //Only first one gets psid and postion
		$poll->type_po = PollTypeEnum::PO_TYPE_INPUT;
		$poll->body_po = translationsArr('campaign.surveys-input-name');
		$poll->explanation_po = __('campaign.surveys-input-name-exp');
		$poll->text_type = PollTypeInput::TEXT_SHORT;
		$poll->save();

		//Email
		$poll = static::initializePollForCombo($surveyId);
		$poll->type_po = PollTypeEnum::PO_TYPE_INPUT;
		$poll->body_po = translationsArr('campaign.surveys-input-email');
		$poll->explanation_po = __('campaign.surveys-input-email-exp');
		$poll->text_type = PollTypeInput::TEXT_EMAIL;
		$poll->save();

		//Phone
		$poll = static::initializePollForCombo($surveyId);
		$poll->type_po = PollTypeEnum::PO_TYPE_INPUT;
		$poll->body_po = translationsArr('campaign.surveys-input-phone');
		$poll->explanation_po = __('campaign.surveys-input-phone-exp');
		$poll->text_type = PollTypeInput::TEXT_PHONE;
		$poll->save();

		//Address
		$poll = static::initializePollForCombo($surveyId);
		$poll->type_po = PollTypeEnum::PO_TYPE_INPUT;
		$poll->body_po = translationsArr('campaign.surveys-input-address');
		$poll->explanation_po = __('campaign.surveys-input-address-exp');
		$poll->text_type = PollTypeInput::TEXT_ADDRESS;
		$poll->save();
	}
}
