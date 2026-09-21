Changelog
=========

All notable changes to this package. This project follows
[semantic versioning](https://semver.org/).

Unreleased
----------

### Added

* Dictionaries can carry corpus counts as `word<TAB>count`. When they do, the
  segmenter scores candidate readings by how likely their words are instead of
  by fixed counting rules, which is what lets a large vocabulary help rather
  than hurt. Measured against LST20's test split with a dictionary built from
  its train split, F1 goes from 0.922 without counts to 0.929 with them, and
  from 0.947 to 0.952 on the Blackboard Treebank. A dictionary without counts
  behaves exactly as before.
* `tools/evaluate.php` and `tools/build-dictionary.php`: measure segmentation
  accuracy against an annotated corpus, and turn one into a dictionary.
  `--with-frequency` writes the counts above. No corpus ships with this
  package and neither tool is published with it.

2.0.0
-----

First release of the maintained fork. The version continues from
`veer66/phlongtaiam` 1.0.3, the last release of the original package, and
the major bump reflects that code written against it may need changes.

### Migrating from veer66/phlongtaiam 1.0.3

* **PHP 8.2 or newer is required** (was 5.3). Tested on 8.2 - 8.5.
* **The declared license is now LGPL-2.1-only.** The code has always
  shipped with the LGPL v2.1 text in `LICENSE`, but `composer.json`
  claimed MIT. The metadata was wrong, not the license - check that LGPL
  suits your project before upgrading.
* `require`-ing `src/WordBreaker.php` by hand no longer pulls in the rest
  of the classes. Use Composer's autoloader, or `src/autoload.php` if you
  are not using Composer.
* `src/LatinRules.php` is gone; its six classes now live in one file each,
  as PSR-4 requires.
* `Dict` no longer exposes `dictSeek()`, `isFinal()` or the `$dict` array
  of words. The dictionary is a prefix map now; `Dict::lookup()` replaces
  them, and `array_keys(array_filter($dict->prefixes))` returns the words.
* `WordBreaker::buildPath()` and `rangesToTextList()` take the text as an
  array of characters rather than a string. `breakIntoWords()` and
  `breakIntoRanges()` are unchanged and still take a string.
* Segmentation output changed wherever the fixes below applied.

### Added

* Laravel support: an auto-discovered service provider that binds
  `WordBreaker` as a singleton, a publishable config file, and a
  `PhlongTaIam` facade.
* `WordBreaker::insertWordBreaks()` and the `@thaiwordwrap` Blade
  directive, which insert zero-width spaces between words so HTML-to-PDF
  renderers can wrap unspaced Thai text instead of overflowing.
* Several dictionaries can be merged: `new WordBreaker([$std, $mine])`,
  `Dict::addDict()`, `Dict::addWord()`, and the
  `additional_dictionaries` config key.
* A test suite (44 tests) and CI across PHP 8.2 - 8.5.

### Fixed

* **Reflected XSS in `example/demo.php`**: the submitted text and every
  segmented word were echoed into the page unescaped.
* **Every character was treated as a single symbol.** `SingleSymbolRule`
  tested `mb_strpos(...) >= 0`, which is true even when the character is
  absent, because `false >= 0` compares as equal in PHP.
* **Unknown characters swallowed the end of the preceding word.** The
  left-boundary index was off by one, so with the fix above in place
  `"กิน5"` came out as `กิ` + `น5` instead of `กิน` + `5`.
* **`[`, `\`, `]`, `^`, `_` and `` ` `` counted as letters**, because the
  Latin word rule tested `"A" <= $ch <= "z"` and ASCII puts them between
  the two letter ranges. `"a_b"` was one word; it is now `a`, `_`, `b`.
* **Results depended on what had been segmented before.** Rule tags were
  not cleared between runs, so a reused instance - which is exactly what
  the Laravel singleton is - could return `["e", "at"]` for `"eat"` after
  segmenting `"cat"`.
* **A blank line in a dictionary hid every word after it**, because
  filtering the word list left holes in the array the binary search
  indexed into.
* Dictionary words are trimmed of a trailing carriage return, so a
  dictionary saved on Windows no longer fails to match every entry.
* Dead code in `PathInfoBuilder` that would have raised
  `Error: Undefined constant "j"` on PHP 8 had anything reached it.

### Performance

Segmenting 22,000 characters went from 1.16s to 0.047s (about 24x):

* `buildPath()` and `rangesToTextList()` called `mb_substr()` on the whole
  input once per character and once per word. Since `mb_substr` rescans
  from the start of the string, both were effectively O(n^2). The text is
  split into characters once now.
* Word lookup was a binary search over a sorted array, at 36 steps per
  character with an `mb_strlen` and `mb_substr` in each. It is a prefix
  map now: one hash lookup per character. This costs about 6ms more to
  load and 3MB more memory per instance, and means **the dictionary no
  longer has to be sorted**.
