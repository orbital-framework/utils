<?php

class Resize {

    private $image;
    private $width;
    private $height;
    private $imageResized;

    /**
     * CONSTRUCTOR
     * @param string $file
     */
    public function __construct($file){

        $this->image = $this->openImage($file);
        $this->width  = imagesx($this->image);
        $this->height = imagesy($this->image);

    }

    /**
     * Open image for resizing
     * @param string $file
     * @return resource
     */
    private function openImage($file){

        $extension = strtolower(strrchr($file, '.'));

        switch( $extension ){
            case '.jpg':
            case '.jpeg':
                $img = @imagecreatefromjpeg($file);
            break;
            case '.gif':
                $img = @imagecreatefromgif($file);
            break;
            case '.png':
                $img = @imagecreatefrompng($file);
            break;
            default:
                $img = false;
            break;
        }

        $img = $this->adjustImageOrientation($file, $img);

        return $img;
    }

    /**
     * Automatically adjust image orientation
     * @param string $file
     * @param resource img
     * @return resource
     */
    public function adjustImageOrientation($file, $img){

        $exif = @exif_read_data($file);

        if( !$exif
            OR !isset($exif['Orientation'])
            OR empty($exif['Orientation']) ){
            return $img;
        }

        switch( $exif['Orientation'] ){
            case 3:
                $img = imagerotate($img, 180, 0);
            break;
            case 6:
                $img = imagerotate($img, -90, 0);
            break;
            case 8:
                $img = imagerotate($img, 90, 0);
            break;
        }

        return $img;
    }

    /**
     * Add transparent background to image
     * @param int $width
     * @param int $height
     * @return resource
     */
    public function createTransparentImage($width, $height){

        $img = imagecreatetruecolor($width, $height);
        imagealphablending($img, TRUE);

        $colorTransparent = imagecolorallocatealpha($img, 0, 0, 0, 127);
        imagefill($img, 0, 0, $colorTransparent);
        imagesavealpha($img, TRUE);

        return $img;
    }

    /**
     * Resize image
     * @param int $newWidth
     * @param int $newHeight
     * @param string $option
     * @return void
     */
    public function resizeImage($newWidth, $newHeight, $option = 'auto'){

        $optionArray = $this->getDimensions($newWidth, $newHeight, $option);
        $optimalWidth = $optionArray['optimalWidth'];
        $optimalHeight = $optionArray['optimalHeight'];

        $this->imageResized = $this->createTransparentImage($optimalWidth, $optimalHeight);

        imagecopyresampled(
            $this->imageResized,
            $this->image,
            0, 0, 0, 0,
            $optimalWidth, $optimalHeight,
            $this->width, $this->height
        );

        if( $option == 'crop' ){
            $this->crop($optimalWidth, $optimalHeight, $newWidth, $newHeight);
        }

    }

    /**
     * Retrieve optimal dimensions for image
     * @param int $newWidth
     * @param int $newHeight
     * @param string $option
     * @return array
     */
    private function getDimensions($newWidth, $newHeight, $option){

       switch( $option ){
            case 'exact':
                $optimalWidth = $newWidth;
                $optimalHeight= $newHeight;
            break;
            case 'portrait':
                $optimalWidth = $this->getSizeByFixedHeight($newHeight);
                $optimalHeight= $newHeight;
            break;
            case 'landscape':
                $optimalWidth = $newWidth;
                $optimalHeight= $this->getSizeByFixedWidth($newWidth);
            break;
            case 'auto':
                $optionArray = $this->getSizeByAuto($newWidth, $newHeight);
                $optimalWidth = $optionArray['optimalWidth'];
                $optimalHeight = $optionArray['optimalHeight'];
            break;
            case 'crop':
                $optionArray = $this->getOptimalCrop($newWidth, $newHeight);
                $optimalWidth = $optionArray['optimalWidth'];
                $optimalHeight = $optionArray['optimalHeight'];
            break;
        }

        return array(
            'optimalWidth' => $optimalWidth,
            'optimalHeight' => $optimalHeight
        );
    }

    /**
     * Retrieve size by fixed height
     * @param int $newHeight
     * @return int
     */
    private function getSizeByFixedHeight($newHeight){
        $ratio = $this->width / $this->height;
        $newWidth = $newHeight * $ratio;
        return $newWidth;
    }

