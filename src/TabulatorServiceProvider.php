<?php namespace PortOneFive\Tabulator;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\Compilers\BladeCompiler;
use PortOneFive\Tabulator\Pagination\FoundationPresenter;

class TabulatorServiceProvider extends ServiceProvider
{

    public function boot()
    {
        $configPath = __DIR__ . '/../config/tabulator.php';

        $this->publishes([$configPath => $this->getConfigPath()], 'config');

        $tableView    = 'resources/views/partial/table.blade.php';
        $tableRowView = 'resources/views/partial/table-row.blade.php';

        $this->publishes(
            [
                __DIR__ . '/../' . $tableView    => app_path($tableView),
                __DIR__ . '/../' . $tableRowView => app_path($tableRowView)
            ],
            'views'
        );

        $sassFile = 'resources/assets/sass/partial/_table.scss';

        $this->publishes([__DIR__ . '/../' . $sassFile => app_path($sassFile)], 'sass');

        /** @var BladeCompiler $blade */
        $blade = $this->app['view']->getEngineResolver()->resolve('blade')->getCompiler();

        $blade->extend(
            function ($view) {
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
        $configPath = __DIR__ . '/../config/tabulator.php';

        $this->mergeConfigFrom($configPath, 'tabulator');

        TableBuilder::setViewFactory($this->app['view']);
        TableBuilder::setRequest($this->app['request']);

        if (config('tabulator.css-framework') == 'foundation') {
            Paginator::presenter(
                function ($paginator) {
                    return new FoundationPresenter($paginator);
                }
            );
        }
    }

    private function getConfigPath()
    {
        return config_path('tabulator.php');
    }
}
