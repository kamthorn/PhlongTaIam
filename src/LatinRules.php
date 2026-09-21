<?php
declare(strict_types=1);

namespace PhlongTaIam;

class WordRuleAcceptor
{
    public int $strOffset = 0;
    public bool $isFinal = false;
    public bool $isError = false;
    public string $tag = "WORD_RULE";
    public string $type = "WORD_RULE";
    public int $w = 1;
    public int $mw = 0;
    public int $unk = 0;

    public function transit(string $ch): self
    {
        if (($ch >= "a" && $ch <= "z") || ($ch >= "A" && $ch <= "z")) {
            $this->isFinal = true;
            $this->strOffset++;
        } else {
            $this->isError = true;
        }
        return $this;
    }
}

class WordRule
{
    /**
     * @param array<string, object> $tag
     */
    public function createAcceptor(array $tag): ?WordRuleAcceptor
    {
        if (array_key_exists("WORD_RULE", $tag))
            return null;
        return new WordRuleAcceptor();
    }
}

class SpaceRuleAcceptor
{
    public int $strOffset = 0;
    public bool $isFinal = false;
    public bool $isError = false;
    public string $tag = "SPACE_RULE";
    public string $type = "SPACE_RULE";
    public int $w = 1;
    public int $mw = 0;
    public int $unk = 0;

    public function transit(string $ch): self
    {
        if ($ch == " " || $ch == "\t" || $ch == "\r" || $ch == "\n") {
            $this->isFinal = true;
            $this->strOffset++;
        } else {
            $this->isError = true;
        }
        return $this;
    }
}

class SpaceRule
{
    /**
     * @param array<string, object> $tag
     */
    public function createAcceptor(array $tag): ?SpaceRuleAcceptor
    {
        if (array_key_exists("SPACE_RULE", $tag))
            return null;
        return new SpaceRuleAcceptor();
    }
}

class SingleSymbolAcceptor
{
    public int $strOffset = 0;
    public bool $isFinal = false;
    public bool $isError = false;
    public string $tag = "SINSYM";
    public string $type = "SINSYM";
    public int $w = 1;
    public int $mw = 0;
    public int $unk = 0;

    public function transit(string $ch): self
    {
        if ($this->strOffset == 0 && mb_strpos("()/-", $ch, 0, "UTF-8") !== false) {
            $this->isFinal = true;
            $this->strOffset++;
        } else {
            $this->isError = true;
        }
        return $this;
    }
}

class SingleSymbolRule
{
    /**
     * @param array<string, object> $tag
     */
    public function createAcceptor(array $tag): SingleSymbolAcceptor
    {
        return new SingleSymbolAcceptor();
    }
}
