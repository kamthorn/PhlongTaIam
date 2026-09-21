<?php
namespace PhlongTaIam\Tests\Laravel;

use Orchestra\Testbench\TestCase;
use PhlongTaIam\Laravel\PhlongTaIamServiceProvider;
use PhlongTaIam\WordBreaker;

final class PhlongTaIamServiceProviderTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [PhlongTaIamServiceProvider::class];
    }

    public function testWordBreakerResolvesAsASingletonUsingTheBundledDictionary(): void
    {
        $first = $this->app->make(WordBreaker::class);
        $second = $this->app->make(WordBreaker::class);

        $this->assertSame($first, $second, 'WordBreaker should be bound as a singleton');
        $this->assertSame(['กิน'], $first->breakIntoWords('กิน'));
    }

    public function testTheAliasResolvesToTheSameSingleton(): void
    {
        $this->assertSame(
            $this->app->make(WordBreaker::class),
            $this->app->make('phlongtaiam')
        );
    }

    public function testDictionaryPathIsConfigurable(): void
    {
        config(['phlongtaiam.dictionary_path' => __DIR__ . '/../fixtures/mini-dict.txt']);

        $wordBreaker = $this->app->make(WordBreaker::class);

        $this->assertSame(['กิน'], $wordBreaker->breakIntoWords('กิน'));
        $this->assertSame(['xyz'], $wordBreaker->breakIntoWords('xyz'));
    }

    public function testConfigCanBePublished(): void
    {
        $this->artisan('vendor:publish', ['--tag' => 'phlongtaiam-config'])->run();

        $this->assertFileExists($this->app->configPath('phlongtaiam.php'));
    }
}
