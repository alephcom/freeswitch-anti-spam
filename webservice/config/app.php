<?php

return [

    'token' => env('APP_TOKEN',''), // token to access the admin url

    /*
     |--------------------------------------------------------------------------
     | General Blocking Rules
     |--------------------------------------------------------------------------
     |
    */
     'override' => env('APP_OVERRIDE',"45678"), //5 digit override code.
     'max_attempts' => env('MAX_ATTEMPTS', 3),
     'banned_days' => env('BANNED_DAYS', 30),
     'track_attempts_days' => env( 'TRACK_ATTEMPTS_DAYS',7),
     'whitelisted_days' => env('WHITELISTED_DAYS',30),

    /*
    |--------------------------------------------------------------------------
    | Default Audio Files
    |--------------------------------------------------------------------------
    |
    */
    'audio_file' => [
        'default_first' => env('AUDIO_FILE_DEFAULT_FIRST',"We have enabled protection on this line."),
        'press_one' => env('AUDIO_FILE_PRESS_ONE',"Press one to continue."),
        'press_two' => env('AUDIO_FILE_PRESS_ONE',"Press two to continue."),
        'press_three' => env('AUDIO_FILE_PRESS_ONE',"Press three to continue."),
        'press_four' => env('AUDIO_FILE_PRESS_ONE',"Press four to continue."),
        'press_five' => env('AUDIO_FILE_PRESS_ONE',"Press five to continue."),
        'press_six' => env('AUDIO_FILE_PRESS_ONE',"Press six to continue."),
        'press_seven' => env('AUDIO_FILE_PRESS_ONE',"Press seven to continue."),
        'did_not_receive_response' => env('AUDIO_FILE_DID_NOT_RECEIVE_RESPONSE',"We did not receive your response."),
        'temporarily_banned' => env('AUDIO_FILE_TEMPORARILY_BANNED', "Your number has been temporarily banned from this system.  Goodbye."),
        'temporarily_banned_override' => env('AUDIO_FILE_TEMPORARILY_BANNED_OVERRIDE',
            "Your number has been temporarily banned from this system.  Please enter your override code now.  Otherwise please hang up."
        ),
    ],

     /*
     |--------------------------------------------------------------------------
     | Application Name
     |--------------------------------------------------------------------------
     |
     | This value is the name of your application, which will be used when the
     | framework needs to place the application's name in a notification or
     | other UI elements where an application name needs to be displayed.
     |
     */

    'name' => env('APP_NAME', 'Laravel'),

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    |
    | This value determines the "environment" your application is currently
    | running in. This may determine how you prefer to configure various
    | services the application utilizes. Set this in your ".env" file.
    |
    */

    'env' => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    |
    | When your application is in debug mode, detailed error messages with
    | stack traces will be shown on every error that occurs within your
    | application. If disabled, a simple generic error page is shown.
    |
    */

    'debug' => (bool) env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    |
    | This URL is used by the console to properly generate URLs when using
    | the Artisan command line tool. You should set this to the root of
    | the application so that it's available within Artisan commands.
    |
    */

    'url' => env('APP_URL', 'http://localhost'),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default timezone for your application, which
    | will be used by the PHP date and date-time functions. The timezone
    | is set to "UTC" by default as it is suitable for most use cases.
    |
    */

    'timezone' => env('APP_TIMEZONE', 'UTC'),

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    |
    | The application locale determines the default locale that will be used
    | by Laravel's translation / localization methods. This option can be
    | set to any locale for which you plan to have translation strings.
    |
    */

    'locale' => env('APP_LOCALE', 'en'),

    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),

    'faker_locale' => env('APP_FAKER_LOCALE', 'en_US'),

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is utilized by Laravel's encryption services and should be set
    | to a random, 32 character string to ensure that all encrypted values
    | are secure. You should do this prior to deploying the application.
    |
    */

    'cipher' => 'AES-256-CBC',

    'key' => env('APP_KEY'),

    'previous_keys' => [
        ...array_filter(
            explode(',', env('APP_PREVIOUS_KEYS', ''))
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Maintenance Mode Driver
    |--------------------------------------------------------------------------
    |
    | These configuration options determine the driver used to determine and
    | manage Laravel's "maintenance mode" status. The "cache" driver will
    | allow maintenance mode to be controlled across multiple machines.
    |
    | Supported drivers: "file", "cache"
    |
    */

    'maintenance' => [
        'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),
        'store' => env('APP_MAINTENANCE_STORE', 'database'),
    ],

];
