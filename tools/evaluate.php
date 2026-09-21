<?php
declare(strict_types=1);

/**
 * Measure segmentation accuracy against an annotated corpus.
 *
 * No corpus ships with this package. Point it at your own copy, and check
 * that corpus's licence before redistributing anything derived from it.
 *
 *   php tools/evaluate.php --corpus=/path/to/LST20_Corpus/eval
 *   php tools/evaluate.php --corpus=/path/to/eval --dict=my-words.txt
 *   php tools/evaluate.php --corpus=/path/to/thai10_conll --format=conll
 */

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/lib/CorpusReader.php';
require __DIR__ . '/lib/Scorer.php';

use PhlongTaIam\Tools\CorpusReader;
use PhlongTaIam\Tools\Scorer;
use PhlongTaIam\WordBreaker;

$options = getopt('', ['corpus:', 'format::', 'dict::', 'limit::', 'top::', 'help']);

if (isset($options['help']) || !isset($options['corpus'])) {
    fwrite(STDERR, <<<TXT
    Usage: php tools/evaluate.php --corpus=DIR [options]

      --corpus=DIR    Corpus file or directory (required)
      --format=NAME   lst20 (default) or conll
      --dict=FILE     Extra dictionary merged on top of data/tdict-std.txt,
                      repeatable as a comma-separated list
      --limit=N       Only read the first N files
      --top=N         How many frequently missed words to list (default 15)

    TXT);
    exit(isset($options['help']) ? 0 : 1);
}

$corpus = (string) $options['corpus'];
$format = (string) ($options['format'] ?? CorpusReader::FORMAT_LST20);
$limit = (int) ($options['limit'] ?? 0);
$top = (int) ($options['top'] ?? 15);

$dictionaries = [__DIR__ . '/../data/tdict-std.txt'];
if (!empty($options['dict'])) {
    foreach (explode(',', (string) $options['dict']) as $path) {
        $path = trim($path);
        if ($path !== '') {
            $dictionaries[] = $path;
        }
    }
}

$wordBreaker = new WordBreaker($dictionaries);
$scorer = new Scorer(array_filter($wordBreaker->dict->prefixes));

$start = microtime(true);
foreach (CorpusReader::read($corpus, $format, $limit) as $goldTokens) {
    $goldTokens = Scorer::mergeSpaceRuns($goldTokens);
    $text = implode('', $goldTokens);
    if ($text === '') {
        continue;
    }
    $scorer->add($goldTokens, $wordBreaker->breakIntoWords($text));
}
$elapsed = microtime(true) - $start;

$r = $scorer->report($top);

printf("corpus     %s (%s)\n", $corpus, $format);
printf("dictionary %s\n", implode(' + ', array_map('basename', $dictionaries)));
printf("read       %s sentences, %s characters in %.1fs\n\n",
    number_format($r['sentences']), number_format($r['characters']), $elapsed);

printf("WORD LEVEL              precision   recall       F1\n");
printf("  including spaces         %.4f   %.4f   %.4f\n", $r['precision'], $r['recall'], $r['f1']);
printf("  excluding spaces         %.4f   %.4f   %.4f\n", $r['precision_no_space'], $r['recall_no_space'], $r['f1_no_space']);
printf("  gold %s, predicted %s, exact matches %s\n\n",
    number_format($r['gold_tokens']), number_format($r['predicted_tokens']), number_format($r['correct_tokens']));

printf("VOCABULARY COVERAGE (gold words, spaces excluded)\n");
printf("  in dictionary   %9s   missed %8s  (%.1f%%)\n",
    number_format($r['in_dict_tokens']), number_format($r['in_dict_missed']),
    100 * $r['in_dict_missed'] / max(1, $r['in_dict_tokens']));
printf("  out of vocab    %9s   missed %8s  (%.1f%%)\n",
    number_format($r['oov_tokens']), number_format($r['oov_missed']),
    100 * $r['oov_missed'] / max(1, $r['oov_tokens']));
printf("  OOV rate %.1f%%, and OOV words are %.1f%% of all missed words\n\n",
    100 * $r['oov_rate'], 100 * $r['oov_share_of_errors']);

if ($r['top_missed']) {
    printf("MOST FREQUENTLY MISSED GOLD WORDS\n");
    foreach ($r['top_missed'] as $word => $count) {
        printf("  %-28s %6d   %s\n", $word, $count,
            isset($wordBreaker->dict->prefixes[$word]) && $wordBreaker->dict->prefixes[$word] ? 'in dictionary' : 'OOV');
    }
}
