<?php
namespace PhlongTaIam\Tests\Tools;

use PHPUnit\Framework\TestCase;
use PhlongTaIam\Tools\CorpusReader;
use RuntimeException;

final class CorpusReaderTest extends TestCase
{
    private function fixture(string $name): string
    {
        return __DIR__ . '/../fixtures/' . $name;
    }

    public function testReadsLst20SentencesAndTurnsUnderscoresBackIntoSpaces(): void
    {
        $sentences = iterator_to_array(
            CorpusReader::readLst20($this->fixture('corpus-sample.lst20.txt'))
        );

        $this->assertSame(
            [
                ['ฉัน', 'กิน', 'ข้าว'],
                ['โรงเรียน', ' ', 'เปิด'],
            ],
            $sentences
        );
    }

    public function testReadsConllSentencesSplittingJoinedForms(): void
    {
        $sentences = iterator_to_array(
            CorpusReader::readConll($this->fixture('corpus-sample.conll'))
        );

        // "กิน|ข้าว" is one CoNLL row holding two words.
        $this->assertSame(
            [
                ['ฉัน', 'กิน', 'ข้าว'],
                ['โรงเรียน', 'เปิด'],
            ],
            $sentences
        );
    }

    public function testReadAcceptsASingleFile(): void
    {
        $sentences = iterator_to_array(
            CorpusReader::read($this->fixture('corpus-sample.lst20.txt'), CorpusReader::FORMAT_LST20)
        );

        $this->assertCount(2, $sentences);
    }

    public function testFilesSkipsDotFilesAndOtherExtensions(): void
    {
        $files = CorpusReader::files($this->fixture(''), CorpusReader::FORMAT_CONLL);

        $this->assertSame(['corpus-sample.conll'], array_map('basename', $files));
    }

    public function testReadRejectsAMissingCorpus(): void
    {
        $this->expectException(RuntimeException::class);
        iterator_to_array(CorpusReader::read('/no/such/corpus', CorpusReader::FORMAT_LST20));
    }
}
