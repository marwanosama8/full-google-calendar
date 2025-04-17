<?php

namespace Marwanosama8\FullGoogleCalendar\Widgets;

use App\Models\User;
use Marwanosama8\FullGoogleCalendar\Helper\Helper;
use Marwanosama8\FullGoogleCalendar\Models\Event;
use Marwanosama8\FullGoogleCalendar\Models\EventCategory;
use Marwanosama8\FullGoogleCalendar\Services\FullGoogleCalendar\GoogleCalendarActions;
use Marwanosama8\FullGoogleCalendar\Services\FullGoogleCalendar\GoogleCalendarClient;
use Marwanosama8\FullGoogleCalendar\Services\FullGoogleCalendar\GoogleCalendarEventsActions;
use Marwanosama8\FullGoogleCalendar\Services\FullGoogleCalendar\GoogleCalendarService;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Illuminate\Database\Eloquent\Model;
use Saade\FilamentFullCalendar\Actions;
use Filament\Forms\Get;
use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action as ActionAction;
use Illuminate\Contracts\View\View;

class FullGoogleCalendarWidget extends FullCalendarWidget
{
    use HasFiltersForm;

    public Model | string | null $model = Event::class;

    private GoogleCalendarClient $client;
    private GoogleCalendarService $service;

    public function boot(GoogleCalendarClient $client, GoogleCalendarService $service)
    {
        // dd('widget,' ,$client);
        $this->client = $client;
        $this->service = $service;
    }
    public function mount()
    {
        if (Helper::isGoogleServiceConfigAvaillable()) {
            if (!is_null($this->client->error)) {
                return Notification::make()
                    ->title(__('full-google-calendar::full-google-calendar.notification.google-says') . $this->client->error)
                    ->danger()
                    ->body(__('full-google-calendar::full-google-calendar.you-need-to-sign-in'))
                    ->actions([
                        ActionAction::make('Sign In')
                            ->label(__('full-google-calendar::full-google-calendar.sign-in'))
                            ->color('success')
                            ->url(route('auth.google.calendar'))
                            ->button(),
                    ])
                    ->send();
            }
        }
    }

    public function fetchEvents(array $fetchInfo): array
    {
        $authUserId = auth()->user()->id;

        $events = Event::with('event_category')
            ->select('id', 'subject', 'start', 'end', 'body', 'category', 'participants', 'meeting_url', 'attachments')
            ->where('start', '>=', $fetchInfo['start'])
            ->where('end', '<=', $fetchInfo['end'])
            ->whereJsonContains('participants', (string)$authUserId)
            ->get()
            ->map(function (Event $task) {
                $eventColor = $task->event_category ? $task->event_category?->color : '#000000';  // Fallback color if no category
                
                return [
                    'id' => $task->id,
                    'title' => $task->subject,
                    'start' => $task->start,
                    'end' => $task->end,
                    'description' => $task->body,
                    'borderColor' => $eventColor,
                    'backgroundColor' => $eventColor,
                    'participants' => $task->participants,
                    'meetingLink' => $task->meeting_url,
                    'attachments' => $task->attachments,
                ];
            })
            ->toArray();
        
        return $events;
        
    }
    protected function headerActions(): array
    {
        $shouldSync = true;
        try {
            $shouldSync = $this->service->shouldSync();
        } catch (\Throwable $th) {
            $shouldSync = false;
        }


        return [
            Action::make('Google Calendar Settings')
                ->label(__('full-google-calendar::full-google-calendar.action.google-settings'))
                ->extraAttributes(['class' => 'mr-4'])
                ->hidden(!Helper::isGoogleServiceConfigAvaillable())
                ->color($shouldSync ? 'success' : 'danger')
                ->closeModalByClickingAway(false)
                ->modalSubmitAction(false)
                ->modalContent(fn(): View => view(
                    'full-google-calendar::actions.google-settings-action',
                    // ['client' => $this->client]
                )),
            Actions\CreateAction::make()
                ->using(function (array $data, string $model): Model {
                    $event = $this->createEvent($data, $model);
                    return $event;
                })
                ->mountUsing(
                    function (Form $form, array $arguments) {
                        $form->fill([
                            'start' => $arguments['start'] ?? null,
                            'end' => $arguments['end'] ?? null
                        ]);
                    }
                )
                ->slideOver(),
        ];
    }

    protected function modalActions(): array
    {
        return [
            Actions\EditAction::make()
                ->slideOver()
                ->using(function (array $data, Model $record) {
                    $event = $record->update($data);
                    return $event;
                }),
            Actions\DeleteAction::make()
                ->slideOver(),
        ];
    }

