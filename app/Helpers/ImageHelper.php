<?php
namespace App\Helpers;
use Illuminate\Http\Request;


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
    public static function cropAndResize($srcPath, $destPath, $targetWidth, $targetHeight, $shouldCrop = true)
    {
        list($width, $height, $type) = getimagesize($srcPath);

        // Reject if image is smaller than desired size
        if ($width < $targetWidth || $height < $targetHeight) {
            return false;
        }

        // Create image resource from file
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
                if (function_exists('imagecreatefromwebp')) {
                    $srcImg = imagecreatefromwebp($srcPath);
                } else {
                    return false;
                }
                break;
            default:
                return false;
        }


        $sourceToCopy = $srcImg;
        $srcW = $width;
        $srcH = $height;

        if ($shouldCrop) {
            // Center crop to square
            $minDim = min($width, $height);
            $srcX = ($width - $minDim) / 2;
            $srcY = ($height - $minDim) / 2;

            $cropped = imagecrop($srcImg, [
                'x' => $srcX,
                'y' => $srcY,
                'width' => $minDim,
                'height' => $minDim
            ]);
            if (!$cropped) {
                imagedestroy($srcImg);
                return false;
            }
            $sourceToCopy = $cropped;
            $srcW = $minDim;
            $srcH = $minDim;
        }

        // Create final image with desired size
        $finalImg = imagecreatetruecolor($targetWidth, $targetHeight);
        // Handle transparency
        if ($type == IMAGETYPE_PNG || $type == IMAGETYPE_GIF) {
            imagecolortransparent($finalImg, imagecolorallocatealpha($finalImg, 0, 0, 0, 127));
            imagealphablending($finalImg, false);
            imagesavealpha($finalImg, true);
        }

        // Resample to target size
        imagecopyresampled($finalImg, $sourceToCopy, 0, 0, 0, 0, $targetWidth, $targetHeight, $srcW, $srcH);

        // Save the image
        switch ($type) {
            case IMAGETYPE_JPEG:
                imagejpeg($finalImg, $destPath, 90);
                break;
            case IMAGETYPE_PNG:
                imagepng($finalImg, $destPath, 9);
                break;
            case IMAGETYPE_GIF:
                imagegif($finalImg, $destPath);
                break;
            case IMAGETYPE_WEBP:
                if (function_exists('imagewebp')) {
                    imagewebp($finalImg, $destPath, 90);
                } else {
                    imagedestroy($srcImg);
                    imagedestroy($cropped);
                    imagedestroy($finalImg);
                    return false;
                }
                break;
        }

        // Free memory
        imagedestroy($srcImg);
        if (isset($cropped)) {
            imagedestroy($cropped);
        }
        imagedestroy($finalImg);

        return true;
    }
    //  public static function cropAndResize($srcPath, $destPath, $targetWidth, $targetHeight)
    // {
    //     if (!file_exists($srcPath)) {
    //         return false;
    //     }

    //     [$width, $height, $type] = getimagesize($srcPath);

    //     // ❌ Reject small images
    //     if ($width < $targetWidth || $height < $targetHeight) {
    //         return false;
    //     }

    //     switch ($type) {
    //         case IMAGETYPE_JPEG:
    //             $srcImg = \imagecreatefromjpeg($srcPath);
    //             break;
    //         case IMAGETYPE_PNG:
    //             $srcImg = \imagecreatefrompng($srcPath);
    //             break;
    //         case IMAGETYPE_WEBP:
    //             $srcImg = \imagecreatefromwebp($srcPath);
    //             break;
    //         default:
    //             return false;
    //     }

    //     $ratio = min($targetWidth / $width, $targetHeight / $height);
    //     $newWidth  = (int)($width * $ratio);
    //     $newHeight = (int)($height * $ratio);

    //     $finalImg = \imagecreatetruecolor($newWidth, $newHeight);

    //     if ($type == IMAGETYPE_PNG || $type == IMAGETYPE_WEBP) {
    //         \imagealphablending($finalImg, false);
    //        \imagesavealpha($finalImg, true);
    //     }

    //     \imagecopyresampled(
    //         $finalImg,
    //         $srcImg,
    //         0, 0, 0, 0,
    //         $newWidth, $newHeight,
    //         $width, $height
    //     );

    //     switch ($type) {
    //         case IMAGETYPE_JPEG:
    //             \imagejpeg($finalImg, $destPath, 90);
    //             break;
    //         case IMAGETYPE_PNG:
    //             \imagepng($finalImg, $destPath, 9);
    //             break;
    //         case IMAGETYPE_WEBP:
    //             \imagewebp($finalImg, $destPath, 90);
    //             break;
    //     }

    //     imagedestroy($srcImg);
    //     imagedestroy($finalImg);

    //     return true;
    // }




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
            \imagealphablending($dstImg, false);
            \imagesavealpha($dstImg, true);
            $transparent = \imagecolorallocatealpha($dstImg, 0, 0, 0, 127);
            \imagefilledrectangle($dstImg, 0, 0, $targetWidth, $newHeight, $transparent);
        } else {
            $white = \imagecolorallocate($dstImg, 255, 255, 255);
            \imagefilledrectangle($dstImg, 0, 0, $targetWidth, $newHeight, $white);
        }

        // Resize maintaining aspect ratio
        if (!\imagecopyresampled($dstImg, $srcImg, 0, 0, 0, 0, $targetWidth, $newHeight, $width, $height)) {
            imagedestroy($srcImg);
            imagedestroy($dstImg);
            return false;
        }

        // Save in original format
        $ok = false;
        switch ($type) {
            case IMAGETYPE_JPEG:
                $ok = \imagejpeg($dstImg, $destPath, 90); break;
            case IMAGETYPE_PNG:
                $ok = \imagepng($dstImg, $destPath, 9); break;
            case IMAGETYPE_GIF:
                $ok = \imagegif($dstImg, $destPath); break;
            case IMAGETYPE_WEBP:
                $ok = function_exists('imagewebp') ? \imagewebp($dstImg, $destPath, 90) : false; break;
            default:
                $ok = false;
        }

        \imagedestroy($srcImg);
        \imagedestroy($dstImg);
        return (bool) $ok;
    }

    /** Code common function used for image by ns */

    public static function isImageFile($file): bool
    {
        if (! $file) {
            return false;
        }

        return str_starts_with((string) $file->getMimeType(), 'image/');
    }

    public static function meetsMinimumDimensions($file, int $minWidth, int $minHeight): bool
    {
        if (! self::isImageFile($file)) {
            return true;
        }

        $dimensions = @getimagesize($file->getRealPath());

        return $dimensions
            && isset($dimensions[0], $dimensions[1])
            && $dimensions[0] >= $minWidth
            && $dimensions[1] >= $minHeight;
    }

    public static function upload(
        Request $request,
        string $fieldName,
        string $moduleName,
        int $recordId,
        ?array $size = null,
        ?string $oldPath = null,
        bool $shouldCrop = true
    ) {
        // ❌ No new image → return old image
        if (!$request->hasFile($fieldName)) {
            return $oldPath;
        }

        // 🔥 DELETE OLD IMAGE (IMPORTANT FIX)
        if ($oldPath) {
            $oldFile = public_path('storage/' . $moduleName . '/' . $oldPath);
            if (file_exists($oldFile)) {
                unlink($oldFile);
            }
        }

        $image = $request->file($fieldName);
        $extension = $image->getClientOriginalExtension();

        $fileName = $moduleName . '_' . (string) $recordId . '_' . $fieldName . '_' . time() . '.' . $extension;

        $tmpPath  = $image->getRealPath();
        $destDir  = public_path('storage/' . $moduleName);
        $destPath = $destDir . '/' . $fileName;

        if (!file_exists($destDir)) {
            mkdir($destDir, 0755, true);
        }

        // Human readable field name
        $fieldLabel = ucwords(str_replace('_', ' ', $fieldName));

        // Crop + resize only for image uploads. PDFs are stored as-is.
        if ($size && self::isImageFile($image)) {
            $success = self::cropAndResize(
                $tmpPath,
                $destPath,
                $size[0],
                $size[1],
                $shouldCrop
            );

            if (! $success) {
                throw new \Exception(
                    "{$fieldLabel} must be at least {$size[0]} x {$size[1]} pixels."
                );
            }
        } else {
            $image->move($destDir, $fileName);
        }

        return $fileName;
    }



}


