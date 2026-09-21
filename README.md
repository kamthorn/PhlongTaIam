PhlongTaIam
===========

PHP Thai word breaker

This is a maintained fork of [veer66/PhlongTaIam](https://github.com/veer66/PhlongTaIam),
which is no longer actively developed. Install and require this package
as `kamthorn/phlongtaiam`; [CHANGELOG.md](CHANGELOG.md) lists what to
watch out for when moving over from the original.

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

To teach it words the standard list does not know - product names, place
names, jargon - pass several dictionary files and they are merged. A
dictionary is a plain UTF-8 file with one word per line, in any order:

```php
$wordBreaker = new WordBreaker([
    __DIR__ . '/vendor/kamthorn/phlongtaiam/data/tdict-std.txt',
    __DIR__ . '/dictionaries/my-products.txt',
]);
```

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

To use a different dictionary, or to add your own words on top of the
standard list, publish the config:

```bash
php artisan vendor:publish --tag=phlongtaiam-config
```

```php
// config/phlongtaiam.php
return [
    // null uses the dictionary bundled with the package
    'dictionary_path' => env('PHLONGTAIAM_DICTIONARY_PATH'),

    'additional_dictionaries' => [
        resource_path('dictionaries/products.txt'),
    ],
];
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

Accuracy
--------
How well this segments your text depends almost entirely on whether the
dictionary knows the words in it. Measured against the LST20 corpus, words
the dictionary knows are segmented correctly 99% of the time, while **98% of
all errors are words it has never seen**. The bundled `tdict-std.txt` holds
base words only - it has `โรง` and `เรียน`, but not `โรงเรียน`.

So the way to improve accuracy is to add the vocabulary of the text you
actually process. Two tools in the repository (not shipped in the package)
help with that. They read an annotated corpus in LST20 or CoNLL format; no
corpus is included, point them at your own copy.

```bash
# What is my accuracy right now?
php tools/evaluate.php --corpus=/path/to/LST20_Corpus/eval

# Build a dictionary from text of the same kind, then measure again
php tools/build-dictionary.php --corpus=/path/to/LST20_Corpus/train \
    --min-freq=5 --exclude=data/tdict-std.txt --out=my-words.txt
php tools/evaluate.php --corpus=/path/to/LST20_Corpus/eval --dict=my-words.txt
```

In that example the 8,000 words this adds take word-level F1 from **0.67 to
0.90**. Load the result alongside the standard list:

```php
$wordBreaker = new WordBreaker([$standardList, 'my-words.txt']);
```

More words is not automatically better. Where a span can be read several
ways, a plain dictionary has nothing to choose with, so rare words mostly add
ambiguity: keeping every word seen even once scored *worse* than keeping only
those seen five times or more. `--min-freq` is worth tuning on your own data,
especially for text without spaces, where there is more ambiguity to get
wrong.

### Weighting words by how common they are

`--with-frequency` writes `word<TAB>count` instead of bare words. Given
counts, the segmenter prefers the likelier reading of an ambiguous span
instead of applying fixed rules, and rare words stop being a liability:

```bash
php tools/build-dictionary.php --corpus=/path/to/train --min-freq=5 \
    --with-frequency --out=my-words.txt
```

Measured on the LST20 test split and on a section of the Blackboard
Treebank, with a dictionary built from LST20's train split:

| dictionary            | LST20 test | Blackboard |
| --------------------- | ---------- | ---------- |
| bundled list only     | 0.743      | 0.694      |
| + words, no counts    | 0.922      | 0.947      |
| + words with counts   | **0.929**  | **0.952**  |

Counts help at every vocabulary size, so use them if you have them. Build the
counted list *without* `--exclude`: that flag removes the words the other
dictionary already has, which are the commonest ones, leaving the weighting
to treat them as rare. Let the two dictionaries overlap instead.

A counted list of 12,000 words costs about 5MB of memory over the bundled
one, and segmentation runs at the same speed either way. Nothing changes for
a dictionary without counts - the original selection rules still apply.

A dictionary built this way is derived from that corpus, so check the
corpus's licence before redistributing it. Research corpora frequently allow
use but not redistribution.

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
