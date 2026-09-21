PhlongTaIam
===========

PHP Thai word breaker

This is a maintained fork of [veer66/PhlongTaIam](https://github.com/veer66/PhlongTaIam),
which is no longer actively developed. Install and require this package
as `kamthorn/phlongtaiam`.

Requirement
-----------
* PHP 8.2+ (tested on 8.2 - 8.5)

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
into a location your web server can reach and requiring `src/autoload.php`,
which registers a small autoloader for the `PhlongTaIam\` namespace - see
`example/brk.php` and `example/demo.php`.

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

### PDF export: wrapping unspaced Thai text

Thai script has no spaces between words. HTML-to-PDF renderers (dompdf,
mPDF, wkhtmltopdf/Snappy, ...) rely on spaces to know where a line can
break, so a long run of Thai text is treated as a single unbreakable
"word" and either overflows its container or gets cut off instead of
wrapping.

`WordBreaker::insertWordBreaks()` (exposed in Blade as `@thaiwordwrap`)
fixes this by segmenting the text and re-joining it with an invisible
zero-width space (U+200B) between words - the text reads exactly the
same, but the renderer now has real line-break opportunities.

Using [barryvdh/laravel-dompdf](https://github.com/barryvdh/laravel-dompdf)
as an example:

```php
// app/Http/Controllers/InvoiceController.php
use Barryvdh\DomPDF\Facade\Pdf;

public function download(Invoice $invoice)
{
    return Pdf::loadView('invoices.pdf', ['invoice' => $invoice])
        ->download("invoice-{$invoice->id}.pdf");
}
```

```blade
{{-- resources/views/invoices/pdf.blade.php --}}
<style>
    /* Belt-and-suspenders: let the renderer break even without U+200B. */
    .description { overflow-wrap: break-word; }
</style>

<p class="description">@thaiwordwrap($invoice->description)</p>
```

`@thaiwordwrap` escapes the value for you (like `{{ }}` does), so pass
the raw, unescaped text - do not combine it with `{{ }}` or `e()`.

Word list
---------
Word lists were taken from [LibThai](http://linux.thai.net/projects/libthai)

Testing
-------
```bash
composer install
vendor/bin/phpunit
```

License
-------
GNU Lesser General Public License v2.1 (LGPL-2.1-only) - see [LICENSE](LICENSE).

Demo
----
A local, standalone HTML demo is included at `example/demo.php` (see Usage
above for how to run it without Composer).
