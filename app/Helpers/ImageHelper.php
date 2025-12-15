<?php
namespace App\Helpers;

class ImageHelper
{
    /**
     * Crop and resize an image to specified dimensions.
     *
     * @param string $srcPath Path to source image
     * @param string $destPath Path to save the processed image
     * @param int $targetWidth Desired width
     * @param int $targetHeight Desired height
     * @return bool
     */
    // public static function cropAndResize($srcPath, $destPath, $targetWidth, $targetHeight)
    // {
    //     list($width, $height, $type) = getimagesize($srcPath);

    //     // Reject if image is smaller than desired size
    //     if ($width < $targetWidth || $height < $targetHeight) {
    //         return false;
    //     }

    //     // Create image resource from file
    //     switch ($type) {
    //         case IMAGETYPE_JPEG:
    //             $srcImg = imagecreatefromjpeg($srcPath);
    //             break;
    //         case IMAGETYPE_PNG:
    //             $srcImg = imagecreatefrompng($srcPath);
    //             break;
    //         case IMAGETYPE_GIF:
    //             $srcImg = imagecreatefromgif($srcPath);
    //             break;
    //         case IMAGETYPE_WEBP:
    //             if (function_exists('imagecreatefromwebp')) {
    //                 $srcImg = imagecreatefromwebp($srcPath);
    //             } else {
    //                 return false;
    //             }
    //             break;
    //         default:
    //             return false;
    //     }

    //     // Center crop to square
    //     $minDim = min($width, $height);
    //     $srcX = ($width - $minDim) / 2;
    //     $srcY = ($height - $minDim) / 2;

    //     $cropped = imagecrop($srcImg, [
    //         'x' => $srcX,
    //         'y' => $srcY,
    //         'width' => $minDim,
    //         'height' => $minDim
    //     ]);
    //     if (!$cropped) {
    //         imagedestroy($srcImg);
    //         return false;
    //     }

    //     // Create final image with desired size
    //     $finalImg = imagecreatetruecolor($targetWidth, $targetHeight);
    //     // Handle transparency
    //     if ($type == IMAGETYPE_PNG || $type == IMAGETYPE_GIF) {
    //         imagecolortransparent($finalImg, imagecolorallocatealpha($finalImg, 0, 0, 0, 127));
    //         imagealphablending($finalImg, false);
    //         imagesavealpha($finalImg, true);
    //     }

    //     // Resample to target size
    //     imagecopyresampled($finalImg, $cropped, 0, 0, 0, 0, $targetWidth, $targetHeight, $minDim, $minDim);

    //     // Save the image
    //     switch ($type) {
    //         case IMAGETYPE_JPEG:
    //             imagejpeg($finalImg, $destPath, 90);
    //             break;
    //         case IMAGETYPE_PNG:
    //             imagepng($finalImg, $destPath, 9);
    //             break;
    //         case IMAGETYPE_GIF:
    //             imagegif($finalImg, $destPath);
    //             break;
    //         case IMAGETYPE_WEBP:
    //             if (function_exists('imagewebp')) {
    //                 imagewebp($finalImg, $destPath, 90);
    //             } else {
    //                 imagedestroy($srcImg);
    //                 imagedestroy($cropped);
    //                 imagedestroy($finalImg);
    //                 return false;
    //             }
    //             break;
    //     }

    //     // Free memory
    //     imagedestroy($srcImg);
    //     imagedestroy($cropped);
    //     imagedestroy($finalImg);

