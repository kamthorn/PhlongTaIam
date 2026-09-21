<?php
declare(strict_types=1);

namespace PhlongTaIam;

/**
 * Thai word segmenter driven by a plain-text, newline-separated dictionary.
 */
class WordBreaker
{
    public Dict $dict;
    public Acceptors $acceptors;
    public PathInfoBuilder $pathInfoBuilder;
    public PathSelector $pathSelector;

    /**
     * @param string|string[] $dictPath Path to a UTF-8 dictionary file, one
     *                        word per line, in any order - e.g. the bundled
     *                        data/tdict-std.txt. Pass several paths to merge
     *                        them, for instance the bundled list plus your
     *                        own terms.
     */
    public function __construct(string|array $dictPath)
    {
        $this->dict = new Dict();
        foreach ((array) $dictPath as $path) {
            $this->dict->addDict($path);
        }
        $this->acceptors = new Acceptors();
        $this->acceptors->creators[] = $this->dict;
        $this->acceptors->creators[] = new WordRule();
        $this->acceptors->creators[] = new SpaceRule();
        $this->acceptors->creators[] = new SingleSymbolRule();
        $this->pathInfoBuilder = new PathInfoBuilder();
        $this->pathSelector = new PathSelector($this->dict->isWeighted());
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function createPath(): array
    {
        return [["p" => null,
                 "w" => 0,
                 "unk" => 0,
                 "cost" => 0.0,
                 "type" => "INIT",
                 "mw" => 0]];
    }

    /**
     * @param  string[] $chars $text split into individual UTF-8 characters,
     *                         e.g. via mb_str_split(). Taking the array
     *                         instead of the source string avoids calling
     *                         mb_substr($text, $i, 1) once per character -
     *                         mb_substr re-scans from the start of the
     *                         string on every call, which made this
     *                         effectively O(n^2) on long input.
     * @return array<int, array<string, mixed>>
     */
    public function buildPath(array $chars): array
    {
        $leftBoundary = 0;
        $path = $this->createPath();
        $this->acceptors->reset();
        $len = count($chars);
        for ($i = 0; $i < $len; $i++) {
            $ch = $chars[$i];
            $this->acceptors->transit($ch);
            $possiblePathInfos =
                $this->pathInfoBuilder->build(
                    $path,
                    $this->acceptors->getFinalAcceptors(),
                    $i,
                    $leftBoundary,
                    $chars);
            $selectedPath = $this->pathSelector->selectPath($possiblePathInfos);
            $path[] = $selectedPath;
            if ($selectedPath["type"] != "UNK")
                $leftBoundary = $i + 1;
        }
        return $path;
    }

    /**
     * @param  string[] $chars See buildPath(). Slicing the pre-split array
     *                         instead of calling mb_substr($text, ...) once
     *                         per word avoids the same O(n^2) re-scan for
     *                         texts with many short tokens.
     * @param  array<int, array{s: int, e: int}> $ranges
     * @return string[]
     */
    public function rangesToTextList(array $chars, array $ranges): array
    {
        $textList = [];
        foreach ($ranges as $r) {
            $textList[] = implode('', array_slice($chars, $r["s"], $r["e"] - $r["s"]));
        }
        return $textList;
    }

    /**
     * @param  array<int, array<string, mixed>> $path
     * @return array<int, array{s: int, e: int}>
     */
    public function pathToRanges(array $path): array
    {
        $e = count($path) - 1;
        $ranges = [];

        while ($e > 0) {
            $s = $path[$e]["p"];
            $ranges[] = ["s" => $s, "e" => $e];
            $e = $s;
        }

        return array_reverse($ranges);
    }

    /**
     * @param  string[] $chars See buildPath().
     * @return array<int, array{s: int, e: int}>
     */
    private function charsToRanges(array $chars): array
    {
        return $this->pathToRanges($this->buildPath($chars));
    }

    /**
     * @param  string $text UTF-8 text to segment.
     * @return array<int, array{s: int, e: int}> Character-offset ranges (start
     *         inclusive, end exclusive) of each token, in order.
     */
    public function breakIntoRanges(string $text): array
    {
        return $this->charsToRanges(mb_str_split($text, 1, "UTF-8"));
    }

    /**
     * @param  string $text UTF-8 text to segment.
     * @return string[] The tokens (words, whitespace runs, and unknown
     *         character runs) found in $text, in order.
     */
    public function breakIntoWords(string $text): array
    {
        $chars = mb_str_split($text, 1, "UTF-8");
        return $this->rangesToTextList($chars, $this->charsToRanges($chars));
    }

    /**
     * Segment $text and re-join it with $breakChar between each token.
     *
     * Thai script has no spaces between words, so HTML/PDF renderers (e.g.
     * dompdf, mPDF, wkhtmltopdf) treat a long run of Thai text as a single
     * unbreakable "word" and either overflow its container or refuse to
     * wrap at all. Inserting a zero-width space (the default $breakChar)
     * between words gives the renderer real line-break opportunities
     * without changing how the text looks.
     *
     * @param  string $text      UTF-8 text to segment.
     * @param  string $breakChar Inserted between each token. Defaults to
     *                           U+200B (zero-width space).
     * @return string $text with $breakChar inserted between tokens.
     */
    public function insertWordBreaks(string $text, string $breakChar = "\u{200B}"): string
    {
        return implode($breakChar, $this->breakIntoWords($text));
    }
}
