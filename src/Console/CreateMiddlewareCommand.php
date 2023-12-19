<?php

namespace Alnaggar\Turjuman\Console;

use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Input\InputOption;

/**
 * Class CreateMiddlewareCommand
 *
 * This console command generates a new middleware under the \App\Http\Middleware namespace.
 * The generated middleware extends the \Alnaggar\Turjuman\Middleware\SetLocale middleware, enabling the overriding of the fetchUserLocale function to facilitate fetching the user's locale from a specified source, such as a database.
 *
 * @package Alnaggar\Turjuman
 */
class CreateMiddlewareCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'turjuman:middleware';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish a new middleware that extends the SetLocale middleware to enable overriding of the fetchUserLocale function.';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Middleware';

    /**
     * Get the stub file for the generator.
     * 
     * @return string
     */
    protected function getStub() : string
    {
        return __DIR__ . '/../../stubs/middleware.stub';
    }

    /**
     * Get the default namespace for the class.
     *
     * @param string $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace) : string
    {
        return $rootNamespace . '\Http\Middleware';
    }

    /**
     * Get the console command arguments.
     * 
     * @return array
     */
    protected function getArguments() : array
    {
        return [
            ['name', InputOption::VALUE_REQUIRED, 'Name of the Middleware that should be created.', 'TurjumanSetLocale'],
        ];
    }

    /**
     * Get the console command options.
     * 
     * @return array
     */
    protected function getOptions() : array
    {
        return [
            ['force', null, InputOption::VALUE_NONE, 'Create the class even if the middleware already exists.'],
        ];
    }
}