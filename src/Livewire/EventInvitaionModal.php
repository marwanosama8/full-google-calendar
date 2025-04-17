<?php

namespace Marwanosama8\FullGoogleCalendar\Livewire;

use Livewire\Component;

class EventInvitaionModal extends Component
{
    public $data;
    protected $listeners = ['invitationStatusUpdated' => '$refresh'];

    public function mount($data)
    {
        $this->data = $data;
    }
    public function render()
    {
        return view('full-google-calendar::livewire.event-invitaion-modal');
    }
}
