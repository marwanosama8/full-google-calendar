@php
    use Marwanosama8\FullGoogleCalendar\Models\Event;
    use App\Models\User;
    use Carbon\Carbon;
    use Filament\Facades\Filament;
    use Illuminate\Support\Fluent;
    $event = $data;
    $startTime = Carbon::parse($event->start);
    $endTime = Carbon::parse($event->end);
    $start = Carbon::create($event->start)->setTimeFrom($startTime);
    $end = Carbon::create($event->end)->setTimeFrom($endTime);
    $color = $event->event_category->color ? $event->event_category->color : 'primary';
    $name = config('full-google-calendar.categories.labels.' . $event->event_category->value);
    $participants = $event?->participants;
    $subject = $event?->subject;
    $meeting_url = $event?->sw_meeting_url;
    $attachments = $event?->attachments;
    $leader = User::find($event?->sw_event_leader);
    $organizer = User::find($event->organizer);

    $invitedUsers = Event::find($event->id)?->invitedUsers->sortByDesc('name');

    if ($start == $end) {
        $duration = $endTime->shortRelativeDiffForHumans($startTime);
    } else {
        $duration = $end->shortAbsoluteDiffForHumans($start, 3);
    }
@endphp
<div>
    @unless (!$organizer)
        <div class="flex items-center gap-4 pb-2 text-xs">
            <x-dynamic-component :component="config('full-google-calendar.pages.buttons.modal.view.event_organizer')" class="w-5 h-5" />
            <span>{{ $organizer->name }}</span>
        </div>
    @endunless
    <div class="flex items-center gap-4 pb-2">
        <x-dynamic-component :component="config('full-google-calendar.pages.buttons.modal.view.time', 'heroicon-o-clock')" class="w-5 h-5" />
        <div>
            {{ $start->isoFormat('dddd, D MMM Y') }}
            <div class="flex items-center gap-2 text-xs">
                <div>
                    {{ $startTime->isoFormat('H:mm') }}
                </div>
                <div>
                    <x-dynamic-component :component="'heroicon-o-arrow-long-right'" class="w-4 h-4" />
                </div>
                <div>
                    @if ($event->start !== $event->end)
                        {{ $end->isoFormat('H:mm, D.M.Y') }}
                    @else
                        {{ $endTime->isoFormat('H:mm') }}
                    @endif
                </div>
                <div>
                    ({{ $duration }})
                </div>
            </div>
        </div>
    </div>
    @unless (!$leader)
        <div class="flex items-center gap-4 pb-2 text-xs">
            <x-dynamic-component :component="config('full-google-calendar.pages.buttons.modal.view.event_leader')" class="w-5 h-5" />
            <span>{{ $leader->name }}</span>
        </div>
    @endunless
    @unless (!$subject)
        <div class="flex items-center gap-4 pb-2 text-xs">
            <x-dynamic-component :component="config('full-google-calendar.pages.buttons.modal.view.event_leader')" class="w-5 h-5" />
            <span>{{ $subject }}</span>
        </div>
    @endunless
    @unless (!$event->category)
        <div class="flex items-center gap-4 pb-2 text-xs">
            <div>
                <x-dynamic-component :component="config('full-google-calendar.pages.buttons.modal.view.category', 'heroicon-o-tag')" class="w-5 h-5" />
            </div>
            <div @class([
                'bg-' . $color . '-500/10 text-' . $color . '-500' =>
                    $color !== 'secondary',
                'bg-gray-500/10 text-gray-500' => $color == 'secondary',
                'rounded-xl',
            ])>
                {{ $name }}
            </div>
        </div>
    @endunless
    @unless (!$event->body)
        <div class="flex items-start gap-4 pt-2">
            <div class="mt-1">
                {{-- <x-dynamic-component
                    :component="config('full-google-calendar.pages.buttons.modal.view.body','heroicon-o-pencil-square')"
                    class="w-5 h-5" /> --}}
            </div>
            <div class="pb-2">
                {{ \Livewire\str($event->body)->toHtmlString() }}
            </div>
        </div>
    @endunless

    @unless (!$invitedUsers)
        <div class="flex items-start gap-4 pt-2" style="margin-bottom: 200px;">
            <div>
                <x-dynamic-component :component="config('full-google-calendar.pages.buttons.modal.view.participants', 'heroicon-o-user-group')" class="w-5 h-5" />
            </div>
            <div class="flex flex-col gap-2">
                @php
                    // Function to count participants that accepted the invitation
                    function countAcceptedParticipants($eventId)
                    {
                        return Event::find($eventId)->invitedUsers()->wherePivot('status', 'accepted')->count();
                    }

                    function countDeclinedParticipants($eventId)
                    {
                        return Event::find($eventId)->invitedUsers()->wherePivot('status', 'declined')->count();
                    }

                    function countMissedParticipants($eventId)
                    {
                        return Event::find($eventId)->invitedUsers()->wherePivot('status', 'missed')->count();
                    }

                    $acceptedCount = countAcceptedParticipants($event->id);
                    $declinedCount = countDeclinedParticipants($event->id);
                    $missedCount = countMissedParticipants($event->id);
                @endphp
                <span class="text-sm">{{ __('calendar.invited') }}: {{ count($invitedUsers) }},
                    {{ __('calendar.accepted') }}: {{ $acceptedCount }}, {{ __('calendar.declined') }}:
                    {{ $declinedCount }}, {{ __('calendar.missed') }}:
                    {{ $missedCount }}</span>
                @foreach ($invitedUsers as $invitedUser)
                    @php
                        // Check if user accepted the invitation and set their color accordingly
                        $invitationStatus = $invitedUser->pivot->status ?? null;
                        $bgColorClass = '';
                        $textColorClass = '';

                        switch ($invitationStatus) {
                            case 'accepted':
                                $bgColorClass = 'bg-green-500';
                                $textColorClass = 'text-white';
                                break;
                            case 'pending':
                                $bgColorClass = 'bg-yellow-500';
                                $textColorClass = 'text-grey-500';
                                break;
                            case 'missed':
                                $bgColorClass = 'bg-gray-700';
                                $textColorClass = 'text-white';
                                break;
                            case 'declined':
                                $bgColorClass = 'bg-red-500';
                                $textColorClass = 'text-white';
                                break;
                        }
                    @endphp
                    <div class="flex items-center gap-2 {{ $bgColorClass }} p-1 rounded-full">
                        <div @class([
                            'w-7 h-7 rounded-full bg-gray-200 bg-cover bg-center',
                            'dark:bg-gray-900' => config('filament.dark_mode'),
                        ])
                             style="background-image: url('{{ Filament::getUserAvatarUrl($invitedUser) }}')">
                        </div>
                        <div class="text-sm {{ $textColorClass }}">
                            {{ $invitedUser->name }}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endunless
    @unless (!$meeting_url)
        <div class="flex items-start gap-4 pt-2">
            <x-dynamic-component :component="config('full-google-calendar.pages.buttons.modal.view.meeting_url')" class="w-5 h-5" />
            <a href="{{ $meeting_url }}" target="_blank" class="underline">{{ $meeting_url }}</a>
        </div>
    @endunless
    @unless (!$attachments)
        <filament::hr class="mt-2 mb-2" />
        <div class="grid grid-cols-1 mt-2 border divide-y rounded-md">
            @foreach ($attachments as $attachment)
                <div class="flex justify-between p-2">
                    <div class="flex items-center gap-2">
                        <div>
                            <x-dynamic-component :component="'heroicon-o-paper-clip'" class="w-4 h-4" />
                        </div>
                        <div class="text-sm">
                            {{ $attachment }}
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <filament-support::icon-button :icon="'heroicon-o-download'" :size="'sm'"
                                                       wire:click="loadAttachment('{{ $attachment }}')" />
                    </div>
                </div>
            @endforeach
        </div>
    @endunless

    @if (in_array(auth()->user()->id, $invitedUsers->pluck('id')->toArray()))
        <livewire:invitation-status-actions :event-id="$event->id" />
    @endif
</div>
