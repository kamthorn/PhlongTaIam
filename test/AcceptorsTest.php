<?php
namespace PhlongTaIam\Tests;

use PHPUnit\Framework\TestCase;
use PhlongTaIam\WordRuleAcceptor;
use PhlongTaIam\SpaceRuleAcceptor;
use PhlongTaIam\SingleSymbolAcceptor;

final class AcceptorsTest extends TestCase
{
    public function testWordRuleAcceptsAsciiLetters(): void
    {
        foreach (['a', 'z', 'A', 'Z', 'm', 'M'] as $ch) {
            $acceptor = new WordRuleAcceptor();
            $acceptor->transit($ch);

            $this->assertFalse($acceptor->isError, "Expected '$ch' to be accepted as a letter");
            $this->assertTrue($acceptor->isFinal);
        }
    }

    public function testWordRuleRejectsNonLetters(): void
    {
        $acceptor = new WordRuleAcceptor();
        $acceptor->transit('1');

        $this->assertTrue($acceptor->isError);
    }

    public function testWordRuleRejectsThePunctuationBetweenTheAsciiLetterRanges(): void
    {
        // '[' '\' ']' '^' '_' '`' sit between 'Z' and 'a' in ASCII, so a
        // "A".."z" test would wrongly treat them as letters.
        foreach (['[', '\\', ']', '^', '_', '`'] as $ch) {
            $acceptor = new WordRuleAcceptor();
            $acceptor->transit($ch);

            $this->assertTrue($acceptor->isError, "Expected '$ch' to be rejected: it is not a letter");
        }
    }

    public function testSpaceRuleAcceptsWhitespaceCharacters(): void
    {
        foreach ([' ', "\t", "\r", "\n"] as $ch) {
            $acceptor = new SpaceRuleAcceptor();
            $acceptor->transit($ch);
            $this->assertFalse($acceptor->isError, "Expected '$ch' to be accepted as whitespace");
            $this->assertTrue($acceptor->isFinal);
        }
    }

    public function testSpaceRuleRejectsNonWhitespace(): void
    {
        $acceptor = new SpaceRuleAcceptor();
        $acceptor->transit('a');

        $this->assertTrue($acceptor->isError);
    }

    public function testSingleSymbolRuleAcceptsOnlyTheFourSpecialCharacters(): void
    {
        foreach (['(', ')', '/', '-'] as $ch) {
            $acceptor = new SingleSymbolAcceptor();
            $acceptor->transit($ch);
            $this->assertFalse($acceptor->isError, "Expected '$ch' to be accepted");
            $this->assertTrue($acceptor->isFinal);
        }
    }

    public function testSingleSymbolRuleRejectsCharactersOutsideItsSet(): void
    {
        foreach (['a', '5', 'ก', ' '] as $ch) {
            $acceptor = new SingleSymbolAcceptor();
            $acceptor->transit($ch);
            $this->assertTrue($acceptor->isError, "Expected '$ch' to be rejected, since it is not one of ()/-");
        }
    }
}
