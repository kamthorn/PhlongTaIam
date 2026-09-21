<?php
declare(strict_types=1);

/**
 * Build a dictionary from an annotated corpus.
 *
 * Accuracy is dominated by whether the dictionary knows the words your text
 * actually uses, so the most effective dictionary is one built from text of
 * the same kind. Feed the result to WordBreaker as an extra dictionary:
 *
 *   new WordBreaker([$standardList, 'my-words.txt'])
 *
 * No corpus ships with this package, and whatever you build from one is
 * derived from it - check that corpus's licence before redistributing.
 *
 *   php tools/build-dictionary.php --corpus=/path/to/LST20_Corpus/train \
 *       --min-freq=5 --exclude=data/tdict.txt --out=my-words.txt
 */

require __DIR__ . '/lib/CorpusReader.php';

use PhlongTaIam\Tools\CorpusReader;

$options = getopt('', ['corpus:', 'format::', 'min-freq::', 'exclude::', 'out::', 'limit::', 'with-frequency', 'help']);

if (isset($options['help']) || !isset($options['corpus'])) {
    fwrite(STDERR, <<<TXT
    Usage: php tools/build-dictionary.php --corpus=DIR [options]

      --corpus=DIR    Corpus file or directory (required)
      --format=NAME   lst20 (default) or conll
      --min-freq=N    Keep words seen at least N times (default 5). Rare words
                      add ambiguity without adding coverage, so raising this
                      often scores better than keeping everything.
      --exclude=FILE  Leave out words this dictionary already has, so the
                      output is a supplement rather than a replacement
      --out=FILE      Where to write (default: stdout)
      --limit=N       Only read the first N files
      --with-frequency
                      Write "word<TAB>count" instead of just the word. The
                      counts let WordBreaker prefer the likelier reading of an
                      ambiguous span; without them all known words are equal.

    TXT);
    exit(isset($options['help']) ? 0 : 1);
}

$corpus = (string) $options['corpus'];
$format = (string) ($options['format'] ?? CorpusReader::FORMAT_LST20);
$minFreq = (int) ($options['min-freq'] ?? 5);
$limit = (int) ($options['limit'] ?? 0);
$out = isset($options['out']) ? (string) $options['out'] : null;

$exclude = [];
if (!empty($options['exclude'])) {
    foreach (explode(',', (string) $options['exclude']) as $path) {
        $path = trim($path);
        if ($path === '') {
            continue;
        }
        $contents = @file_get_contents($path);
        if ($contents === false) {
            fwrite(STDERR, "Cannot read dictionary to exclude: $path\n");
            exit(1);
        }
        foreach (explode("\n", $contents) as $word) {
            $word = trim($word);
            if ($word !== '') {
                $exclude[$word] = true;
            }
        }
    }
}

$frequency = [];
$tokenCount = 0;
$sentenceCount = 0;
foreach (CorpusReader::read($corpus, $format, $limit) as $tokens) {
    $sentenceCount++;
    foreach ($tokens as $token) {
        $tokenCount++;
        // A word containing whitespace could never be matched anyway: the
        // space rule ends a token at the space.
        if ($token === '' || preg_match('/\s/u', $token)) {
            continue;
        }
        $frequency[$token] = ($frequency[$token] ?? 0) + 1;
    }
}

$kept = [];
$droppedRare = 0;
$droppedKnown = 0;
foreach ($frequency as $word => $count) {
    if ($count < $minFreq) {
        $droppedRare++;
        continue;
    }
    if (isset($exclude[$word])) {
        $droppedKnown++;
        continue;
    }
    $kept[] = $word;
}
sort($kept, SORT_STRING);

$withFrequency = isset($options['with-frequency']);

if ($withFrequency && $exclude) {
    fwrite(STDERR,
        "warning: --exclude drops the words the other dictionary already has,\n"
        . "  which are the commonest ones, so the counts you are writing cover\n"
        . "  everything except them. The weighting then treats those common\n"
        . "  words as if they were rare. Build the counted list without\n"
        . "  --exclude and let the dictionaries overlap instead.\n");
}
$lines = $withFrequency
    ? array_map(static fn(string $w): string => $w . "\t" . $frequency[$w], $kept)
    : $kept;

$body = $lines ? implode("\n", $lines) . "\n" : '';
if ($out === null) {
    echo $body;
} elseif (file_put_contents($out, $body) === false) {
    fwrite(STDERR, "Cannot write to $out\n");
    exit(1);
}

fwrite(STDERR, sprintf(
    "read %s sentences, %s tokens, %s distinct words\n"
    . "dropped %s seen fewer than %d times, %s already in the excluded dictionaries\n"
    . "wrote %s words%s\n",
    number_format($sentenceCount),
    number_format($tokenCount),
    number_format(count($frequency)),
    number_format($droppedRare),
    $minFreq,
    number_format($droppedKnown),
    number_format(count($kept)),
    $out === null ? '' : " to $out"
));
