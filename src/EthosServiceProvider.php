<?php

namespace Roanokecollege\Ethos;

use Illuminate\Support\ServiceProvider;


class EthosServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
          __DIR__ . "/../config/ethos.php", "ethos"
        );

        \App::bind('Ethos', function () {
          return new EthosRequest;
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
      $this->publishes ([
          __DIR__ . "/../config/ethos.php" => config_path("ethos.php"),
      ]);
    }
}
