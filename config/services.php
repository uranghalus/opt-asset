<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

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

    'saml2' => [
        'metadata'    => null,
        'entityid'    => env('SAML_IDP_ENTITYID'),
        'certificate' => env('SAML_X509_CERT'),
        'acs'         => env('SAML_SSO_URL'),
        'slo'         => env('SAML_SLO_URL'),
        'sp_entityid' => env('SAML_SP_ENTITYID'),
        'sp_acs'      => 'saml/acs',
        'sp_sls'      => 'saml/logout',
        'sp_default_binding_method' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
    ],

];
