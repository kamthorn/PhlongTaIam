<?php
declare(strict_types=1);

namespace PhlongTaIam;

class PathInfoBuilder
{
    /**
     * @param  array<int, array<string, mixed>> $path
     * @param  AbstractAcceptor[] $finalAcceptors
     * @return array<int, array<string, mixed>>
     */
    public function buildByAcceptors(array $path, array $finalAcceptors, int $i): array
    {
        $infos = [];
        foreach ($finalAcceptors as $acceptor) {
            $p = $i - $acceptor->strOffset + 1;
            $_info = $path[$p];
            $infos[] = ["p" => $p,
                        "mw" => $_info["mw"] + $acceptor->mw,
                        "w" => $acceptor->w + $_info["w"],
                        "unk" => $acceptor->unk + $_info["unk"],
                        "type" => $acceptor->type];
        }
        return $infos;
    }

    /**
     * @param  array<int, array<string, mixed>> $path
     * @param  string[] $chars The full text split into individual UTF-8
     *                         characters (see WordBreaker::buildPath()).
     * @return array<string, mixed>
     */
    public function fallback(array $path, int $leftBoundary, array $chars, int $i): array
    {
        $_info = $path[$leftBoundary];
        $ch = $chars[$i];
        mb_regex_encoding("UTF-8");
        // U+0E48..U+0E4E are Thai tone marks and diacritics. They are written
        // as escapes rather than literally because they are combining marks:
        // on their own they have no base character to sit on, so the literal
        // form renders as unreadable floating glyphs in most editors.
        if (mb_ereg("[\u0E48-\u0E4E]", $ch)) {
            if ($leftBoundary != 0) {
                $leftBoundary = $path[$leftBoundary]["p"];
            }
            return ["p" => $leftBoundary,
                    "mw" => 0,
                    "w" => 1 + $_info["w"],
                    "unk" => 1 + $_info["unk"],
                    "type" => "UNK"];
        } else {
            return ["p" => $leftBoundary,
                    "mw" => $_info["mw"],
                    "w" => 1 + $_info["w"],
                    "unk" => 1 + $_info["unk"],
                    "type" => "UNK"];
        }
    }

    /**
     * @param  array<int, array<string, mixed>> $path
     * @param  AbstractAcceptor[] $finalAcceptors
     * @param  string[] $chars
     * @return array<int, array<string, mixed>>
     */
    public function build(array $path, array $finalAcceptors, int $i, int $leftBoundary, array $chars): array
    {
        $basicPathInfos = $this->buildByAcceptors($path, $finalAcceptors, $i);
        if (count($basicPathInfos) > 0) {
            return $basicPathInfos;
        } else {
            return [$this->fallback($path, $leftBoundary, $chars, $i)];
        }
    }
}
