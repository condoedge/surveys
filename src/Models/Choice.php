<?php

namespace Condoedge\Surveys\Models;

class Choice extends ModelBaseForSurveys
{
	use \Condoedge\Surveys\Models\BelongsToPollTrait;

	/* RELATIONS */

	/* SCOPES */

	/* CALCULATED FIELDS */
	public function remainingQuantity($answer = null)
	{
		$initialQty = $this->choice_max_quantity;

		$usedQty = $this->getUsageQueryForChoice()->forPoll($this->getUsagePollIds())->whereHas('answer', fn($q) => $q->lockedAnswer())->count();

		return $initialQty - $usedQty - $this->getCurrentQuantity($answer);
	}

	public function getUsageQueryForChoice()
	{
		$q = AnswerPoll::query();

		if ($this->poll->hasArrayAnswer()) {
			$q = $q->where('answer_text', 'LIKE', wildcardSpace('"'.$this->id.'"'));
		} else {
			$q = $q->where('answer_text', $this->id);			
		}

		return $q;
	}

	public function getUsagePollIds()
	{
		return $this->poll_id;
	}

	public function getCurrentQuantity($answer)
	{
		return 0; //To override
	}

	/* ACTIONS */

	/* ELEMENTS */
	public function choiceLabelInHtml()
	{
		return '<div onclick="updateChoiceQuantityIfNeeded(this,'.$this->id.')">'.
			'<div>'.$this->choice_content.'</div>'.
			'<div class="text-sm text-info">'.currency($this->choice_amount).$this->choiceMaxQuantityLabel().'</div>'.
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
