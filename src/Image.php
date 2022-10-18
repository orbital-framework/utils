<?php
declare(strict_types=1);

namespace Core\Helper;

use \Exception;
use \GdImage;
use \Orbital\Http\Router;

abstract class Image {

    /**
     * Default image, in case of missing
     * @var string
     */
    protected static $default = null;

    /**
     * Set default image
     * @param string $default
     * @return void
     */
    public static function setDefault(string $default): void {
        self::$default = $default;
    }

    /**
     * Retrieve default image
     * @return string
     */
    public static function getDefault(): string {
        return ( self::$default )
            ? self::$default : Router::getUrl('/img/thumb/default.png');
    }

    /**
     * Transform URL into path
     * @param string $src
     * @return string
     */
    public static function toPath(string $src): string {

        $path = WWW;
        $url = Router::getUrl(). '/';

        $src = str_replace('http://', 'https://', $src);
        $src = str_replace($url, $path, $src);

        return $src;
    }

    /**
     * Transform path to URL
     * @param string $src
     * @return string
     */
    public static function toUrl(string $src): string {

        $path = WWW;
        $url = Router::getUrl(). '/';
        $src = str_replace($path, $url, $src);

        return $src;
    }

    /**
     * Open image resource
     * Fallbacks to default placeholder image if necessary
     * @param string $src
     * @return GdImage
     */
    public static function openResource(string $src): GdImage {

        $src = $src ? $src : self::getDefault();
        $src = self::toPath($src);

        $extension = strtolower(strrchr($src, '.'));

        // Open image
        switch( $extension ){
            case '.jpg':
            case '.jpeg':
                $resource = @imagecreatefromjpeg($src);
            break;
            case '.gif':
                $resource = @imagecreatefromgif($src);
            break;
            case '.png':
                $resource = @imagecreatefrompng($src);
            break;
            case '.webp':
                $resource = @imagecreatefromwebp($src);
            break;
            default:
                $resource = false;
            break;
        }

        if( !$resource ){
            throw new Exception('Image resource could not be determined for "'. $src. '" file.');
        }

        // Adjust orientation
        $exif = @exif_read_data($resource);

        if( !$exif
            OR !isset($exif['Orientation'])
            OR empty($exif['Orientation']) ){
            return $resource;
        }

        switch( $exif['Orientation'] ){
            case 3:
                $resource = imagerotate($resource, 180, 0);
            break;
            case 6:
                $resource = imagerotate($resource, -90, 0);
            break;
            case 8:
                $resource = imagerotate($resource, 90, 0);
            break;
        }

        return $resource;
    }

    /**
     * Close image resource
     * @param GdImage $resource
     * @return void
     */
    public static function closeResource(GdImage $resource): void {
        imagedestroy($resource);
    }

    /**
     * Retrieve resource dimensions
     * @param GdImage $resource
     * @return array
     */
    public static function getDimensions(GdImage $resource): array {

        $width = imagesx($resource);
        $height = imagesy($resource);

        if( $height < $width ){
            $format = 'landscape';
        }elseif( $height > $width ){
            $format = 'portrait';
        }else{
            $format = 'square';
        }

        $dimensions = array(
            'format' => $format,
            'width' => $width,
            'height' => $height,
            'ratio' => $width / $height,
            'inverse' => $height / $width
        );

        return $dimensions;
    }

    /**
     * Retrieve the appropriate dimensions specs for given resource
     * @param GdImage $resource
     * @param string $format
     * @param int $width
     * @param int $height
     * @return array
     */
    public static function bestDimensions(GdImage $resource, string $format, int $width = null, int $height = null): array {

        $current = self::getDimensions($resource);

        // Detect best format when automatic
        if( $format === 'auto' ){

            if( $height < $width ){
                $format = 'landscape';
            }elseif( $height > $width ){
                $format = 'portrait';
            }else{
                $format = 'square';
            }

        }

        // Adjust size based on format
        if( $format === 'square' ){
            $height = $width;

        }elseif( $format === 'portrait' ){
            $ratio = $current['ratio'];
            $width = $height * $ratio;

        }elseif( $format === 'landscape' ){
            $ratio = $current['inverse'];
            $height = $width * $ratio;

        }elseif( $format === 'optimal' ){

            $widthRatio = $current['width'] / $width;
            $heightRatio = $current['height'] / $height;

            if( $heightRatio < $widthRatio ){
                $optimalRatio = $heightRatio;
            } else {
                $optimalRatio = $widthRatio;
            }

            $width = $current['width'] / $optimalRatio;
            $height = $current['height'] / $optimalRatio;

        }elseif( $format === 'scale' ){

            $scale = $width;
            $width = $current['width'] * $scale;
            $height = $current['height'] * $scale;

        }

        $dimensions = array(
            'format' => $format,
            'width' => $width,
            'height' => $height,
            'ratio' => $width / $height,
            'inverse' => $height / $width
        );

        return $dimensions;
    }

    /**
     * Create canvas to image
     * @param array $dimensions
     * @param array $color
     * @return GdImage
     */
    public static function createCanvas(array $dimensions, array $color = array()): GdImage {

        $canvas = imagecreatetruecolor(
            $dimensions['width'],
            $dimensions['height']
        );

        // Alpha color
        if( count($color) === 4 ){

            $fill = imagecolorallocatealpha(
                $canvas,
                $color[0],
                $color[1],
                $color[2],
                $color[3]
            );

            imagealphablending($canvas, false);
            imagefill($canvas, 0, 0, $fill);
            imagesavealpha($canvas, true);

        // Normal color
        }else{

            $fill = imagecolorallocate(
                $canvas,
                $color[0],
                $color[1],
                $color[2]
            );

            imagefill($canvas, 0, 0, $fill);

        }

        return $canvas;
    }

