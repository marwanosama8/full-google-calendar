<?php

return [
    'calendar_name' => env('GOOGLE_CALENDAR_NAME', env('APP_NAME')),
    'scope' => 'https://www.googleapis.com/auth/calendar',
    // 'credntials_json' => env('GOOGLE_CREDENTIALS_PATH'), // you can store the credintiols in json file and just declare tha path, ex. : ./storage/app/google_credentiols/filename.json
    'credntials_json' => json_decode(env('GOOGLE_CREDENTIALS_JSON'), true),
    'access_type' => 'offline',
    'default_category' => 'Service', // the default event category name in database 

    'pages' => [
        'buttons' => [
            'hideYearNavigation' => false,
            'today' => [
                'static' => false,
                'format' => 'D MMM'
            ],
            'outlined' => true,
            'icons' => [
                'previousYear' => 'heroicon-o-chevron-double-left',
                'nextYear' => 'heroicon-o-chevron-double-right',
                'previousMonth' => 'heroicon-o-chevron-left',
                'nextMonth' => 'heroicon-o-chevron-right',
                'createEvent' => 'heroicon-o-plus'
            ],
            'modal' => [
                'submit' => [
                    'outlined' => false,
                    'color' => 'primary',
                    'icon' => [
                        'enabled' => true,
                        'name' => 'heroicon-o-save'
                    ],
                ],
                'cancel' => [
                    'outlined' => false,
                    'color' => 'secondary',
                    'icon' => [
                        'enabled' => true,
                        'name' => 'heroicon-o-x-circle'
                    ],
                ],
                'delete' => [
                    'outlined' => false,
                    'color' => 'danger',
                    'icon' => [
                        'enabled' => true,
                        'name' => 'heroicon-o-trash'
                    ],
                ],
                'edit' => [
                    'outlined' => false,
                    'color' => 'primary',
                    'icon' => [
                        'enabled' => true,
                        'name' => 'heroicon-o-pencil-alt'
                    ],
                ],
                'view' => [
                    'time' => 'heroicon-o-clock',
                    'category' => 'heroicon-o-tag',
                    'body' => 'heroicon-o-annotation',
                    'participants' => 'heroicon-o-user-group',
                    'meeting_url' => 'heroicon-o-link',
                    'event_leader' => 'heroicon-o-star',
                    'event_organizer' => 'heroicon-o-user-circle'
                ],
            ],
        ],
    ],

    'tables' => [
        'event' => [
            'name' => 'timex_events',
        ],
        'category' => [
            'name' => 'timex_categories',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | TIMEX Event categories
    |--------------------------------------------------------------------------
    |
    | Categories names are used to define colors & icons.
    | Each represents default tailwind colors.
    | You may change as you wish, just make sure your color have -500 / -600 and etc variants
    | You may also go for a custom Category model to define your labels, colors and icons
    |
    */

    'categories' => [

        /*
        |--------------------------------------------------------------------------
        | Default TiMEX Categories
        |--------------------------------------------------------------------------
        */
        'labels' => [
            'T1' => 'T1',
            'T2' => 'T2',
            'T3' => 'T3',
            'Service' => 'Service',
            'Kickoff' => 'Kickoff',
            'Karrieregespräch' => 'Karrieregespräch',
            'Impulsgespräch' => 'Impulsgespräch',
            'Online' => 'Online',
            'Offline' => 'Offline'
        ],
        'icons' => [
            'T1' => 'heroicon-o-clipboard',
            'T2' => 'heroicon-o-bookmark',
            'T3' => 'heroicon-o-flag',
            'Service' => 'heroicon-o-badge-check',
            'Kickoff' => 'heroicon-o-calendar',
            'Karrieregespräch' => 'heroicon-o-user-group',
            'Impulsgespräch' => 'heroicon-o-light-bulb',
            'Online' => 'heroicon-o-wifi',
            'Offline' => 'heroicon-o-cloud-slash',
        ],
        'colors' => [
            'T1' => 'blue',
            'T2' => 'green',
            'T3' => 'red',
            'Service' => 'yellow',
            'Kickoff' => 'indigo',
            'Karrieregespräch' => 'pink',
            'Impulsgespräch' => 'teal',
            'Online' => 'purple',
            'Offline' => 'gray',
        ],
    ],
];