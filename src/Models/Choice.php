<?php

namespace Condoedge\Surveys\Models;

class Choice extends ModelBaseForSurveys
{
	use \Condoedge\Surveys\Models\BelongsToPollTrait;

	/* RELATIONS */

	/* SCOPES */

	/* CALCULATED FIELDS */
	public function remainingQuantity()
	{
		$initialQty = $this->choice_max_quantity;

		$usedQty = AnswerPoll::forPoll($this->poll_id)->whereHas('answer', fn($q) => $q->lockedAnswer());

		if ($this->poll->hasArrayAnswer()) {
			$usedQty = $usedQty->where('answer_text', 'LIKE', wildcardSpace('"'.$this->id.'"'));
		} else {
			$usedQty = $usedQty->where('answer_text', $this->id);			
		}

		return $initialQty - $usedQty->count();
	}

	/* ACTIONS */

	/* ELEMENTS */
	public function choiceLabelInHtml()
	{
		return '<div onclick="updateChoiceQuantityIfNeeded(this,'.$this->id.')">'.
			'<div>'.$this->choice_content.'</div>'.
			'<div class="text-sm text-info">'.$this->choice_amount.$this->choiceMaxQuantityLabel().'</div>'.
		'</div>';
	}

	public function choiceMaxQuantityLabel()
	{
		if (!$this->choice_max_quantity) {
			return '';
		}

		$maxQty = intval($this->remainingQuantity());

		$qtyLabel = '<span class="choice_max_quantity" data-initial-qty="'.$maxQty.'">'.$maxQty.'</span>';

		return '<span class="text-gray-600 whitespace-nowrap">&nbsp;('.$qtyLabel.' '.__('campaign.remaining').')</span>';
	}
}