    /**
     * Resize image into canvas
     * @param GdImage $resource
     * @param array $dimensions
     * @return GdImage
     */
    public static function resize(GdImage $resource, array $dimensions): GdImage {

        $canvas = self::createCanvas($dimensions, array(255, 255, 255, 0));
        $current = self::getDimensions($resource);

        imagecopyresampled(
            $canvas,
            $resource,
            0, 0,
            0, 0,
            $dimensions['width'], $dimensions['height'],
            $current['width'], $current['height'],
        );

        self::closeResource($resource);

        return $canvas;
    }

    /**
     * Crop image into canvas
     * @param GdImage $resource
     * @param array $dimensions
     * @return GdImage
     */
    public static function crop(GdImage $resource, array $dimensions): GdImage {

        $crop = self::createCanvas($dimensions, array(255, 255, 255, 0));
        $current = self::getDimensions($resource);

        // Find center - this will be used for the crop
        $cropStartX = ( $current['width'] / 2 ) - ( $dimensions['width'] / 2 );
        $cropStartY = ( $current['height'] / 2 ) - ( $dimensions['height'] / 2 );

        // Now crop from center to exact requested size
        imagecopyresampled(
            $crop,
            $resource,
            0, 0,
            $cropStartX, $cropStartY,
            $dimensions['width'], $dimensions['height'],
            $dimensions['width'], $dimensions['height']
        );

        self::closeResource($resource);

        return $crop;
    }

    /**
     * Add watermark to image
     * @param GdImage $resource
     * @param string $watermarkFile
     * @return GdImage
     */
    public static function addWatermark(GdImage $resource, string $watermarkFile): GdImage{

        $watermark = self::openResource($watermarkFile);
        $dimensions = self::getDimensions($watermark);
        $current = self::getDimensions($resource);

        $destX = ($current['width'] - $dimensions['width']) / 2;
        $destY = ($current['height'] - $dimensions['height']) / 2;

        imagecopy(
            $resource,
            $watermark,
            $destX, $destY,
            0, 0,
            $dimensions['width'],
            $dimensions['height']
        );

        self::closeResource($watermark);

        return $resource;
    }

    /**
     * Apply filter to the resource
     * @param GdImage $resource
     * @param string $filter
     * @return void
     */
    public static function filter(GdImage $resource, string $filter): void {

        if( $filter === 'blur' ){
            imagefilter($resource, IMG_FILTER_GAUSSIAN_BLUR);
        }elseif( $filter === 'grayscale' ){
            imagefilter($resource, IMG_FILTER_GRAYSCALE);
        }elseif( $filter === 'invert' ){
            imagefilter($resource, IMG_FILTER_NEGATE);
        }

    }

    /**
     * Save image
     * @param GdImage $resource
     * @param string $savePath
     * @param int $imageQuality
     * @return void
     */
    public static function save(GdImage $resource, string $savePath, int $imageQuality = 90): void {

        $extension = strrchr($savePath, '.');
        $extension = strtolower($extension);

        switch( $extension ){
            case '.jpg':
            case '.jpeg':
                imagejpeg($resource, $savePath, $imageQuality);
            break;
            case '.gif':
                imagegif($resource, $savePath);
            break;
            case '.webp':
                imagewebp($resource, $savePath, $imageQuality);
            case '.png':

                $scaleQuality = (int) round( ($imageQuality/100) * 9 );
                $invertScaleQuality = 9 - $scaleQuality;

                imagepng($resource, $savePath, $invertScaleQuality);

            break;
            default:
                // No extension - No save.
            break;
        }

    }

    /**
     * Retrieve thumbnail from image
     * @param string $src
     * @param array $specs
     * @return string
     */
    public static function thumbnail(string $src, array $specs): string {

        $src = $src ? $src : self::getDefault();
        $src = self::toPath($src);

        $name = pathinfo($src, PATHINFO_BASENAME);
        $name = strtolower($name);

        $format = $specs['format'];
        $width = $specs['width'];
        $height = $specs['height'];

        $quality = isset( $specs['quality'] )
            ? $specs['quality'] : 90;
        $watermark = isset( $specs['watermark'] )
            ? $specs['watermark'] : null;
        $directory = isset( $specs['directory'] )
            ? $specs['directory'] : WWW. 'thumbs/';
        $filter = isset( $specs['filter'] )
            ? $specs['filter'] : null;

        $thumbnail = $format. '_w'. $width. '_h'. $height. '_q'. $quality. '_'. $name;
        $thumbnail = str_replace('.png', '.jpg', $thumbnail); // Prefer JPG format
        $path = $directory. $thumbnail;

        // Create folder if necessary
        if( !is_dir($directory) ){
            mkdir($directory, 0777, true);
        }

        // Create file if necessary
        if( !file_exists($path) ){

            $resource = self::openResource($src);

            if( $format === 'crop' ){
                $dimensions = self::bestDimensions($resource, 'optimal', $width, $height);
                $resource = self::resize($resource, $dimensions);
                $resource = self::crop($resource, $specs);
            }else{
                $resource = self::resize($resource, $specs);
            }

            if( $watermark ){
                $resource = self::addWatermark($resource, $watermark);
            }

            if( isset($filter) ){
                self::filter($resource, $filter);
            }

            self::save($resource, $path, $quality);

            // PHP webp format need a lot of optimizations
            // file size is bigger than original
            // self::save($resource, $path. '.webp', $quality);

            self::closeResource($resource);

        }

        return $path;
    }

    /**
     * Retrieve thumbnail URL
     * @param string $src
     * @param array $specs
     * @return string
     */
    public static function thumbnailUrl(string $src, array $specs): string {

        $path = self::thumbnail($src, $specs);
        $url = self::toUrl($path);

        return $url;
    }

}