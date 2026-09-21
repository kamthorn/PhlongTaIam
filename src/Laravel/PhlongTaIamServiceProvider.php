<?php
namespace PhlongTaIam\Laravel;

use Illuminate\Support\ServiceProvider;
use PhlongTaIam\WordBreaker;

class PhlongTaIamServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/phlongtaiam.php', 'phlongtaiam');

        $this->app->singleton(WordBreaker::class, function ($app) {
            $path = $app['config']->get('phlongtaiam.dictionary_path')
                ?: __DIR__ . '/../../data/tdict-std.txt';

            return new WordBreaker($path);
        });

        $this->app->alias(WordBreaker::class, 'phlongtaiam');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../config/phlongtaiam.php' => $this->app->configPath('phlongtaiam.php'),
            ], 'phlongtaiam-config');
        }
    }

    /**
     * @return string[]
     */
    public function provides(): array
    {
        return [WordBreaker::class, 'phlongtaiam'];
    }
}
