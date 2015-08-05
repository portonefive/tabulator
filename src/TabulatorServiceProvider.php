<?php namespace PortOneFive\Tabulator;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\Compilers\BladeCompiler;
use PortOneFive\Tabulator\Pagination\FoundationPresenter;

class TabulatorServiceProvider extends ServiceProvider {

    public function boot()
    {
        $this->mergeConfigFrom(__DIR__ . '/config/table.php', 'tabulator');

        /** @var BladeCompiler $blade */
        $blade = $this->app['view']->getEngineResolver()->resolve('blade')->getCompiler();

        $blade->extend(
            function ($view)
            {
                return preg_replace_callback(
                    '/\B@(\w+)([ \t]*)(\( ( (?>[^()]+) | (?3) )* \))?/x',
                    [BladeTableCompiler::class, 'attempt'],
                    $view
                );
            }
        );
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        Paginator::presenter(
            function ($paginator) {
                return new FoundationPresenter($paginator);
            }
        );
    }
}
