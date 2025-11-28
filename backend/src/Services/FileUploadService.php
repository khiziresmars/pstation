<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Application;
use App\Core\Logger;
use App\Core\Validator;

/**
 * File Upload Service
 * Handles image and document uploads
 */
class FileUploadService
{
    private string $uploadPath;
    private string $publicPath;
    private Logger $logger;
    private array $allowedImageTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    private array $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    private int $maxFileSize = 5 * 1024 * 1024; // 5MB
    private int $maxImageWidth = 2048;
    private int $maxImageHeight = 2048;
    private int $thumbnailWidth = 400;
    private int $thumbnailHeight = 300;
    private int $jpegQuality = 85;

    public function __construct()
    {
        $this->uploadPath = dirname(__DIR__, 2) . '/storage/uploads';
        $this->publicPath = dirname(__DIR__, 2) . '/public/images';
        $this->logger = new Logger('uploads');

        $this->ensureDirectories();
    }

    /**
     * Upload single image
     */
    public function uploadImage(array $file, string $folder = 'misc'): array
    {
        // Validate file
        $validation = $this->validateImage($file);
        if (!$validation['valid']) {
            return ['error' => $validation['error']];
        }

        // Generate unique filename
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = $this->generateFilename($extension);
        $relativePath = $folder . '/' . date('Y/m');

        // Create directories
        $fullPath = $this->publicPath . '/' . $relativePath;
        if (!is_dir($fullPath)) {
            mkdir($fullPath, 0755, true);
        }

        // Process and save image
        try {
            $result = $this->processImage(
                $file['tmp_name'],
                $fullPath . '/' . $filename,
                $extension
            );

            if (!$result) {
                return ['error' => 'Failed to process image'];
            }

            // Generate thumbnail
            $thumbnailName = 'thumb_' . $filename;
            $this->createThumbnail(
                $fullPath . '/' . $filename,
                $fullPath . '/' . $thumbnailName
            );

            $this->logger->info('Image uploaded', [
                'filename' => $filename,
                'folder' => $folder,
                'size' => $file['size'],
            ]);

            return [
                'success' => true,
                'filename' => $filename,
                'path' => '/images/' . $relativePath . '/' . $filename,
                'thumbnail' => '/images/' . $relativePath . '/' . $thumbnailName,
                'size' => $file['size'],
            ];
        } catch (\Throwable $e) {
            $this->logger->error('Upload failed', ['error' => $e->getMessage()]);
            return ['error' => 'Upload failed: ' . $e->getMessage()];
        }
    }

    /**
     * Upload multiple images
     */
    public function uploadImages(array $files, string $folder = 'misc'): array
    {
        $results = [];

        // Handle both single file array and multiple files
        if (isset($files['name']) && is_array($files['name'])) {
            $fileCount = count($files['name']);
            for ($i = 0; $i < $fileCount; $i++) {
                $file = [
                    'name' => $files['name'][$i],
                    'type' => $files['type'][$i],
                    'tmp_name' => $files['tmp_name'][$i],
                    'error' => $files['error'][$i],
                    'size' => $files['size'][$i],
                ];
                $results[] = $this->uploadImage($file, $folder);
            }
        } else {
            $results[] = $this->uploadImage($files, $folder);
        }

        return $results;
    }

    /**
     * Delete image
     */
    public function deleteImage(string $path): bool
    {
        // Remove leading slash if present
        $path = ltrim($path, '/');

        // Security: ensure path is within public images
        if (!str_starts_with($path, 'images/')) {
            return false;
        }

        $fullPath = dirname(__DIR__, 2) . '/public/' . $path;

        if (file_exists($fullPath)) {
            unlink($fullPath);

            // Try to delete thumbnail
            $dir = dirname($fullPath);
            $filename = basename($fullPath);
            $thumbnailPath = $dir . '/thumb_' . $filename;
            if (file_exists($thumbnailPath)) {
                unlink($thumbnailPath);
            }

            $this->logger->info('Image deleted', ['path' => $path]);
            return true;
        }

        return false;
    }