    /**
     * Retrieve size by fixed width
     * @param int $newWidth
     * @return int
     */
    private function getSizeByFixedWidth($newWidth){
        $ratio = $this->height / $this->width;
        $newHeight = $newWidth * $ratio;
        return $newHeight;
    }

    /**
     * Retrieve automatic size by width and height
     * @param int $newWidth
     * @param int $newHeight
     * @return array
     */
    private function getSizeByAuto($newWidth, $newHeight){

        // Image to be resized is wider (landscape)
        if( $this->height < $this->width ){
            $optimalWidth = $newWidth;
            $optimalHeight= $this->getSizeByFixedWidth($newWidth);

        // Image to be resized is taller (portrait)
        }elseif( $this->height > $this->width ){
            $optimalWidth = $this->getSizeByFixedHeight($newHeight);
            $optimalHeight= $newHeight;

        // Image to be resized is a square
        }else{

            if( $newHeight < $newWidth ){
                $optimalWidth = $newWidth;
                $optimalHeight= $this->getSizeByFixedWidth($newWidth);

            }elseif( $newHeight > $newWidth ){
                $optimalWidth = $this->getSizeByFixedHeight($newHeight);
                $optimalHeight= $newHeight;

            }else{
                $optimalWidth = $newWidth;
                $optimalHeight= $newHeight;
            }

        }

        return array(
            'optimalWidth' => $optimalWidth,
            'optimalHeight' => $optimalHeight
        );
    }

    /**
     * Retrieve optimal crop dimensions
     * @param int $newWidth
     * @param int $newHeight
     * @return array
     */
    private function getOptimalCrop($newWidth, $newHeight){

        $heightRatio = $this->height / $newHeight;
        $widthRatio = $this->width / $newWidth;

        if( $heightRatio < $widthRatio ){
            $optimalRatio = $heightRatio;
        } else {
            $optimalRatio = $widthRatio;
        }

        $optimalHeight = $this->height / $optimalRatio;
        $optimalWidth = $this->width / $optimalRatio;

        return array(
            'optimalWidth' => $optimalWidth,
            'optimalHeight' => $optimalHeight
        );
    }

    /**
     * Crop image
     * @param int $optimalWidth
     * @param int $optimalHeight
     * @param int $newWidth
     * @param int $newHeight
     * @return void
     */
    public function crop($optimalWidth, $optimalHeight, $newWidth, $newHeight){

        // Find center - this will be used for the crop
        $cropStartX = ( $optimalWidth / 2 ) - ( $newWidth / 2 );
        $cropStartY = ( $optimalHeight / 2 ) - ( $newHeight / 2 );
        $crop = $this->imageResized;

        // Now crop from center to exact requested size
        $this->imageResized = $this->createTransparentImage($newWidth , $newHeight);

        imagecopyresampled(
            $this->imageResized,
            $crop,
            0, 0, $cropStartX, $cropStartY,
            $newWidth, $newHeight,
            $newWidth, $newHeight
        );

    }

    /**
     * Add watermark to image
     * @param string $file
     * @return void
     */
    public function addWatermark($file){

        $watermark = $this->openImage($file);
        $watermarkWidth = imagesx($watermark);
        $watermarkHeight = imagesy($watermark);

        $destX = (imagesx($this->imageResized) - $watermarkWidth) / 2;
        $destY = (imagesy($this->imageResized) - $watermarkHeight) / 2;

        imagecopy(
            $this->imageResized,
            $watermark,
            $destX, $destY, 0, 0,
            $watermarkWidth,
            $watermarkHeight
        );

        imagedestroy($watermark);
    }

    /**
     * Save image
     * @param string $savePath
     * @param int $imageQuality
     * @return void
     */
    public function saveImage($savePath, $imageQuality = 100){

        $extension = strrchr($savePath, '.');
        $extension = strtolower($extension);

        switch( $extension ){
            case '.jpg':
            case '.jpeg':
                imagejpeg($this->imageResized, $savePath, $imageQuality);
            break;
            case '.gif':
                imagegif($this->imageResized, $savePath);
            break;
            case '.png':

                $scaleQuality = round(($imageQuality/100) * 9);
                $invertScaleQuality = 9 - $scaleQuality;

                imagepng($this->imageResized, $savePath, $invertScaleQuality);
            break;
            default:
                // No extension - No save.
            break;
        }

        imagedestroy($this->imageResized);
    }

}