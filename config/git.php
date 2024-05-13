<?php

return [
    'remote' => env('GIT_REMOTE'),
    'remote_branch' => env('GIT_REMOTE_BRANCH') ?? 'master',
    'local' => env('GIT_LOCAL'),
    'user_name' => env('GIT_USER_NAME'),
    'user_email' => env('GIT_USER_EMAIL'),
    'metadata_base_url' => rtrim(env('METADATA_BASE_URL'), '/'),
    'edugain_tag' => env('GIT_EDUGAIN_TAG'),
    'edugain_cfg' => env('GIT_EDUGAIN_CFG'),
    'hfd' => env('GIT_HFD_TAG'),
    'ec_rs' => env('GIT_EC_RS'),
    'ec_esi' => env('GIT_EC_ESI'),
    'reginfo' => 'eduid.registration',
    'rs_federation' => env('GIT_RS_FEDERATION', 'ratamahatta'),
];
