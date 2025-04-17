<?php

namespace Marwanosama8\FullGoogleCalendar\Livewire;

use Livewire\Component;
use Marwanosama8\FullGoogleCalendar\Models\Event;

class InvitationStatusActions extends Component
{
    public $eventId;
    public $status = 'pending';
    public $note = '';

    public function mount($eventId)
    {
        $this->eventId = $eventId;
    }

    public function accept()
    {
        $this->status = 'accepted';
        $this->updateInvitationStatus();
        $this->dispatch('invitationStatusUpdated');
    }

    private function updateInvitationStatus()
    {
        Event::find($this->eventId)->invitedUsers()->updateExistingPivot(auth()->user()->id, [
            'status' => $this->status,
            'note' => $this->note,
        ]);

        // Reset form fields after updating
        $this->reset(['status', 'note']);
    }

    public function decline()
    {
        $this->status = 'declined';
        $this->updateInvitationStatus();
        $this->dispatch('invitationStatusUpdated');
    }

    public function missed()
    {
        $this->status = 'missed';
        $this->updateInvitationStatus();
        $this->dispatch('invitationStatusUpdated');
    }

    public function render()
    {
        return view('full-google-calendar::livewire.invitation-status-actions');
    }
}
