<?php
declare(strict_types=1);

/**
 * What a dictionary costs: load time, memory, and segmentation speed.
 *
 * Memory is only meaningful in a fresh process, so this benchmarks one
 * dictionary set per run. Compare by running it once per set:
 *
 *   php tools/benchmark.php
 *   php tools/benchmark.php --dict=data/tdict-std.txt --no-default
 *   php tools/benchmark.php --dict=my-words.txt
 */

require __DIR__ . '/../vendor/autoload.php';

use PhlongTaIam\WordBreaker;

$options = getopt('', ['dict::', 'no-default', 'chars::', 'repeat::', 'text::', 'help']);

if (isset($options['help'])) {
    fwrite(STDERR, <<<TXT
    Usage: php tools/benchmark.php [options]

      --dict=FILE     Dictionary to measure, comma-separated for several.
                      Merged on top of data/tdict.txt unless --no-default.
      --no-default    Do not include the bundled dictionary
      --text=FILE     Text to segment (default: a built-in Thai sample)
      --chars=N       Grow the text to about N characters (default 100000)
      --repeat=N      Segmentation passes to average over (default 3)

    TXT);
    exit(0);
}

$dictionaries = isset($options['no-default']) ? [] : [__DIR__ . '/../data/tdict.txt'];
if (!empty($options['dict'])) {
    foreach (explode(',', (string) $options['dict']) as $path) {
        $path = trim($path);
        if ($path !== '') {
            $dictionaries[] = $path;
        }
    }
}
if (!$dictionaries) {
    fwrite(STDERR, "Nothing to measure: --no-default needs at least one --dict\n");
    exit(1);
}

$targetChars = (int) ($options['chars'] ?? 100000);
$repeat = max(1, (int) ($options['repeat'] ?? 3));

if (!empty($options['text'])) {
    $sample = @file_get_contents((string) $options['text']);
    if ($sample === false) {
        fwrite(STDERR, "Cannot read text file: {$options['text']}\n");
        exit(1);
    }
} else {
    // Synthetic, so the numbers are reproducible; pass --text for your own.
    $sample = 'การให้คำแนะนำในเรื่องของจุดแข็ง จุดอ่อน และโอกาสในการปรับปรุงให้ดีขึ้น '
        . 'นักเรียนโรงเรียนแห่งหนึ่งเดินทางไปทัศนศึกษาที่จังหวัดเชียงใหม่เมื่อวันที่ 15 มีนาคม 2567 ';
}
$text = '';
while (mb_strlen($text, 'UTF-8') < $targetChars) {
    $text .= $sample;
}
$characters = mb_strlen($text, 'UTF-8');

$baseline = memory_get_usage();
$loadStart = microtime(true);
$wordBreaker = new WordBreaker($dictionaries);
$loadTime = microtime(true) - $loadStart;
$dictionaryMemory = memory_get_usage() - $baseline;

$words = count(array_filter($wordBreaker->dict->prefixes));
$prefixes = count($wordBreaker->dict->prefixes);

$best = INF;
$total = 0.0;
for ($i = 0; $i < $repeat; $i++) {
    $start = microtime(true);
    $tokens = $wordBreaker->breakIntoWords($text);
    $elapsed = microtime(true) - $start;
    $best = min($best, $elapsed);
    $total += $elapsed;
}
$average = $total / $repeat;

printf("dictionary    %s\n", implode(' + ', array_map('basename', $dictionaries)));
printf("              %s words, %s prefixes%s\n",
    number_format($words), number_format($prefixes),
    $wordBreaker->dict->isWeighted() ? ', weighted' : '');
printf("load          %.3fs, holding %.2f MB\n", $loadTime, $dictionaryMemory / 1048576);
printf("peak memory   %.2f MB (whole process, text included)\n", memory_get_peak_usage(true) / 1048576);
printf("segmenting    %s characters into %s tokens\n",
    number_format($characters), number_format(count($tokens)));
printf("              %.3fs average of %d, %.3fs best\n", $average, $repeat, $best);
printf("              %s characters/second\n", number_format((int) round($characters / $best)));
