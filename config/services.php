<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'ghasedak' => [
        'api_key' => env('GHASEDAK_API_KEY'),
        'line_number' => env('GHASEDAK_LINE_NUMBER'),
        'url' => env('GHASEDAK_URL', 'https://api.ghasedak.io/v1/SendSimple'),
        'appointment_confirmation_template' => env('GHASEDAK_APPOINTMENT_CONFIRMATION_TEMPLATE', 'AppointmentConfirmationSMS'),
        'appointment_reminder_template' => env('GHASEDAK_APPOINTMENT_REMINDER_TEMPLATE'),
        'appointment_update_template' => env('GHASEDAK_APPOINTMENT_UPDATE_TEMPLATE'),
        'appointment_cancellation_template' => env('GHASEDAK_APPOINTMENT_CANCELLATION_TEMPLATE'),
        'patient_follow_up_template' => env('GHASEDAK_PATIENT_FOLLOW_UP_TEMPLATE'),
        'installment_reminder_template' => env('GHASEDAK_INSTALLMENT_REMINDER_TEMPLATE'),
    ],
    'sep' => [
        'terminal_id' => env('SEP_TERMINAL_ID'),
        'token_url' => env('SEP_TOKEN_URL', 'https://sep.shaparak.ir/OnlinePG/OnlinePG'),
        'verify_url' => env('SEP_VERIFY_URL', 'https://sep.shaparak.ir/verifyTxnRandomSessionkey/ipg/VerifyTransaction'),
        'reverse_url' => env('SEP_REVERSE_URL', 'https://sep.shaparak.ir/verifyTxnRandomSessionkey/ipg/ReverseTransaction'),
        'ocp_url' => env('SEP_OCP_URL'),
        'callback_url' => env('SEP_CALLBACK_URL'),
    ],

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
