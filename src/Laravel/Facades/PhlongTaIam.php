<?php
namespace PhlongTaIam\Laravel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static string[] breakIntoWords(string $text)
 * @method static array<int, array{s: int, e: int}> breakIntoRanges(string $text)
 *
 * @see \PhlongTaIam\WordBreaker
 */
class PhlongTaIam extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'phlongtaiam';
    }
}
