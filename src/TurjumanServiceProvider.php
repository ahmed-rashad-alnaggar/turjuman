<?php

namespace Alnaggar\Turjuman;

use Illuminate\Support\ServiceProvider;

class TurjumanServiceProvider extends ServiceProvider
{
    /**
     * The path to the configuration file.
     * 
     * @var string
     */
    private const CONFIG_PATH = __DIR__ . '/../config/turjuman.php';

    /**
     * Register services.
     * 
     * @return void
     */
    public function register() : void
    {
        $this->mergeConfigFrom(self::CONFIG_PATH, 'turjuman');

        // Bind Localizer as a singleton and create an alias
        $this->app->singleton(Localizer::class);
        $this->app->alias(Localizer::class, 'turjuman');
    }

    /**
     * Bootstrap services.
     * 
     * @return void
     */
    public function boot() : void
    {
        // Register the package publishable resources.
        $this->publishes([
            self::CONFIG_PATH => config_path('turjuman.php')
        ], 'config');
    }
}