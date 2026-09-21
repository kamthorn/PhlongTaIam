<?php
namespace PhlongTaIam\Tests;

use PHPUnit\Framework\TestCase;

final class ComposerConfigTest extends TestCase
{
    private function readComposerJson(): array
    {
        $json = file_get_contents(__DIR__ . '/../composer.json');
        return json_decode($json, true);
    }

    public function testDeclaresAPsr4AutoloadMappingForTheSrcDirectory(): void
    {
        $composer = $this->readComposerJson();

        $this->assertSame(
            'src/',
            $composer['autoload']['psr-4']['PhlongTaIam\\'] ?? null,
            'composer install must be able to autoload PhlongTaIam\\* classes from src/'
        );
    }

    public function testRequiresACurrentlySupportedPhpVersion(): void
    {
        $composer = $this->readComposerJson();

        $this->assertArrayHasKey('require', $composer);
        $this->assertNotSame('>=5.3.0', $composer['require']['php'] ?? null);
    }
}