    public function getFormSchema(): array
    {
        return [
            TextInput::make('subject')
                ->label(__('full-google-calendar::full-google-calendar.subject'))
                ->required(),
            RichEditor::make('body')
                ->label(__('full-google-calendar::full-google-calendar.body'))
                ->disableToolbarButtons([
                    'blockquote',
                    'attachFiles',
                    'strike',
                ]),
            Select::make('organizer')
                ->label(__('full-google-calendar::full-google-calendar.organizer'))
                ->required()
                ->native(0)
                ->disabled()
                ->hiddenOn('create')
                ->options(
                    User::all()->pluck('name', 'id')->toArray()
                ),
            Select::make('participants')
                ->label(__('full-google-calendar::full-google-calendar.participants'))
                ->multiple()
                ->helperText("If you can't find a specific user here!, that's becouse it's not setted his google email yet!.")
                ->hidden(fn(Get $get): bool => $get('invite_all') == 1)
                ->options(
                    User::whereHas('googleCalendarProfile', function ($query) {
                        $query->whereNotNull('google_email');
                    })->pluck('name', 'id')->toArray()
                ),
            Toggle::make('invite_all')
                ->hiddenOn('view')
                ->label(__('full-google-calendar::full-google-calendar.invite_all'))
                ->live(),
            Select::make('category')
                ->label(__('full-google-calendar::full-google-calendar.category'))
                ->required()
                ->native(0)
                ->options([
                    '' => EventCategory::all()->pluck('value', 'id')->toArray()
                ]),
            TextInput::make('event_origin')
                ->label(__('full-google-calendar::full-google-calendar.event_origin')),
            Select::make('event_leader')
                ->label(__('full-google-calendar::full-google-calendar.event_leader'))
                ->options([
                    //User::role(['Super Admin'])->pluck('name', 'id')->toArray()
                    '' => User::all()
                        ->pluck('name', 'id')
                        ->toArray()
                ])
                ->native(0),
            TextInput::make('meeting_url')
                ->label(__('full-google-calendar::full-google-calendar.meeting_url')),
            // ColorPicker::make('text_color'),
            Toggle::make('isAllDay')
                ->hiddenOn('view')
                ->label(__('full-google-calendar::full-google-calendar.isAllDay')),
            Grid::make()
                ->schema([
                    DateTimePicker::make('start')
                        ->label(__('full-google-calendar::full-google-calendar.start'))
                        ->required()
                        ->timezone(config('app.timezone'))
                        ->native(1),
                    DateTimePicker::make('end')
                        ->label(__('full-google-calendar::full-google-calendar.end'))
                        ->timezone(config('app.timezone'))
                        ->required()
                        ->native(1),
                ]),

            Section::make('Attachments')
                ->label(__('full-google-calendar::full-google-calendar.attachments'))
                ->schema([
                    FileUpload::make('attachments')
                        ->multiple()
                ])
                ->collapsed()
        ];
    }

    private function createEvent($data, $model)
    {
        $arr =  $this->assingParticipants($data);

        $arr['participants'][] = (string)auth()->user()->id;

        $arr['organizer'] = auth()->id();
        $event = $model::create($arr);

        $this->insertInGoogleCalendar($event);

        return $event;
    }

    private function insertInGoogleCalendar($data)
    {
        $shouldSync = true;
        try {
            $shouldSync = $this->service->shouldSync();
        } catch (\Throwable $th) {
            $shouldSync = false;
        }

        try {
            if ($shouldSync) {
                $this->service->createOrUpdateEventToGoogle($data);
            }
        } catch (\Exception $th) {
            return Notification::make()
                ->title(__('full-google-calendar::full-google-calendar.notification.google-says') . $th->getMessage())
                ->danger()
                ->body(__('full-google-calendar::full-google-calendar.you-need-to-sign-in'))
                ->actions([
                    ActionAction::make('Sign In')
                        ->label(__('full-google-calendar::full-google-calendar.sign-in'))
                        ->color('success')
                        ->url(route('auth.google.calendar'))
                        ->button(),
                ])
                ->send();
        }
    }

    private function assingParticipants($data): array
    {
        $arr = $data;

        if (!empty($arr['invite_all']) && $arr['invite_all'] == true) {
            $arr['participants'] = User::all()->pluck('id')->map(function ($id) {
                return (string) $id;
            })->toArray();
        } else {
            $arr['participants'] = array_map('strval', $arr['participants']);
        }

        return $arr;
    }
}
