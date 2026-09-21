<?php
namespace PhlongTaIam\Laravel;

use Illuminate\Support\Facades\Blade;
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
            $additional = $app['config']->get('phlongtaiam.additional_dictionaries') ?: [];

            return new WordBreaker(array_merge([$path], (array) $additional));
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

        // @thaiwordwrap($text) in a Blade view (e.g. a PDF export template)
        // escapes $text and inserts zero-width spaces between its words, so
        // renderers like dompdf/mPDF can wrap unspaced Thai text onto
        // multiple lines instead of overflowing.
        Blade::directive('thaiwordwrap', function ($expression) {
            return "<?php echo e(app('phlongtaiam')->insertWordBreaks($expression)); ?>";
        });
    }

    /**
     * @return string[]
     */
    public function provides(): array
    {
        return [WordBreaker::class, 'phlongtaiam'];
    }
}
