<?php

namespace Condoedge\Surveys\Kompo\Common;

use Kompo\Modal;

class ModalScroll extends Modal //TODO: MERGE WITH ModalScroll from kompo/auth
{
    public $class = 'overflow-y-auto mini-scroll';
    public $style = 'max-height: 95vh';
}