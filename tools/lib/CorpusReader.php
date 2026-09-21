<?php
declare(strict_types=1);

namespace PhlongTaIam\Tools;

use InvalidArgumentException;
use RuntimeException;

/**
 * Reads gold-segmented sentences out of an annotated corpus.
 *
 * No corpus ships with this package - point these tools at your own copy.
 * Check the corpus licence before redistributing anything derived from it.
 */
class CorpusReader
{
    public const FORMAT_LST20 = 'lst20';
    public const FORMAT_CONLL = 'conll';

    /**
     * @param  string $path   A corpus file, or a directory of them.
     * @param  string $format One of the FORMAT_* constants.
     * @param  int    $maxFiles Stop after this many files (0 = no limit).
     * @return iterable<string[]> One array of gold tokens per sentence.
     */
    public static function read(string $path, string $format, int $maxFiles = 0): iterable
    {
        foreach (self::files($path, $format, $maxFiles) as $file) {
            yield from match ($format) {
                self::FORMAT_LST20 => self::readLst20($file),
                self::FORMAT_CONLL => self::readConll($file),
                default => throw new InvalidArgumentException("Unknown corpus format: $format"),
            };
        }
    }

    /**
     * @return string[]
     */
    public static function files(string $path, string $format, int $maxFiles = 0): array
    {
        if (is_file($path)) {
            return [$path];
        }
        if (!is_dir($path)) {
            throw new RuntimeException("No such corpus file or directory: $path");
        }

        $extension = $format === self::FORMAT_CONLL ? '.conll' : '.txt';
        $files = [];
        foreach (scandir($path) ?: [] as $entry) {
            // Archives from macOS carry ._ resource forks next to every file.
            if (str_starts_with($entry, '.') || !str_ends_with($entry, $extension)) {
                continue;
            }
            $files[] = $path . '/' . $entry;
        }
        sort($files);

        return $maxFiles > 0 ? array_slice($files, 0, $maxFiles) : $files;
    }

    /**
     * LST20: "word<TAB>POS<TAB>NE<TAB>clause" per line, blank line between
     * sentences, and a literal "_" wherever the text had a space.
     *
     * @return iterable<string[]>
     */
    public static function readLst20(string $file): iterable
    {
        $tokens = [];
        foreach (self::lines($file) as $line) {
            if (trim($line) === '') {
                if ($tokens) { yield $tokens; $tokens = []; }
                continue;
            }
            $parts = explode("\t", $line);
            if (count($parts) < 2) {
                continue;
            }
            $tokens[] = $parts[0] === '_' ? ' ' : $parts[0];
        }
        if ($tokens) {
            yield $tokens;
        }
    }

    /**
     * CoNLL: "ID<TAB>FORM<TAB>..." per line, "#" comments, blank line between
     * sentences. A FORM may join several words with "|", and the format keeps
     * no spaces, so sentences read from it have none.
     *
     * @return iterable<string[]>
     */
    public static function readConll(string $file): iterable
    {
        $tokens = [];
        foreach (self::lines($file) as $line) {
            if (str_starts_with($line, '#')) {
                continue;
            }
            if (trim($line) === '') {
                if ($tokens) { yield $tokens; $tokens = []; }
                continue;
            }
            $parts = explode("\t", $line);
            if (count($parts) < 2) {
                continue;
            }
            foreach (explode('|', $parts[1]) as $word) {
                if ($word !== '') {
                    $tokens[] = $word;
                }
            }
        }
        if ($tokens) {
            yield $tokens;
        }
    }

    /**
     * @return iterable<string>
     */
    private static function lines(string $file): iterable
    {
        $handle = @fopen($file, 'r');
        if ($handle === false) {
            throw new RuntimeException("Cannot read corpus file: $file");
        }
        try {
            while (($line = fgets($handle)) !== false) {
                yield rtrim($line, "\r\n");
            }
        } finally {
            fclose($handle);
        }
    }
}
