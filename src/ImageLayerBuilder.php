<?php

namespace Aland20\ImageLayerBuilder;

use Illuminate\Support\Str;
use Nette\Utils\FileSystem;
use Intervention\Image\Size;
use Intervention\Image\Facades\Image;
use Intervention\Image\Image as InterventionImage;

class ImageLayerBuilder
{

  protected string $bgDirPath;
  protected ?string $fontPath = null;

  protected string $outputPath;
  protected string $outputFormat = 'png';

  protected string $backgroundName = '';
  protected array $backgroundTexts = [];

  protected string $tempPath;

  protected array $tempImages = [];
  protected array $images = [];

  protected \Intervention\Image\Image $background;
  protected int $bgWidth = 1200;
  protected int $bgHeight = 750;
  protected int $bgCenteredX = 0;
  protected int $bgCenteredY = 0;

  /**
   * Set path for background dir, output dir, and temp dir.
   *
   * @param  string $bgDirPath
   * @param  string $outputPath
   * @param  string $tempPath
   */
  public function __construct(
    string $bgDirPath = '',
    string $outputPath = '',
    string $tempPath = ''
  ) {

    // Set default values
    $this->setBgDirPath($bgDirPath != '' ?: config('image-layer-builder.path.background_dir'));
    $this->setOutputPath($outputPath != '' ?: config('image-layer-builder.path.output_dir'));
    $this->setTempPath($tempPath != '' ?: config('image-layer-builder.path.temp_dir'));

    if ($fontPath = config('image-layer-builder.path.custom_font')) {
      $this->setCustomFont($fontPath);
    }
  }

  /**
   * Make a new instance statically, with default constructor values
   *
   * @param  string $bgDirPath
   * @param  string $outputPath
   * @param  string $tempPath
   * @return self
   */
  public static function make(
    string $bgDirPath = '',
    string $outputPath = '',
    string $tempPath = ''
  ): self {
    return new self($bgDirPath, $outputPath, $tempPath);
  }

  /**
   * Set background directory path to find all the available backgrounds.
   *
   * @param  string $bgDirPath
   * @return self
   */
  public function setBgDirPath(string $bgDirPath): self
  {
    $this->bgDirPath = $bgDirPath;

    return $this;
  }

  /**
   * Set output directory path to store the output file.
   *
   * @param  string $outputPath
   * @return self
   */
  public function setOutputPath(string $outputPath): self
  {
    $this->outputPath = $outputPath;

    return $this;
  }

  /**
   * Set temporary directory path to store temporary files.
   * This will be cleaned up after generation.
   *
   * @param  string $tempPath
   * @return self
   */
  public function setTempPath(string $tempPath): self
  {
    $this->tempPath = $tempPath;

    return $this;
  }

  /**
   * Set the background width to resize.
   *
   * @param  int $width
   * @return self
   */
  public function resizeBackgroundWidth(int $width): self
  {
    $this->bgWidth = $width;

    return $this;
  }

  /**
   * Set the background heght to resize.
   *
   * @param  int $height
   * @return self
   */
  public function resizeBackgroundHeight(int $height): self
  {
    $this->bgHeight = $height;

    return $this;
  }

  /**
   * return all available background file names in the background types directory
   *
   * @return array
   */
  public function getBackgrounds(): array
  {
    $backgrounds = \scandir($this->bgDirPath);

    return collect($backgrounds)
      ->diff(['..', '.'])
      ->reduce(function ($prev, $file) {

        $data = [
          'path' => $file,
          'name' => Str::of($file)->before('.')
        ];

        $prev->push($data);
        return $prev;
      }, collect([]))
      ->values()
      ->toArray();
  }

  /**
   * Set the background. This has to be an image that exists in the bigDirPath dir
   *
   * @param  string $background
   * @return self
   */
  public function setBackground(string $background): self
  {
    $this->backgroundName = $background;

    return $this;
  }

  /**
   * Set custom font path
   *
   * @param  string $fontPath
   * @return self
   */
  public function setCustomFont(string $fontPath): self
  {
    $this->fontPath = $fontPath;

    return $this;
  }

