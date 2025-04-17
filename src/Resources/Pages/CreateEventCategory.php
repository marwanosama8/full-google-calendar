<?php

namespace Marwanosama8\FullGoogleCalendar\Resources\Pages;

use Marwanosama8\FullGoogleCalendar\Resources\EventCategoryResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;
class CreateEventCategory extends CreateRecord
{
    protected static string $resource = EventCategoryResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['id'] = Str::uuid();

        return $data;
    }


}
