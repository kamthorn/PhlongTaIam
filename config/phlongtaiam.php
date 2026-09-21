<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Dictionary path
    |--------------------------------------------------------------------------
    |
    | Path to the newline-separated, UTF-8, lexicographically sorted
    | dictionary file used by PhlongTaIam\WordBreaker. Leave as null to use
    | the dictionary bundled with the package (data/tdict.txt).
    |
    */

    'dictionary_path' => env('PHLONGTAIAM_DICTIONARY_PATH'),

    /*
    |--------------------------------------------------------------------------
    | Additional dictionaries
    |--------------------------------------------------------------------------
    |
    | Paths to extra dictionary files merged on top of the one above, for
    | words the standard list does not know: product names, place names,
    | domain jargon. Same format - one word per line, UTF-8, any order.
    |
    | 'additional_dictionaries' => [resource_path('dictionaries/products.txt')],
    |
    */

    'additional_dictionaries' => [],

];