  /**
   * Add an image on top of the background including X-axis and Y-axis positions from center
   * Available positions are: left, right, center, top, bottom, including top-left combinations
   * This can be called more than once.
   * Accepts the following data for the image image,
   * string - Path of the image in filesystem.
   * string - URL of an image (allow_url_fopen must be enabled).
   * string - Binary image data.
   * string - Data-URL encoded image data.
   * string - Base64 encoded image data.
   * resource - PHP resource of type gd. (when using GD driver)
   * object - Imagick instance (when using Imagick driver)
   * object - Intervention\Image\Image instance
   * object - SplFileInfo instance (To handle Laravel file uploads via Symfony\Component\HttpFoundation\File\UploadedFile)
   * The border is an array of [width, color]
   *
   * @param  mixed $image
   * @param  int $widthResize
   * @param  int $heightResize
   * @param  bool $rounded
   * @param  string $position
   * @param  int $posX
   * @param  int $posY
   * @param  array|null $border
   * @return self
   */
  public function addImage(
    mixed $image,
    int $widthResize = 175,
    int $heightResize = 175,
    bool $rounded = true,
    string $position = 'center',
    int $posX = 0,
    int $posY = 0,
    ?array $border = null
  ): self {

    $this->images[] = $image;

    $this->tempImages[] = [
      'temp_path' => null,
      'start_pos' => $position,
      'pos_x' => $posX,
      'pos_y' => $posY,
      'rounded' => $rounded,
      'width_resize' => $widthResize,
      'height_resize' => $heightResize,
      'border' => $border
    ];

    return $this;
  }

  /**
   * Add given text with the options to the background image.
   * This method can be called more than once.
   *
   * @param  string $text
   * @param  string $position
   * @param  integer $offsetX
   * @param  integer $offsetY
   * @param  integer $fontSize
   * @param  string $color
   * @param  integer $angle
   * @return self
   */
  public function addText(
    string $text,
    string $position = 'top-center',
    int $offsetX = 0,
    int $offsetY = 0,
    int $fontSize = 32,
    string $color = '#fdf6e3',
    int $angle = 0
  ): self {

    $this->backgroundTexts[] = [
      'text' => $text,
      'position' => $position,
      'offset_x' => $offsetX,
      'offset_y' => $offsetY,
      'font_size' => $fontSize,
      'color' => $color,
      'angle' => $angle
    ];

    return $this;
  }

  /**
   * Generate background image
   *
   * @return self
   */
  public function generate(): self
  {
    $this->saveImagesTemporarily();
    $this->generateBackground();
    $this->getBackgroundCenteredDimensions();
    $this->applyBackgroundTexts();

    return $this;
  }

  /**
   * Exports the generated background image to a file with given format
   *
   * @param  string $filename
   * @return string
   */
  public function saveToFile(?string $filename = ''): string
  {
    $filename = $filename != '' ? "{$filename}.{$this->outputFormat}" : $this->generateFileName();
    $output = $this->getOutputStream();
    imagepng($output, "{$this->outputPath}/{$filename}");

    return $filename;
  }

  /**
   * Return the background image stream content.
   *
   * @return \GdImage
   */
  public function getOutputStream(): \GdImage
  {
    $rawStream = $this->getRawStream();
    $output = \imagecreatefromstring($rawStream);

    return $output;
  }

  /**
   * Return the background image stream content.
   *
   * @return string
   */
  public function getRawStream(): string
  {
    $stream = $this->background->stream($this->outputFormat, 100);
    $this->cleanup();
    return $stream->getContents();
  }

  /**
   * Store the image temporarily in a file to be inserted onto the background image instance
   *
   * @return void
   */
  protected function saveImagesTemporarily()
  {

    foreach ($this->images as $key => $image) {

      $tempPath = $this->tempPath . '/' . $this->generateFileName();

      $this->tempImages[$key]['temp_path'] = $tempPath;

      $imageWidthResize = $this->tempImages[$key]['width_resize'];
      $imageHeightResize = $this->tempImages[$key]['height_resize'];

      $src = Image::make($image)
        ->resize($imageWidthResize, $imageHeightResize, function ($constraint) {
          $constraint->aspectRatio();
          $constraint->upsize();
        });

      if ($this->tempImages[$key]['rounded']) {
        $src = $this->setImageRounded($src);
      }

      $border = $this->tempImages[$key]['border'];

      if (is_array($border) && count($border) >= 2) {
        $src = $this->setImageBorder($src, $border[0], $border[1]);
      }

      // Save the image temporarily
      $src->save($tempPath, 100, $this->outputFormat);
    }

    return $this;
  }

