<?php
namespace PhlongTaIam\Tests\Tools;

use PHPUnit\Framework\TestCase;
use PhlongTaIam\Tools\Scorer;

final class ScorerTest extends TestCase
{
    public function testAPerfectSegmentationScoresOne(): void
    {
        $scorer = new Scorer();
        $scorer->add(['ฉัน', 'กิน', 'ข้าว'], ['ฉัน', 'กิน', 'ข้าว']);

        $report = $scorer->report();

        $this->assertSame(1.0, $report['precision']);
        $this->assertSame(1.0, $report['recall']);
        $this->assertSame(1.0, $report['f1']);
    }

    public function testATokenCountsOnlyWhenBothItsEdgesMatch(): void
    {
        $scorer = new Scorer();
        // gold: ฉัน | กิน | ข้าว     predicted: ฉัน | กินข้าว
        $scorer->add(['ฉัน', 'กิน', 'ข้าว'], ['ฉัน', 'กินข้าว']);

        $report = $scorer->report();

        $this->assertSame(3, $report['gold_tokens']);
        $this->assertSame(2, $report['predicted_tokens']);
        $this->assertSame(1, $report['correct_tokens']);
        $this->assertSame(0.5, $report['precision']);
        $this->assertEqualsWithDelta(1 / 3, $report['recall'], 1e-9);
    }

    public function testSpacesInflateTheScoreSoTheyAreAlsoReportedSeparately(): void
    {
        $scorer = new Scorer();
        // กิน is split in two; the space and ข้าว come out right.
        $scorer->add(['กิน', ' ', 'ข้าว'], ['กิ', 'น', ' ', 'ข้าว']);

        $report = $scorer->report();

        $this->assertSame(3, $report['gold_tokens']);
        $this->assertSame(4, $report['predicted_tokens']);
        $this->assertSame(2, $report['correct_tokens'], 'the space and ข้าว match');
        $this->assertSame(0.5, $report['precision']);
        $this->assertEqualsWithDelta(2 / 3, $report['recall'], 1e-9);

        // Dropping the space leaves 2 gold words, 3 predicted, 1 correct.
        $this->assertEqualsWithDelta(1 / 3, $report['precision_no_space'], 1e-9);
        $this->assertSame(0.5, $report['recall_no_space']);
        $this->assertGreaterThan($report['f1_no_space'], $report['f1']);
    }

    public function testMissedWordsAreBucketedByWhetherTheDictionaryKnowsThem(): void
    {
        $scorer = new Scorer(['กิน' => true]);
        // Both gold words are missed; only กิน is in the dictionary.
        $scorer->add(['กิน', 'ข้าวผัด'], ['กินข้าว', 'ผัด']);

        $report = $scorer->report();

        $this->assertSame(1, $report['in_dict_tokens']);
        $this->assertSame(1, $report['in_dict_missed']);
        $this->assertSame(1, $report['oov_tokens']);
        $this->assertSame(1, $report['oov_missed']);
        $this->assertSame(0.5, $report['oov_rate']);
        $this->assertSame(['กิน' => 1, 'ข้าวผัด' => 1], $report['top_missed']);
    }

    public function testMergeSpaceRunsJoinsConsecutiveWhitespaceTokens(): void
    {
        $this->assertSame(
            ['กิน', '  ', 'ข้าว'],
            Scorer::mergeSpaceRuns(['กิน', ' ', ' ', 'ข้าว'])
        );
    }

    public function testSpansAreCharacterOffsets(): void
    {
        $this->assertSame(['0:3', '3:6'], Scorer::spans(['ฉัน', 'กิน']));
    }
}
