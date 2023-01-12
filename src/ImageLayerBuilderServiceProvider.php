<?php

namespace Aland20\ImageLayerBuilder;

use Illuminate\Support\ServiceProvider;

class ImageLayerBuilderServiceProvider extends ServiceProvider
{
  /**
   * Bootstrap the application services.
   */
  public function boot()
  {
    /*
    * Optional methods to load your package assets
    */
    // $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'image-layer-builder');
    // $this->loadViewsFrom(__DIR__.'/../resources/views', 'image-layer-builder');
    // $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    // $this->loadRoutesFrom(__DIR__.'/routes.php');

    if ($this->app->runningInConsole()) {
      $this->publishes([
        __DIR__ . '/../config/config.php' => config_path('image-layer-builder.php'),
      ], 'config');

      $this->publishes([
        __DIR__ . '/../storage/image-layers/backgrounds' => \storage_path('app/public/vendor/image-layers/backgrounds'),
        __DIR__ . '/../storage/image-layers/output' => \storage_path('app/public/vendor/image-layers/output'),
        __DIR__ . '/../storage/image-layers/temp' => \storage_path('app/vendor/image-layers/temp'),
      ], 'config');

      // Publishing the views.
      /*$this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/image-layer-builder'),
            ], 'views');*/

      // Publishing assets.
      /*$this->publishes([
                __DIR__.'/../resources/assets' => public_path('vendor/image-layer-builder'),
            ], 'assets');*/

      // Publishing the translation files.
      /*$this->publishes([
                __DIR__.'/../resources/lang' => resource_path('lang/vendor/image-layer-builder'),
            ], 'lang');*/

      // Registering package commands.
      // $this->commands([]);
    }
  }

  /**
   * Register the application services.
   */
  public function register()
  {
    // Automatically apply the package configuration
    $this->mergeConfigFrom(__DIR__ . '/../config/config.php', 'image-layer-builder');

    // Register the main class to use with the facade
    $this->app->singleton('ImageLayerBuilder', function () {
      return new ImageLayerBuilder;
    });
  }
}
