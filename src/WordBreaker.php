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

    function buildPath($text) 
    {
        $leftBoundary = 0;
        $path = $this->createPath();
        $this->acceptors->reset();
        $len = mb_strlen($text, "UTF-8");
        for ($i = 0; $i < $len; $i++) {
            $ch = mb_substr($text, $i, 1, "UTF-8");
            $this->acceptors->transit($ch);
            $possiblePathInfos = 
                $this->pathInfoBuilder->build(
                    $path, 
                    $this->acceptors->getFinalAcceptors(), 
                    $i, 
                    $leftBoundary, 
                    $text);
            $selectedPath = $this->pathSelector->selectPath($possiblePathInfos);
            $path[] = $selectedPath;
            if ($selectedPath["type"] != "UNK")
                $leftBoundary = $i + 1;
        }
        return $path;
    }

    function rangesToTextList($text, $ranges) 
    {
        $textList = array();
        foreach($ranges as $r) {
            $w = mb_substr($text, $r["s"], $r["e"] - $r["s"], "UTF-8");
            $textList[] = $w;
        }   
        return $textList;
    }

    function pathToRanges($path) 
    {
        $e = sizeof($path) - 1;
        $ranges = array();

        while ($e > 0) {
            $info = $path[$e];
            $s = $info["p"];

            if (array_key_exists("merge", $info) && sizeof($ranges) > 0) {
                $r = $ranges[sizeof($ranges) - 1];
                $r["s"] = $info["merge"];
                $s = $r["s"];
            } else {
                $ranges[] = array("s" => $s, "e" => $e);
            }
            $e = $s;
        }

        return array_reverse($ranges);
    }

    /**
     * @param  string $text UTF-8 text to segment.
     * @return array<int, array{s: int, e: int}> Character-offset ranges (start
     *         inclusive, end exclusive) of each token, in order.
     */
    function breakIntoRanges($text)
    {
        $path = $this->buildPath($text);
        $ranges = $this->pathToRanges($path);
        return $ranges;
    }

    /**
     * @param  string $text UTF-8 text to segment.
     * @return string[] The tokens (words, whitespace runs, and unknown
     *         character runs) found in $text, in order.
     */
    function breakIntoWords($text)
    {
        $ranges = $this->breakIntoRanges($text);
        $textList = $this->rangesToTextList($text, $ranges);
        return $textList;
    }
}
?>
