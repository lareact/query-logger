<?php

namespace Golly\QueryLogger;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\ServiceProvider;

/**
 * Class QueryServiceProvider
 * @package Golly\QueryLogger\Providers
 */
class QueryServiceProvider extends ServiceProvider
{
    /**
     * {@inheritdoc}
     * @throws BindingResolutionException
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/channels.php', 'logging.channels');
        // make sure events will be fired in Lumen
        $this->app->make('events');
        // create logger class
        $logger = $this->app->make(QueryLogger::class);
        // listen to database queries
        $this->app['db']->listen(function ($query) use ($logger) {
            $logger->setQuery($query);
        });
    }
}