    //     return true;
    // }
   public static function cropAndResize($srcPath, $destPath, $targetWidth, $targetHeight)
{
    if (!file_exists($srcPath)) {
        return false;
    }

    list($width, $height, $type) = getimagesize($srcPath);

    switch ($type) {
        case IMAGETYPE_JPEG:
            $srcImg = imagecreatefromjpeg($srcPath);
            break;
        case IMAGETYPE_PNG:
            $srcImg = imagecreatefrompng($srcPath);
            break;
        case IMAGETYPE_GIF:
            $srcImg = imagecreatefromgif($srcPath);
            break;
        case IMAGETYPE_WEBP:
            $srcImg = imagecreatefromwebp($srcPath);
            break;
        default:
            return false;
    }

    // Calculate new size preserving aspect ratio
    $ratio = min($targetWidth / $width, $targetHeight / $height);
    $newWidth  = (int)($width * $ratio);
    $newHeight = (int)($height * $ratio);

    // Create final canvas
    $finalImg = imagecreatetruecolor($newWidth, $newHeight);

    // Preserve transparency for PNG/WebP
    if ($type == IMAGETYPE_PNG || $type == IMAGETYPE_WEBP) {
        imagealphablending($finalImg, false);
        imagesavealpha($finalImg, true);
        $transparent = imagecolorallocatealpha($finalImg, 0, 0, 0, 127);
        imagefilledrectangle($finalImg, 0, 0, $newWidth, $newHeight, $transparent);
    }

    // Resize image with aspect ratio
    imagecopyresampled(
        $finalImg, $srcImg,
        0, 0, 0, 0,
        $newWidth, $newHeight,
        $width, $height
    );

    // Save image
    switch ($type) {
        case IMAGETYPE_JPEG:
            imagejpeg($finalImg, $destPath, 90);
            break;
        case IMAGETYPE_PNG:
            imagepng($finalImg, $destPath, 9);
            break;
        case IMAGETYPE_WEBP:
            imagewebp($finalImg, $destPath, 90);
            break;
        case IMAGETYPE_GIF:
            imagegif($finalImg, $destPath);
            break;
    }

    imagedestroy($srcImg);
    imagedestroy($finalImg);

    return true;
}


public static function resizeToPortfolioDimensions($srcPath, $destPath, $targetWidth = 400)
    {
        if (!is_file($srcPath) || $targetWidth <= 0) {
            return false;
        }

        $info = @getimagesize($srcPath);
        if ($info === false) {
            return false;
        }

        list($width, $height, $type) = $info;
        if ($width <= 0 || $height <= 0) {
            return false;
        }

        // Calculate new height to maintain aspect ratio (natural height for masonry)
        $scale = $targetWidth / $width;
        $newHeight = intval($height * $scale);

        // Ensure reasonable height range for masonry effect
        if ($newHeight < 250) {
            $newHeight = 250; // Minimum height for very short images
        } elseif ($newHeight > 600) {
            $newHeight = 600; // Maximum height for very tall images
        }

        // Create source image
        switch ($type) {
            case IMAGETYPE_JPEG:
                $srcImg = @imagecreatefromjpeg($srcPath); break;
            case IMAGETYPE_PNG:
                $srcImg = @imagecreatefrompng($srcPath); break;
            case IMAGETYPE_GIF:
                $srcImg = @imagecreatefromgif($srcPath); break;
            case IMAGETYPE_WEBP:
                $srcImg = function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($srcPath) : false; break;
            default:
                $srcImg = false;
        }

        if (!$srcImg) {
            return false;
        }

        // Create destination image with natural dimensions
        $dstImg = imagecreatetruecolor($targetWidth, $newHeight);

        // Handle transparency
        if (in_array($type, [IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP], true)) {
            imagealphablending($dstImg, false);
            imagesavealpha($dstImg, true);
            $transparent = imagecolorallocatealpha($dstImg, 0, 0, 0, 127);
            imagefilledrectangle($dstImg, 0, 0, $targetWidth, $newHeight, $transparent);
        } else {
            $white = imagecolorallocate($dstImg, 255, 255, 255);
            imagefilledrectangle($dstImg, 0, 0, $targetWidth, $newHeight, $white);
        }

        // Resize maintaining aspect ratio
        if (!imagecopyresampled($dstImg, $srcImg, 0, 0, 0, 0, $targetWidth, $newHeight, $width, $height)) {
            imagedestroy($srcImg);
            imagedestroy($dstImg);
            return false;
        }

        // Save in original format
        $ok = false;
        switch ($type) {
            case IMAGETYPE_JPEG:
                $ok = imagejpeg($dstImg, $destPath, 90); break;
            case IMAGETYPE_PNG:
                $ok = imagepng($dstImg, $destPath, 9); break;
            case IMAGETYPE_GIF:
                $ok = imagegif($dstImg, $destPath); break;
            case IMAGETYPE_WEBP:
                $ok = function_exists('imagewebp') ? imagewebp($dstImg, $destPath, 90) : false; break;
            default:
                $ok = false;
        }

        imagedestroy($srcImg);
        imagedestroy($dstImg);
        return (bool) $ok;
    }
}
