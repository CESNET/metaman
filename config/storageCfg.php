<?php

return [
    'name' => 'metadata',
    'mdaConfigFolder' => env('MDA_CONFIG_FOLDER', '/opt/mda/config'),
    'mdaScript' => env('MDA_SCRIPT', '/opt/mda/mda.sh'),
    'edu2edugain' => env('EDU_TO_EDUGAIN_FOLDER', 'eduid2edugain'),
];
