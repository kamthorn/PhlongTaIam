PhlongTaIam
===========

PHP Thai word breaker

This is a maintained fork of [veer66/PhlongTaIam](https://github.com/veer66/PhlongTaIam),
which is no longer actively developed. Install and require this package
as `kamthorn/phlongtaiam`.

Requirement
-----------
* PHP 8.1+

Installation
------------
```bash
composer require kamthorn/phlongtaiam
```

Usage
-----
```php
require __DIR__ . '/vendor/autoload.php';

use PhlongTaIam\WordBreaker;

$wordBreaker = new WordBreaker(__DIR__ . '/vendor/kamthorn/phlongtaiam/data/tdict-std.txt');

foreach ($wordBreaker->breakIntoWords('ฉันกินข้าวชิมิ') as $word) {
    echo $word . "\n";
}
```

`breakIntoWords()` returns the tokens (words, whitespace runs, and unknown
character runs) found in the text, in order:

* ฉัน
* กิน
* ข้าว
* ชิ
* มิ

If you need character offsets instead of text, use `breakIntoRanges()`,
which returns `[['s' => start, 'e' => end], ...]` (start inclusive, end
exclusive, in UTF-8 character counts).

You can also run the package without Composer by copying `src/` and `data/`
into a location your web server can reach and `require`-ing
`src/WordBreaker.php` directly - see `example/brk.php` and
`example/demo.php`.

Laravel
-------
The package ships a service provider that Laravel auto-discovers - no
manual registration needed after `composer require`. It binds
`PhlongTaIam\WordBreaker` as a singleton, so the dictionary (~16,000
entries) is parsed once per application boot rather than once per
resolution.

```php
use PhlongTaIam\WordBreaker;

class SearchController
{
    public function __construct(private WordBreaker $wordBreaker) {}

    public function index(Request $request)
    {
        $tokens = $this->wordBreaker->breakIntoWords($request->string('q'));
        // ...
    }
}
```

Or via the facade:

```php
use PhlongTaIam\Laravel\Facades\PhlongTaIam;

$tokens = PhlongTaIam::breakIntoWords('ฉันกินข้าว');
```

To use a different dictionary, publish the config and set
`dictionary_path` (or the `PHLONGTAIAM_DICTIONARY_PATH` env var):

```bash
php artisan vendor:publish --tag=phlongtaiam-config
```

Word list
---------
Word lists were taken from [LibThai](http://linux.thai.net/projects/libthai)

Testing
-------
```bash
composer install
vendor/bin/phpunit
```

Demo
----
A local, standalone HTML demo is included at `example/demo.php` (see Usage
above for how to run it without Composer).
