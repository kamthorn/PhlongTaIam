<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Dictionary path
    |--------------------------------------------------------------------------
    |
    | Path to the newline-separated, UTF-8, lexicographically sorted
    | dictionary file used by PhlongTaIam\WordBreaker. Leave as null to use
    | the dictionary bundled with the package (data/tdict-std.txt).
    |
    */

    'dictionary_path' => env('PHLONGTAIAM_DICTIONARY_PATH'),

];
