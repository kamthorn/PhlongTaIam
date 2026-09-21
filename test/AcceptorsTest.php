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
        $acceptor = new WordRuleAcceptor();
        $acceptor->transit('a');

        $this->assertFalse($acceptor->isError);
        $this->assertTrue($acceptor->isFinal);
    }

    public function testWordRuleRejectsNonLetters(): void
    {
        $acceptor = new WordRuleAcceptor();
        $acceptor->transit('1');

        $this->assertTrue($acceptor->isError);
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