  /**
   * Create a new Intervention Image instance for the given background type.
   *
   * @return void
   */
  protected function generateBackground()
  {

    $backgroundPath = $this->getBackgroundPath();

    $this->background = Image::make($backgroundPath)
      ->resize($this->bgWidth, $this->bgHeight, function ($constraint) {
        $constraint->upsize();
      });

    foreach ($this->tempImages as $image) {
      $this->background->insert(
        $image['temp_path'],
        $image['start_pos'],
        $image['pos_x'],
        $image['pos_y'],
      );
    }
  }

  /**
   * Calls after background instance is created to insert all the texts onto the background instance.
   *
   * @return void
   */
  protected function applyBackgroundTexts()
  {
    foreach ($this->backgroundTexts as $applier) {

      $size = new Size($this->bgWidth, $this->bgHeight);
      $size->align($applier['position'], $applier['offset_x'], $applier['offset_y']);

      $this->background->text(
        $applier['text'],
        $size->pivot->x,
        $size->pivot->y,
        function ($font) use ($applier) {

          $fontType = $this->fontPath ?: 5;
          $font->file($fontType);

          $font->size($applier['font_size']);
          $font->color($applier['color']);
          $font->align('center');
          $font->valign('top');
          $font->angle($applier['angle']);
        }
      );
    }
  }

  /**
   * Set given image source to a rounded image
   *
   * @param  InterventionImage $src
   * @return InterventionImage
   */
  protected function setImageRounded(InterventionImage $src): InterventionImage
  {
    $width = $src->getWidth();
    $height = $src->getHeight();

    // draw a circle to make the image rounded and mask it
    $mask = Image::canvas($width, $height);

    $mask->circle($width, $width / 2, $height / 2, function ($draw) {
      $draw->background('#fff');
    });

    $src->mask($mask, true);

    return $src;
  }

  /**
   * Set given image source to a rounded image
   *
   * @param  InterventionImage $src
   * @return InterventionImage
   */
  protected function setImageBorder(InterventionImage $src, int $width, string $color): InterventionImage
  {
    $srcWidth = $src->getWidth();
    $srcHeight = $src->getHeight();

    $src->resize($srcWidth - $width, $srcHeight - $width, function ($constraint) {
      $constraint->aspectRatio();
      $constraint->upsize();
    });

    $border = Image::canvas($srcWidth, $srcHeight);

    $border->circle($srcWidth, $srcWidth  / 2, $srcHeight  / 2, function ($draw) use ($width, $color) {
      $draw->border($width, $color);
    });

    $border->insert($src, 'center');

    return $border;
  }

  /**
   * Sets the centered X and Y position of the selected background.
   *
   * @return void
   */
  protected function getBackgroundCenteredDimensions()
  {
    $this->bgCenteredX = $this->background->getWidth() / 2;
    $this->bgCenteredY = $this->background->getHeight() / 2;
  }

  /**
   * Performs cleanup after generation
   *
   * @return void
   */
  protected function cleanup()
  {
    foreach ($this->tempImages as $image) {
      FileSystem::delete($image['temp_path']);
    }
  }

  /**
   * Get the background path for given background
   *
   * @return string
   * @throws \Exception
   */
  protected function getBackgroundPath(): string
  {
    $path = "{$this->bgDirPath}/{$this->backgroundName}";
    if (!file_exists($path)) {
      throw new \Exception('Background image does not exist. Make sure the background directory is set and the image exists.');
    }

    return $path;
  }

  /**
   * Generates a random 15 character for a file name, with the output format
   *
   * @return string
   */
  protected function generateFileName(): string
  {
    $randomString = (new Str())->random(25);
    return "{$randomString}.{$this->outputFormat}";
  }
}
