<?php
namespace PhlongTaIam;

require_once "Dict.php";
require_once "PathInfoBuilder.php";
require_once "Acceptors.php";
require_once "PathSelector.php";
require_once "LatinRules.php";

/**
 * Thai word segmenter driven by a plain-text, newline-separated dictionary.
 */
class WordBreaker
{
    /**
     * @param string $dictPath Path to a UTF-8, newline-separated, lexicographically
     *                         sorted dictionary file (one word per line), e.g. the
     *                         bundled data/tdict-std.txt.
     */
    function __construct($dictPath)
    {
        mb_internal_encoding("UTF-8");
        $this->dict = new Dict();
        $this->dict->loadDict($dictPath);
        $this->acceptors = new Acceptors();
        $this->acceptors->creators[] = $this->dict;
        $this->acceptors->creators[] = new WordRule();
        $this->acceptors->creators[] = new SpaceRule();
        $this->acceptors->creators[] = new SingleSymbolRule();
        $this->pathInfoBuilder = new PathInfoBuilder();
        $this->pathSelector = new PathSelector();
    }

    function createPath()
    {
        return array(array("p" => NULL,
                           "w" => 0,
                           "unk" => 0,
                           "type" => "INIT",
                           "mw" => 0));
    }

    /**
     * @param string[] $chars $text split into individual UTF-8 characters,
     *                        e.g. via mb_str_split(). Taking the array
     *                        instead of the source string avoids calling
     *                        mb_substr($text, $i, 1) once per character -
     *                        mb_substr re-scans from the start of the
     *                        string on every call, which made this
     *                        effectively O(n^2) on long input.
     */
    function buildPath($chars)
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
     * @param string[] $chars See buildPath(). Slicing the pre-split array
     *                        instead of calling mb_substr($text, ...) once
     *                        per word avoids the same O(n^2) re-scan for
     *                        texts with many short tokens.
     */
    function rangesToTextList($chars, $ranges)
    {
        $textList = array();
        foreach($ranges as $r) {
            $textList[] = implode('', array_slice($chars, $r["s"], $r["e"] - $r["s"]));
        }
        return $textList;
    }

    function pathToRanges($path)
    {
        $e = sizeof($path) - 1;
        $ranges = array();

        while ($e > 0) {
            $s = $path[$e]["p"];
            $ranges[] = array("s" => $s, "e" => $e);
            $e = $s;
        }

        return array_reverse($ranges);
    }

    /**
     * @param  string[] $chars See buildPath().
     * @return array<int, array{s: int, e: int}>
     */
    private function charsToRanges($chars)
    {
        return $this->pathToRanges($this->buildPath($chars));
    }

    /**
     * @param  string $text UTF-8 text to segment.
     * @return array<int, array{s: int, e: int}> Character-offset ranges (start
     *         inclusive, end exclusive) of each token, in order.
     */
    function breakIntoRanges($text)
    {
        return $this->charsToRanges(mb_str_split($text, 1, "UTF-8"));
    }

    /**
     * @param  string $text UTF-8 text to segment.
     * @return string[] The tokens (words, whitespace runs, and unknown
     *         character runs) found in $text, in order.
     */
    function breakIntoWords($text)
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
    function insertWordBreaks($text, $breakChar = "\u{200B}")
    {
        return implode($breakChar, $this->breakIntoWords($text));
    }
}
?>
