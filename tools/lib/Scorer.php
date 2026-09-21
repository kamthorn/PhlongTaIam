<?php
declare(strict_types=1);

namespace PhlongTaIam\Tools;

/**
 * Word-level segmentation scoring.
 *
 * A token counts as correct only when its start and end both match the gold
 * annotation, which is the usual way Thai word segmenters are compared.
 */
class Scorer
{
    private int $goldTotal = 0;
    private int $predTotal = 0;
    private int $hits = 0;

    private int $goldNoSpace = 0;
    private int $predNoSpace = 0;
    private int $hitsNoSpace = 0;

    private int $sentences = 0;
    private int $characters = 0;

    private int $inDictTokens = 0;
    private int $inDictMissed = 0;
    private int $oovTokens = 0;
    private int $oovMissed = 0;

    /** @var array<string, int> */
    private array $missedWords = [];

    /**
     * @param array<string, mixed> $dictSet Dictionary words as array keys.
     */
    public function __construct(private array $dictSet = [])
    {
    }

    /**
     * Character offsets of each token: "start:end", end exclusive.
     *
     * @param  string[] $tokens
     * @return string[]
     */
    public static function spans(array $tokens): array
    {
        $spans = [];
        $position = 0;
        foreach ($tokens as $token) {
            $length = mb_strlen($token, 'UTF-8');
            if ($length === 0) {
                continue;
            }
            $spans[] = $position . ':' . ($position + $length);
            $position += $length;
        }
        return $spans;
    }

    /**
     * The segmenter returns a run of whitespace as one token, so gold runs are
     * joined too - otherwise every double space would count as an error.
     *
     * @param  string[] $tokens
     * @return string[]
     */
    public static function mergeSpaceRuns(array $tokens): array
    {
        $merged = [];
        foreach ($tokens as $token) {
            $last = $merged ? $merged[count($merged) - 1] : null;
            if ($last !== null && trim($token) === '' && trim($last) === '') {
                $merged[count($merged) - 1] .= $token;
            } else {
                $merged[] = $token;
            }
        }
        return $merged;
    }

    /**
     * @param string[] $goldTokens
     * @param string[] $predictedTokens
     */
    public function add(array $goldTokens, array $predictedTokens): void
    {
        $gold = self::spans($goldTokens);
        $predicted = self::spans($predictedTokens);
        $goldLookup = array_flip($gold);
        $predictedLookup = array_flip($predicted);

        $this->goldTotal += count($gold);
        $this->predTotal += count($predicted);
        foreach ($predicted as $span) {
            if (isset($goldLookup[$span])) {
                $this->hits++;
            }
        }

        $this->sentences++;
        $this->characters += mb_strlen(implode('', $goldTokens), 'UTF-8');

        $this->tallyGoldTokens($goldTokens, $predictedLookup);
        $this->tallyExcludingSpaces($goldTokens, $predictedTokens, $goldLookup);
    }

    /**
     * @param string[]             $goldTokens
     * @param array<string, mixed> $predictedLookup
     */
    private function tallyGoldTokens(array $goldTokens, array $predictedLookup): void
    {
        $position = 0;
        foreach ($goldTokens as $token) {
            $length = mb_strlen($token, 'UTF-8');
            if ($length === 0) {
                continue;
            }
            $span = $position . ':' . ($position + $length);
            $position += $length;

            if (trim($token) === '') {
                continue;
            }

            $found = isset($predictedLookup[$span]);
            if (isset($this->dictSet[$token])) {
                $this->inDictTokens++;
                if (!$found) { $this->inDictMissed++; }
            } else {
                $this->oovTokens++;
                if (!$found) { $this->oovMissed++; }
            }
            if (!$found) {
                $this->missedWords[$token] = ($this->missedWords[$token] ?? 0) + 1;
            }
        }
    }

    /**
     * Whitespace is trivial to get right, so the headline number is also
     * reported with space tokens taken out of both sides.
     *
     * @param string[]             $goldTokens
     * @param string[]             $predictedTokens
     * @param array<string, mixed> $goldLookup
     */
    private function tallyExcludingSpaces(array $goldTokens, array $predictedTokens, array $goldLookup): void
    {
        $this->goldNoSpace += count(self::nonSpaceSpans($goldTokens));

        foreach (self::nonSpaceSpans($predictedTokens) as $span) {
            $this->predNoSpace++;
            if (isset($goldLookup[$span])) {
                $this->hitsNoSpace++;
            }
        }
    }

    /**
     * @param  string[] $tokens
     * @return string[]
     */
    private static function nonSpaceSpans(array $tokens): array
    {
        $spans = [];
        $position = 0;
        foreach ($tokens as $token) {
            $length = mb_strlen($token, 'UTF-8');
            if ($length === 0) {
                continue;
            }
            $span = $position . ':' . ($position + $length);
            $position += $length;
            if (trim($token) !== '') {
                $spans[] = $span;
            }
        }
        return $spans;
    }

    /**
     * @return array<string, mixed>
     */
    public function report(int $topMissed = 15): array
    {
        [$p, $r, $f1] = self::prf($this->hits, $this->predTotal, $this->goldTotal);
        [$pn, $rn, $f1n] = self::prf($this->hitsNoSpace, $this->predNoSpace, $this->goldNoSpace);

        arsort($this->missedWords);
        $nonSpaceGold = $this->inDictTokens + $this->oovTokens;
        $allMissed = $this->inDictMissed + $this->oovMissed;

        return [
            'sentences' => $this->sentences,
            'characters' => $this->characters,
            'precision' => $p,
            'recall' => $r,
            'f1' => $f1,
            'precision_no_space' => $pn,
            'recall_no_space' => $rn,
            'f1_no_space' => $f1n,
            'gold_tokens' => $this->goldTotal,
            'predicted_tokens' => $this->predTotal,
            'correct_tokens' => $this->hits,
            'in_dict_tokens' => $this->inDictTokens,
            'in_dict_missed' => $this->inDictMissed,
            'oov_tokens' => $this->oovTokens,
            'oov_missed' => $this->oovMissed,
            'oov_rate' => $nonSpaceGold > 0 ? $this->oovTokens / $nonSpaceGold : 0.0,
            'oov_share_of_errors' => $allMissed > 0 ? $this->oovMissed / $allMissed : 0.0,
            'top_missed' => array_slice($this->missedWords, 0, $topMissed, true),
        ];
    }

    /**
     * @return array{0: float, 1: float, 2: float}
     */
    private static function prf(int $hits, int $predicted, int $gold): array
    {
        $p = $predicted > 0 ? (float) $hits / $predicted : 0.0;
        $r = $gold > 0 ? (float) $hits / $gold : 0.0;
        $f1 = ($p + $r) > 0 ? 2 * $p * $r / ($p + $r) : 0.0;
        return [$p, $r, $f1];
    }
}
