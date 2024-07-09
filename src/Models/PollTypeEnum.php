<?php

namespace Condoedge\Surveys\Models;

enum PollTypeEnum: int
{
    use \Kompo\Models\Traits\EnumKompo;

    case PO_TYPE_TEXT = 1;
    case PO_TYPE_SELECT = 2;
    case PO_TYPE_RADIO = 3;
    case PO_TYPE_MULTICHECKBOX = 4;
    case PO_TYPE_DATE = 5;
    case PO_TYPE_BINARY = 6;
    case PO_TYPE_INPUT = 7;
    case PO_TYPE_ACCEPTATION = 8;
    case PO_TYPE_RATING = 9;

    public function pollTypeClass()
    {
        return match ($this)
        {
            static::PO_TYPE_TEXT => new \Condoedge\Surveys\Models\PollTypes\PollTypeText(),
            static::PO_TYPE_INPUT => new \Condoedge\Surveys\Models\PollTypes\PollTypeInput(),
            static::PO_TYPE_SELECT => new \Condoedge\Surveys\Models\PollTypes\PollTypeSelect(),
            static::PO_TYPE_RADIO => new \Condoedge\Surveys\Models\PollTypes\PollTypeRadio(),
            static::PO_TYPE_MULTICHECKBOX => new \Condoedge\Surveys\Models\PollTypes\PollTypeMultiCheckbox(),
            static::PO_TYPE_DATE => new \Condoedge\Surveys\Models\PollTypes\PollTypeDate(),
            static::PO_TYPE_BINARY => new \Condoedge\Surveys\Models\PollTypes\PollTypeBinary(),
            static::PO_TYPE_RATING => new \Condoedge\Surveys\Models\PollTypes\PollTypeRating(),
            static::PO_TYPE_ACCEPTATION => new \Condoedge\Surveys\Models\PollTypes\PollTypeAcceptation(),
        };
    }

    public function label()
    {
        return match ($this)
        {
            static::PO_TYPE_TEXT => static::pollTypeLabel('campaign.text', 'textalign-left', 'campaign.text-sub1'),
            static::PO_TYPE_INPUT => static::pollTypeLabel('campaign.text-input', 'row-vertical', 'campaign.text-input-sub1'),
            static::PO_TYPE_SELECT => static::pollTypeLabel('campaign.selection', 'textalign-justifycenter', 'campaign.selection-sub1'),
            static::PO_TYPE_RADIO => static::pollTypeLabel('campaign.simple-choice', 'record-circle', 'campaign.simple-choice-sub1'),
            static::PO_TYPE_MULTICHECKBOX => static::pollTypeLabel('campaign.multiple-choice', 'tick-square', 'campaign.multiple-choice-sub1'),
            static::PO_TYPE_DATE => static::pollTypeLabel('campaign.date', 'calendar', 'campaign.date-sub1'),
            static::PO_TYPE_BINARY => static::pollTypeLabel('campaign.binary-or-yes-no', 'like-dislike', 'campaign.binary-or-yes-no-sub1'),
            static::PO_TYPE_RATING => static::pollTypeLabel('campaign.rating', 'star-1', 'campaign.rating-sub1'),
            static::PO_TYPE_ACCEPTATION => static::pollTypeLabel('campaign.acceptation', 'tick-square', 'campaign.acceptation-sub1'),
        };
    }
    
    public static function pollTypeLabel($text, $icon, $description)
    {
        return _Flex4(
            _Sax($icon, 30)->class('text-gray-500'),
            _Rows(
                _Html($text)->class('text-lg font-medium text-level2'),
                _Html($description)->class('text-xs text-gray-500'),
            )
        )->class('border-b border-gray-200 py-2');
    }
}
