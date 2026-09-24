<?php

/**
 * Application policy in one file. Nothing security-relevant is a magic
 * number buried in a class; every knob below is read from the environment
 * so a deployment can tighten it without a code change.
 */
return [
    'doc_disk'          => env('SLDWP_DOC_DISK', 'local'),
    'mfa_ttl'           => (int) env('SLDWP_MFA_TTL', 300),
    'mfa_max_attempts'  => (int) env('SLDWP_MFA_MAX_ATTEMPTS', 5),
    'password_min'      => (int) env('SLDWP_PASSWORD_MIN', 12),
    'password_history'  => (int) env('SLDWP_PASSWORD_HISTORY', 5),
    'login_throttle'    => (int) env('SLDWP_LOGIN_THROTTLE', 5),
    'classifications'   => ['public', 'internal', 'restricted'],
];
