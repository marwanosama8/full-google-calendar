<?php

namespace Marwanosama8\FullGoogleCalendar\Pages;


use Marwanosama8\FullGoogleCalendar\Models\Event;
use Marwanosama8\FullGoogleCalendar\Models\EventInvitation;
use Carbon\Carbon;
use Filament\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;

class Invitations extends Page implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-calendar';
    protected static string $view = 'full-google-calendar::pages.invitations';
    protected static ?string $navigationGroup = 'Kalender';

    public static function getNavigationLabel(): string
    {
        return __('navigation.appointments');
    }

    public function getTitle(): string
    {
        return __('navigation.appointments');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Event::query()->whereJsonContains('participants', (string)auth()->user()->id))
            ->columns([
                TextColumn::make('subject')
                    ->label(__('event-invitation.subject')),
                TextColumn::make('body_preview')
                    ->label(__('event-invitation.body'))->limit(50),
                TextColumn::make('getorganizer.name')
                    ->label(__('event-invitation.organizer'))->searchable(),
                TextColumn::make('start')
                    ->label(__('event-invitation.start'))
                    ->dateTime('d.m.y')
                    ->description(fn (Event $record): string => Carbon::parse($record->start)->toTimeString(), position: 'bottom'),
                TextColumn::make('end')
                    ->label(__('event-invitation.end'))
                    ->dateTime('d.m.y')
                    ->description(fn (Event $record): string => Carbon::parse($record->end)->toTimeString(), position: 'bottom'),
                TextColumn::make('event_category.value')
                    ->label(__('event-invitation.category')),
                TextColumn::make('sw_meeting_url')
                    ->label(__('event-invitation.meeting_url')),
                TextColumn::make("invitation_status")
                    ->label(__('event-invitation.invitation_status'))
                    ->getStateUsing(function ($record) {
                        $status = EventInvitation::where('event_id', $record->id)->where('user_id', auth()->user()->id)->first()?->status;
                        if (!is_null($status)) {
                            return __('event-invitation.status.' . $status);
                        } else {
                            return '';
                        }

                    })
            ])
            ->actions([
                Action::make('Show Participants')
                    ->label(__('event-invitation.show_participants'))
                    ->action(fn (Event $record) => $record)
                    ->modalSubmitAction(false)
                    ->modalCancelAction(false)
                    ->modalContent(fn (Event $record): View => view(
                        'full-google-calendar::pages.invited-events',
                        ['data' => $record],
                    )),
                ActionGroup::make([
                    Action::make('Accept')
                        ->label(__('calendar.accept_invitation'))
                        ->action(function (Event $record) {
                            $this->acceptInvitation($record->id);
                            Notification::make()
                                ->title(__('calendar.accept_invitation'))
                                ->success()
                                ->send();
                        })
                        ->sendSuccessNotification(),
                    Action::make('Decline')
                        ->label(__('calendar.decline_invitation'))
                        ->action(function (Event $record) {
                            $this->declineInvitation($record->id);
                            Notification::make()
                                ->title(__('calendar.decline_invitation'))
                                ->success()
                                ->send();
                        }),
                    Action::make('Missed')
                        ->label(__('calendar.missed_invitation'))
                        ->action(function (Event $record) {
                            $this->missedInvitation($record->id);
                            Notification::make()
                                ->title(__('calendar.missed_invitation'))
                                ->success()
                                ->send();
                        })
                ])
            ]);
    }
    public static function getNavigationBadge(): ?string
    {
        return Event::query()->whereJsonContains('participants', (string)auth()->user()->id)->count();
    }


    public function acceptInvitation($event)
    {
        $this->updateInvitationStatus($event, 'accepted');
    }

    public function declineInvitation($event)
    {
        $this->updateInvitationStatus($event, 'declined');
    }
    public function missedInvitation($event)
    {
        $this->updateInvitationStatus($event, 'missed');
    }

    private function updateInvitationStatus($eventId, $status)
    {
        Event::find($eventId)->invitedUsers()->updateExistingPivot(auth()->user()->id, [
            'status' => $status,
        ]);
    }
}
