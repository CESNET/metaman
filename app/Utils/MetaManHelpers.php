<?php

if (! function_exists('generateFederationID')) {
    function generateFederationID(string $name): string
    {
        setlocale(LC_ALL, 'en_US.utf8');

        $ID = iconv('UTF-8', 'ASCII//TRANSLIT', $name);
        $ID = preg_replace('/\s+/', '-', $ID);              // Replace white spaces with a dash.
        $ID = preg_replace('/[^A-Za-z0-9-_]/', '', $ID);    // Drop everything except A-Z, a-z, 0-9, _ and -.
        $ID = preg_replace('/_{2,}/', '_', $ID);            // Replace two (or more) underscores with just one.
        $ID = preg_replace('/-{2,}/', '-', $ID);            // Replace two (or more) dashes with just one.
        $ID = strtolower($ID);

        return $ID;
    }
}
