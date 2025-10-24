<?php

namespace Condoedge\Surveys\Kompo\HttpExceptions;

use Condoedge\Utils\Kompo\Common\Form;

// TODO This must go to condoedge/utils and create more helpers to use them
class GenericErrorView extends Form
{
    public $containerClass = 'h-screen bg-black bg-opacity-30';
    public $class = 'h-full';

    public $principalMessage = '';
    public $secondaryMessage = '';

    public function created()
    {
        $this->principalMessage = $this->prop('principal_message');
        $this->secondaryMessage = $this->prop('secondary_message');
    }

    public function render()
    {
        return _Rows(
            _Rows(
                $this->errorComponent(),
            )->class('max-w-max bg-white rounded-xl overflow-hidden px-8'),
        )->class('w-full h-full flex justify-center items-center');
    }

    protected function errorComponent()
    {
        return new GenericErrorModal([
            'principal_message' => $this->principalMessage,
            'secondary_message' => $this->secondaryMessage,
        ]);
    }
}
