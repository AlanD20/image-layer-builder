<?php

namespace Aland20\ImageLayerBuilder;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Aland20\ImageLayerBuilder\Skeleton\SkeletonClass
 */
class ImageLayerBuilderFacade extends Facade
{
  /**
   * Get the registered name of the component.
   *
   * @return string
   */
  protected static function getFacadeAccessor()
  {
    return 'ImageLayerBuilder';
  }
}