    /**
     * Validate uploaded image
     */
    private function validateImage(array $file): array
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['valid' => false, 'error' => $this->getUploadErrorMessage($file['error'])];
        }

        if ($file['size'] > $this->maxFileSize) {
            return ['valid' => false, 'error' => 'File too large. Maximum size is ' . ($this->maxFileSize / 1024 / 1024) . 'MB'];
        }

        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $this->allowedExtensions)) {
            return ['valid' => false, 'error' => 'Invalid file type. Allowed: ' . implode(', ', $this->allowedExtensions)];
        }

        // Verify MIME type
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);
        if (!in_array($mimeType, $this->allowedImageTypes)) {
            return ['valid' => false, 'error' => 'Invalid image type'];
        }

        // Verify it's actually an image
        $imageInfo = @getimagesize($file['tmp_name']);
        if ($imageInfo === false) {
            return ['valid' => false, 'error' => 'Invalid image file'];
        }

        return ['valid' => true];
    }

    /**
     * Process and optimize image
     */
    private function processImage(string $source, string $destination, string $extension): bool
    {
        $imageInfo = getimagesize($source);
        if ($imageInfo === false) {
            return false;
        }

        [$width, $height, $type] = $imageInfo;

        // Create image resource
        $image = match ($type) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($source),
            IMAGETYPE_PNG => imagecreatefrompng($source),
            IMAGETYPE_GIF => imagecreatefromgif($source),
            IMAGETYPE_WEBP => imagecreatefromwebp($source),
            default => null,
        };

        if ($image === null) {
            return false;
        }

        // Resize if too large
        if ($width > $this->maxImageWidth || $height > $this->maxImageHeight) {
            $ratio = min($this->maxImageWidth / $width, $this->maxImageHeight / $height);
            $newWidth = (int) ($width * $ratio);
            $newHeight = (int) ($height * $ratio);

            $resized = imagecreatetruecolor($newWidth, $newHeight);

            // Preserve transparency for PNG
            if ($type === IMAGETYPE_PNG) {
                imagealphablending($resized, false);
                imagesavealpha($resized, true);
            }

            imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            imagedestroy($image);
            $image = $resized;
        }

        // Save image
        $result = match ($extension) {
            'jpg', 'jpeg' => imagejpeg($image, $destination, $this->jpegQuality),
            'png' => imagepng($image, $destination, 8),
            'gif' => imagegif($image, $destination),
            'webp' => imagewebp($image, $destination, $this->jpegQuality),
            default => false,
        };

        imagedestroy($image);

        return $result;
    }

    /**
     * Create thumbnail
     */
    private function createThumbnail(string $source, string $destination): bool
    {
        $imageInfo = getimagesize($source);
        if ($imageInfo === false) {
            return false;
        }

        [$width, $height, $type] = $imageInfo;

        // Create image resource
        $image = match ($type) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($source),
            IMAGETYPE_PNG => imagecreatefrompng($source),
            IMAGETYPE_GIF => imagecreatefromgif($source),
            IMAGETYPE_WEBP => imagecreatefromwebp($source),
            default => null,
        };

        if ($image === null) {
            return false;
        }

        // Calculate crop dimensions (center crop)
        $srcRatio = $width / $height;
        $dstRatio = $this->thumbnailWidth / $this->thumbnailHeight;

        if ($srcRatio > $dstRatio) {
            $srcWidth = (int) ($height * $dstRatio);
            $srcHeight = $height;
            $srcX = (int) (($width - $srcWidth) / 2);
            $srcY = 0;
        } else {
            $srcWidth = $width;
            $srcHeight = (int) ($width / $dstRatio);
            $srcX = 0;
            $srcY = (int) (($height - $srcHeight) / 2);
        }

        $thumbnail = imagecreatetruecolor($this->thumbnailWidth, $this->thumbnailHeight);

        // Preserve transparency
        if ($type === IMAGETYPE_PNG) {
            imagealphablending($thumbnail, false);
            imagesavealpha($thumbnail, true);
        }

        imagecopyresampled(
            $thumbnail, $image,
            0, 0, $srcX, $srcY,
            $this->thumbnailWidth, $this->thumbnailHeight,
            $srcWidth, $srcHeight
        );

        // Always save thumbnails as JPEG for efficiency
        $result = imagejpeg($thumbnail, $destination, 80);

        imagedestroy($image);
        imagedestroy($thumbnail);

        return $result;
    }

    /**
     * Generate unique filename
     */
    private function generateFilename(string $extension): string
    {
        return date('Ymd_His') . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
    }

    /**
     * Get upload error message
     */
    private function getUploadErrorMessage(int $error): string
    {
        return match ($error) {
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'Upload stopped by extension',
            default => 'Unknown upload error',
        };
    }

    /**
     * Ensure upload directories exist
     */
    private function ensureDirectories(): void
    {
        $dirs = [
            $this->uploadPath,
            $this->publicPath,
            $this->publicPath . '/vessels',
            $this->publicPath . '/tours',
            $this->publicPath . '/reviews',
            $this->publicPath . '/misc',
        ];

        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }
    }

    /**
     * Clean up old temporary files
     */
    public function cleanup(): int
    {
        $deleted = 0;
        $files = glob($this->uploadPath . '/*');

        foreach ($files as $file) {
            if (is_file($file) && filemtime($file) < (time() - 86400)) { // 24 hours
                unlink($file);
                $deleted++;
            }
        }

        return $deleted;
    }
}
