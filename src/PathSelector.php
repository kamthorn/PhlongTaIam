<?php
declare(strict_types=1);

namespace PhlongTaIam;

class PathSelector
{
    /**
     * Pick the cheapest candidate: fewest unknown characters first, then
     * fewest merges, then fewest words.
     *
     * @param  array<int, array<string, mixed>> $paths
     * @return array<string, mixed>|null
     */
    public function selectPath(array $paths): ?array
    {
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
}
