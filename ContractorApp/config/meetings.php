<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Morning meeting summary
    |--------------------------------------------------------------------------
    |
    | Every contractor user with a registered device receives one push per
    | local day listing the meetings scheduled for that day. The job runs in
    | the app timezone (config/app.php); "hour" is the local hour it fires.
    |
    */

    'morning_summary' => [
        'hour' => (int) env('MEETINGS_SUMMARY_HOUR', 6),

        // Send "No meetings scheduled for today." when a user has nothing on.
        'notify_when_empty' => (bool) env('MEETINGS_SUMMARY_NOTIFY_WHEN_EMPTY', false),
    ],

];
