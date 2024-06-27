<?php

namespace Condoedge\Surveys\Kompo\Surveys;

use App\Models\Surveys\Survey;
use Kompo\Auth\Common\ModalScroll;

class SurveyForm extends ModalScroll
{
    public $model = Survey::class;

    protected $eventId;
    protected $event;

    public function created()
    {
        $this->eventId = $this->prop('event_id');
        $this->event = Event::findOrFail($this->eventId);
    }

    public function beforeSave()
    {
        $this->model->event_id = $this->eventId;
    }

    public function render()
    {
        return _Rows(
            _Card(
                _Input('inscriptions.title')->name('registration_name')
                    ->default(__('inscriptions.registrations').' '.$this->event->name_ev),
                _Columns(
                    _Select('Registration type')->name('registration_type')->options(RegistrationTypeEnum::optionsWithLabels()),
                ),
                _Columns(
                    _DateTime('inscriptions.registration-period-start')->name('registration_start'),
                    _DateTime('inscriptions.registration-period-end')->name('registration_end'),
                ),
                _Columns(
                    _InputDollar('inscriptions.amount-for-registration')->name('registration_price'),
                    _Input('inscriptions.number-of-participants')->name('registration_max_members'),
                ),
            ),
            _SubmitButton('inscriptions.save'),
        )->class('p-8');
    }

    public function rules()
    {
        return [
            'registration_name' => 'required',
            'registration_start' => 'required',
            'registration_end' => 'required',
            'registration_price' => 'required',
            'registration_max_members' => 'required',
        ];
    }
}
