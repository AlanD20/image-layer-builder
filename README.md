# Laravel Image Layer Builder

[![Latest Version on Packagist](https://img.shields.io/packagist/v/aland20/image-layer-builder.svg?style=flat-square)](https://packagist.org/packages/aland20/image-layer-builder)
[![Total Downloads](https://img.shields.io/packagist/dt/aland20/image-layer-builder.svg?style=flat-square)](https://packagist.org/packages/aland20/image-layer-builder)

This package allows to merge small images onto a background, in addition to add texts to the background. Under the hood the package uses [`Intervention/v2/Image`](https://image.intervention.io/v2) to perform the generation.

## Installation

You can install the package via composer:

```bash
composer require aland20/image-layer-builder
```

- Publish config file

```bash
php artisan vendor:publish --provider="Aland20\ImageLayerBuilder\ImageLayerBuilderServiceProvider"
```

## Usage

- **Note**: You have to set a custom font otherwise, you may not be able to use font size or other font properties.

- Checkout [`Intervention/v2/Image::text`](https://image.intervention.io/v2/api/text) for more information.
- You may want to customize the background and image resize as you desire.

```php

use Illuminate\Http\Request;
use Aland20\ImageLayerBuilder\ImageLayerBuilder;


class ImageController extends Controller
{

    public function generate(Request $request)
    {
        $avatar = $request->file('avatar');
        $logo = $request->file('logo');
        $text = $request->text;

        $output = ImageLayerBuilder::make()
            // Set the background, it must be a valid full file name in the bgDirPath. Also, you may dynamically set it via requests.
            ->setBackground('my-custom-bg.png')
            // You may set the width and height for the background to resize. Default is 1200x750
            ->resizeBackgroundWidth(1000)
            ->resizeBackgroundHeight(750)
            // Add an image for the avatar, you may want to set the width and height resize,  positions, X-axis, Y-axis and many more as well.
            ->addImage($avatar, widthResize: 125, heightResize: 125, border: [3, '#fff'])
            // You may add more than one image on top of the background and change the position
            ->addImage($logo, rounded: false, position: 'bottom-right')
            // Add Text to the background, you may give some offset to get into the range
            ->addText('Welcome', position: 'bottom-center', offsetY: 25, angle: 25)
            // Add Text to the background, position starts from center
            ->addText($text, 'center', offsetY: -150)
            // Generate the instance
            ->generate();
        
        // Save to file
        $filename = $output->saveToFile();

        // Return raw stream data in string
        $rawStream = $output->getRawStream();

        return response($rawStream)->header('Content-type', 'image/png');
    }
}
```

- You may want to return all the available background to show it in frontend

```php

use Aland20\ImageLayerBuilder;

$backgrounds = ImageLayerBuilder::make()->getBackgrounds();

foreach($backgrounds as $background):
    
    echo $background['path']; // background image file with extension
    echo $background['name']; // background image file without extension

endforeach;

```

### Available Public Methods

```php

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
    ): self {}

  /**
   * Set background directory path to find all the available backgrounds.
   *
   * @param  string $bgDirPath
   * @return self
   */
  public function setBgDirPath(string $bgDirPath): self {}

  /**
   * Set output directory path to store the output file.
   *
   * @param  string $outputPath
   * @return self
   */
  public function setOutputPath(string $outputPath): self {}

  /**
   * Set temporary directory path to store temporary files.
   * This will be cleaned up after generation.
   *
   * @param  string $tempPath
   * @return self
   */
  public function setTempPath(string $tempPath): self {}

  /**
   * Set the background width to resize.
   *
   * @param  int $width
   * @return self
   */
  public function resizeBackgroundWidth(int $width): self {}

  /**
   * Set the background heght to resize.
   *
   * @param  int $height
   * @return self
   */
  public function resizeBackgroundHeight(int $height): self {}

  /**
   * return all available background file names in the background types directory
   *
   * @return array
   */
  public function getBackgrounds(): array {}

  /**
   * Set the background. This has to be an image that exists in the bigDirPath dir
   *
   * @param  string $background
   * @return self
   */
  public function setBackground(string $background): self {}

  /**
   * Set custom font path
   *
   * @param  string $fontPath
   * @return self
   */
  public function setCustomFont(string $fontPath): self {}

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
    int $posY = 0
  ): self {}

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

  /**
   * Generate background image
   *
   * @return self
   */
  public function generate(): self {}

  /**
   * Exports the generated background image to a file with given format
   *
   * @param  string $filename
   * @return string
   */
  public function saveToFile(?string $filename = ''): string

  /**
   * Return the background image stream content.
   *
   * @return \GdImage
   */
  public function getOutputStream(): \GdImage {}

  /**
   * Return the background image stream content.
   *
   * @return string
   */
  public function getRawStream(): string {}
```

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Laravel Package Boilerplate

This package was generated using the [Laravel Package Boilerplate](https://laravelpackageboilerplate.com).
