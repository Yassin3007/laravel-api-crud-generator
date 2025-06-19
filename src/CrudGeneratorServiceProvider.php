<?php

namespace Yassin\LaravelApiCrudGenerator;

use Illuminate\Support\ServiceProvider;
use Yassin\LaravelApiCrudGenerator\Commands\CrudGenerateCommand;

class CrudGeneratorServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                CrudGenerateCommand::class,
            ]);
        }
    }

    public function register()
    {
        //
    }
}
