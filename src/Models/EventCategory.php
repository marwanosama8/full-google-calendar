<?php

namespace Marwanosama8\FullGoogleCalendar\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class EventCategory extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $fillable = [
        'id',
        'icon',
        'color',
        'default',
        'tags',
        'value',
        'show_in_overview',
    ];

    protected $casts = [
        'id'   => 'string',
        'tags' => 'array',
    ];

    public function scopeShowInOverview(Builder $query): void
    {
        $query->where('show_in_overview', 1);
    }

    public function events()
    {
        return $this->hasMany(Event::class, 'category', 'id');
    }
}
