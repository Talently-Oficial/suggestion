<?php

namespace Suggestion\Providers;

use GuzzleHttp\ClientInterface;
use Illuminate\Contracts\Config\Repository as Config;
use Suggestion\SuggestionClient;
use Suggestion\Facades\Suggestion;
use Illuminate\Support\ServiceProvider;

class SuggestionServiceProvider extends ServiceProvider
{

    public function register(): void
    {
        $this->app->singleton(Suggestion::class, function ($app) {
            return new SuggestionClient(
                $app->make(ClientInterface::class),
                $app->make(Config::class)
            );
        });
        $this->mergeConfigFrom(
            __DIR__.'/../../config/suggestion.php',
            'suggestion'
        );
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../config/suggestion.php' => $this->app->configPath('suggestion.php'),
            ], 'config');
        }
    }

}