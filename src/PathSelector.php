<?php
declare(strict_types=1);

namespace PhlongTaIam;

class PathSelector
{
    /**
     * @param bool $weighted Compare candidates by accumulated cost, which is
     *                       only meaningful when the dictionary carries corpus
     *                       counts. Otherwise every known word costs the same
     *                       and the counting rules below decide.
     */
    public function __construct(private bool $weighted = false)
    {
    }

    /**
     * Pick the cheapest candidate: by accumulated cost when weighted,
     * otherwise fewest unknown characters, then fewest merges, then fewest
     * words.
     *
     * @param  array<int, array<string, mixed>> $paths
     * @return array<string, mixed>|null
     */
    public function selectPath(array $paths): ?array
    {
        if ($this->weighted) {
            return $this->selectCheapest($paths);
        }

        $selectedPath = null;
        foreach ($paths as $path) {
            if (is_null($selectedPath)) {
                $selectedPath = $path;
            } else {
                if ($path["unk"] < $selectedPath["unk"]) {
                    $selectedPath = $path;
                } else if ($path["unk"] == $selectedPath["unk"]) {
                    if ($path["mw"] < $selectedPath["mw"]) {
                        $selectedPath = $path;
                    } else if ($path["mw"] == $selectedPath["mw"]) {
                        if ($path["w"] < $selectedPath["w"])
                            $selectedPath = $path;
                    }
                }
            }
        }
        return $selectedPath;
    }

    /**
     * @param  array<int, array<string, mixed>> $paths
     * @return array<string, mixed>|null
     */
    private function selectCheapest(array $paths): ?array
    {
        $selectedPath = null;
        foreach ($paths as $path) {
            if ($selectedPath === null || $path["cost"] < $selectedPath["cost"]) {
                $selectedPath = $path;
            }
        }
        return $selectedPath;
    }
}
