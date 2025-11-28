<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Image Optimization Service
 * Handles image resizing, WebP conversion, and optimization
 */
class ImageOptimizer
{
    private string $uploadPath;
    private int $quality = 85;
    private int $webpQuality = 80;

    private array $sizes = [
        'thumb' => ['width' => 150, 'height' => 150],
        'small' => ['width' => 320, 'height' => 240],
        'medium' => ['width' => 640, 'height' => 480],
        'large' => ['width' => 1024, 'height' => 768],
        'xlarge' => ['width' => 1920, 'height' => 1080],
    ];

    public function __construct()
    {
        $this->uploadPath = BASE_PATH . '/public/uploads';
    }

    /**
     * Optimize and create multiple sizes of an image
     */
    public function optimize(string $sourcePath, string $destDir = ''): array
    {
        if (!file_exists($sourcePath)) {
            throw new \RuntimeException("Source image not found: {$sourcePath}");
        }

        $imageInfo = getimagesize($sourcePath);
        if (!$imageInfo) {
            throw new \RuntimeException("Invalid image file");
        }

        $mimeType = $imageInfo['mime'];
        $srcWidth = $imageInfo[0];
        $srcHeight = $imageInfo[1];

        // Create source image resource
        $srcImage = $this->createImageFromFile($sourcePath, $mimeType);
        if (!$srcImage) {
            throw new \RuntimeException("Failed to create image resource");
        }

        // Generate unique filename
        $filename = pathinfo($sourcePath, PATHINFO_FILENAME);
        $extension = pathinfo($sourcePath, PATHINFO_EXTENSION);

        if (!$destDir) {
            $destDir = $this->uploadPath . '/' . date('Y/m');
        }

        if (!is_dir($destDir)) {
            mkdir($destDir, 0755, true);
        }

        $results = [
            'original' => $this->saveOptimized($srcImage, $destDir . '/' . $filename . '.' . $extension, $mimeType),
            'webp' => $this->saveAsWebP($srcImage, $destDir . '/' . $filename . '.webp'),
        ];

        // Create responsive sizes
        foreach ($this->sizes as $sizeName => $dimensions) {
            if ($srcWidth > $dimensions['width'] || $srcHeight > $dimensions['height']) {
                $resized = $this->resize($srcImage, $srcWidth, $srcHeight, $dimensions['width'], $dimensions['height']);

                $sizeFilename = $filename . '-' . $dimensions['width'] . 'w';
                $results[$sizeName] = $this->saveOptimized($resized, $destDir . '/' . $sizeFilename . '.' . $extension, $mimeType);
                $results[$sizeName . '_webp'] = $this->saveAsWebP($resized, $destDir . '/' . $sizeFilename . '.webp');

                imagedestroy($resized);
            }
        }

        imagedestroy($srcImage);

        return $results;
    }

    /**
     * Create image resource from file
     */
    private function createImageFromFile(string $path, string $mimeType): \GdImage|false
    {
        return match ($mimeType) {
            'image/jpeg' => imagecreatefromjpeg($path),
            'image/png' => imagecreatefrompng($path),
            'image/gif' => imagecreatefromgif($path),
            'image/webp' => imagecreatefromwebp($path),
            default => false,
        };
    }

    /**
     * Resize image maintaining aspect ratio
     */
    private function resize(\GdImage $src, int $srcW, int $srcH, int $maxW, int $maxH): \GdImage
    {
        $ratio = min($maxW / $srcW, $maxH / $srcH);
        $newW = (int) ($srcW * $ratio);
        $newH = (int) ($srcH * $ratio);

        $dst = imagecreatetruecolor($newW, $newH);

        // Preserve transparency for PNG
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
        imagefill($dst, 0, 0, $transparent);

        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $srcW, $srcH);

        return $dst;
    }

    /**
     * Save optimized image
     */
    private function saveOptimized(\GdImage $image, string $path, string $mimeType): string
    {
        switch ($mimeType) {
            case 'image/jpeg':
                imagejpeg($image, $path, $this->quality);
                break;
            case 'image/png':
                // PNG compression level (0-9)
                imagepng($image, $path, 6);
                break;
            case 'image/gif':
                imagegif($image, $path);
                break;
            case 'image/webp':
                imagewebp($image, $path, $this->webpQuality);
                break;
        }

        return str_replace(BASE_PATH . '/public', '', $path);
    }

    /**
     * Save image as WebP
     */
    private function saveAsWebP(\GdImage $image, string $path): string
    {
        imagewebp($image, $path, $this->webpQuality);
        return str_replace(BASE_PATH . '/public', '', $path);
    }

    /**
     * Generate srcset for responsive images
     */
    public function generateSrcset(string $basePath, string $extension = 'jpg'): string
    {
        $srcset = [];
        $baseUrl = str_replace('.' . $extension, '', $basePath);

        foreach ($this->sizes as $sizeName => $dimensions) {
            $srcset[] = "{$baseUrl}-{$dimensions['width']}w.{$extension} {$dimensions['width']}w";
        }

        return implode(', ', $srcset);
    }

    /**
     * Get placeholder blur hash or low-quality image
     */
    public function generatePlaceholder(string $imagePath, int $width = 20): string
    {
        if (!file_exists($imagePath)) {
            return '';
        }

        $imageInfo = getimagesize($imagePath);
        if (!$imageInfo) {
            return '';
        }

        $srcImage = $this->createImageFromFile($imagePath, $imageInfo['mime']);
        if (!$srcImage) {
            return '';
        }

        $ratio = $width / $imageInfo[0];
        $height = (int) ($imageInfo[1] * $ratio);

        $thumb = imagecreatetruecolor($width, $height);
        imagecopyresampled($thumb, $srcImage, 0, 0, 0, 0, $width, $height, $imageInfo[0], $imageInfo[1]);

        ob_start();
        imagejpeg($thumb, null, 20);
        $data = ob_get_clean();

        imagedestroy($srcImage);
        imagedestroy($thumb);

        return 'data:image/jpeg;base64,' . base64_encode($data);
    }

    /**
     * Set quality for JPEG compression
     */
    public function setQuality(int $quality): self
    {
        $this->quality = max(1, min(100, $quality));
        return $this;
    }

    /**
     * Set quality for WebP compression
     */
    public function setWebPQuality(int $quality): self
    {
        $this->webpQuality = max(1, min(100, $quality));
        return $this;
    }

    /**
     * Add custom size
     */
    public function addSize(string $name, int $width, int $height): self
    {
        $this->sizes[$name] = ['width' => $width, 'height' => $height];
        return $this;
    }

    /**
     * Clean old optimized images
     */
    public function cleanOldImages(int $daysOld = 30): int
    {
        $deleted = 0;
        $cutoff = time() - ($daysOld * 86400);

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->uploadPath)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getMTime() < $cutoff) {
                // Only delete optimized variants, not originals
                if (preg_match('/-\d+w\.(jpg|png|webp)$/', $file->getFilename())) {
                    unlink($file->getPathname());
                    $deleted++;
                }
            }
        }

        return $deleted;
    }
}
