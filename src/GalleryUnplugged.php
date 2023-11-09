<?php

namespace Kontakt\GalleryUnplugged;

use Exception;

/**
 * Class GalleryUnplugged
 * @package Kontakt\GalleryUnplugged
 */
class GalleryUnplugged
{
    // Gallery settigns
    const GALLERY_FOLDER = "gallery";

    // Thumbnails settings
    const THUMBNAIL_FOLDER = 'thumbs';
    const THUMBNAIL_PATH_SQUARE = self::THUMBNAIL_FOLDER . '/square_';
    const THUMBNAIL_PATH_PROPORTIONAL = self::THUMBNAIL_FOLDER . '/proportional_';
    const THUMBNAIL_CACHE = self::THUMBNAIL_FOLDER . '/thumbs.json';
    const THUMBNAIL_MAX_SQUARE_SIZE = 300;
    const THUMBNAIL_MAX_PROPORTIONAL_SIZE = 300;

    // Response cache
    const CACHE_FOLDER = "cache";

    private string $imagePath;
    private string $thumbnailCacheFile;
    private array $cache;

    public function __construct()
    {
        $this->imagePath = self::GALLERY_FOLDER;
        $this->thumbnailCacheFile = self::THUMBNAIL_CACHE;
        $this->init();
        $this->loadThumbnailCache();
    }

    /**
     * All initialisation logic
     *
     * @throws Exception
     */
    private function init(): void
    {
        // Create Gallery Folder if it doesn't exist
        if (!is_dir(self::GALLERY_FOLDER)) {
            if (!mkdir(self::GALLERY_FOLDER, 0755, true)) {
                throw new Exception('Failed to create the ' . self::GALLERY_FOLDER . ' directory.');
            }
        }
    }

    /**
     *  Generates Thumbnails
     */
    public function generate(): void
    {
        // Create Thumbnail Folder if it doesn't exist
        if (!is_dir(self::THUMBNAIL_FOLDER)) {
            if (!mkdir(self::THUMBNAIL_FOLDER, 0755, true)) {
                throw new Exception('Failed to create the ' . self::THUMBNAIL_FOLDER . ' directory.');
            }
        }

        $this->scanAndCreateThumbnails($this->imagePath);
        $this->saveThumbnailCache();
    }

    /**
     * Get Albums and Images
     *
     * @param string $folderPath
     * @return string
     */
    public function get(string $folderPath = ''): void
    {
        $data = $this->getData($folderPath);
        Api::response(
            Api::API_STATUS_SUCCESS,
            $data,
        );
    }

    /**
     * Get Albums and Images from Cache
     *
     * @param string $folderPath
     * @throws Exception
     */
    public function getWithCache(string $folderPath = ''): void
    {
        // Create Cache folder if it doesn't exist
        if (!is_dir(self::CACHE_FOLDER)) {
            if (!mkdir(self::CACHE_FOLDER, 0755, true)) {
                throw new \Exception('Failed to create the ' . self::CACHE_FOLDER . ' directory.');
            }
        }

        $cacheFilePath = $this->getCacheFilePath($folderPath);

        if (file_exists($cacheFilePath)) {
            // if Cache file exists, load the cached data
            $data = json_decode(file_get_contents($cacheFilePath), true);
        } else {
            // if Cache file doesn't exist, fetch the data and store it to the cache
            $data = $this->getData($folderPath);
            // Save the response to the cache file
            file_put_contents($cacheFilePath, json_encode($data));
        }

        Api::response(
            Api::API_STATUS_SUCCESS,
            $data,
        );
    }

    /**
     * Fetch Data
     *
     * @param string $folderPath
     * @return array
     */
    private function getData(string $folderPath = ''): array
    {
        $folderPath = (empty($folderPath))
            ? $this->imagePath
            : $this->imagePath . '/' . $folderPath;

        $data = is_dir($folderPath)
            ? $this->scanWithResponse($folderPath)
            : [];

        return $data;
    }

    /**
     * Load Thumbnail cached data
     */
    private function loadThumbnailCache(): void
    {
        if (file_exists($this->thumbnailCacheFile)) {
            $this->cache = json_decode(file_get_contents($this->thumbnailCacheFile), true);
        } else {
            $this->cache = [];
        }
    }

    /**
     * Save Thumbnail cache data
     */
    private function saveThumbnailCache(): void
    {
        file_put_contents($this->thumbnailCacheFile, json_encode($this->cache));
    }

    /**
     * Generate Thumbnails
     *
     * @param string $imagePath
     */
    private function generateThumbnails(string $imagePath): void
    {
        // Save the thumbnails to the "thumbs" directory
        $thumbnailPathSquare = self::THUMBNAIL_PATH_SQUARE . basename($imagePath);
        $thumbnailPathProportional = self::THUMBNAIL_PATH_PROPORTIONAL . basename($imagePath);

        $imageProcessor = new ImageProcessor($imagePath);
        $imageProcessor->createSquareThumbnail(self::THUMBNAIL_MAX_SQUARE_SIZE, $thumbnailPathSquare);
        $imageProcessor->createPropotionalThumbnail(self::THUMBNAIL_MAX_PROPORTIONAL_SIZE, $thumbnailPathProportional);
    }

    /**
     * @param $folderPath
     * @return array
     */
    private function scanAndCreateThumbnails(string $folderPath): void
    {
        if ($handle = opendir($folderPath)) {
            while (false !== ($entry = readdir($handle))) {
                if ($entry != "." && $entry != "..") {
                    $resourcePath = $folderPath . '/' . $entry;
                    $relativePath = ltrim(str_replace($this->imagePath, '', $resourcePath), '/');
                    $cacheKey = md5($relativePath);

                    if (is_dir($resourcePath)) {
                        $this->scanAndCreateThumbnails($resourcePath);
                    } elseif (in_array(pathinfo($entry, PATHINFO_EXTENSION), ['jpg', 'jpeg', 'JPG'])) {
                        // Check if the image was modified since the last cache
                        if (!isset($this->cache[$cacheKey]) || filemtime($resourcePath) > $this->cache[$cacheKey]) {
                            // Generate thumbnails for images as needed
                            $this->generateThumbnails($resourcePath);
                            $this->cache[$cacheKey] = filemtime($resourcePath);
                        }
                    }
                }
            }
            closedir($handle);
        }
    }

    private function scanWithResponse(string $folderPath): array
    {
        $resources = [];

        if ($handle = opendir($folderPath)) {
            while (false !== ($entry = readdir($handle))) {
                if ($entry != "." && $entry != "..") {
                    $resourcePath = $folderPath . '/' . $entry;
                    $relativePath = ltrim(str_replace($this->imagePath, '', $resourcePath), '/');
                    $cacheKey = md5($relativePath);

                    if (is_dir($resourcePath)) {
                        // Recursively scan subdirectories
                        $resources[] =
                            Directory::build($entry, $relativePath);
                    } elseif (in_array(pathinfo($entry, PATHINFO_EXTENSION), ['jpg', 'jpeg', 'JPG'])) {
                        $thumbnailPathSquare = null;
                        $thumbnailPathProportional = null;
                        if (isset($this->cache[$cacheKey])) {
                            $thumbnailPathSquare = self::THUMBNAIL_PATH_SQUARE . basename($resourcePath);
                            $thumbnailPathProportional = self::THUMBNAIL_PATH_PROPORTIONAL . basename($resourcePath);
                        }

                        $resources[] =
                            Image::build($relativePath, $thumbnailPathSquare, $thumbnailPathProportional);
                    }
                }
            }
            closedir($handle);
        }

        return $resources;
    }

    /**
     * Get the cache file path for a given folder path
     *
     * @param string $folderPath
     * @return string
     */
    private function getCacheFilePath(string $folderPath): string
    {
        $cacheFolder = self::CACHE_FOLDER;
        $cacheFileName = md5($folderPath) . '.json';

        return $cacheFolder . '/' . $cacheFileName;
    }
}

/**
 * Class Resource
 * @package Kontakt\GalleryUnplugged
 */
abstract class Resource
{
    public string $type;

    const TYPE_DIRECTORY = 'directory';
    const TYPE_IMAGE = 'image';
}

/**
 * Class Directory
 * @package Kontakt\GalleryUnplugged
 */
class Directory extends Resource
{
    public string $name;
    public string $path;

    public function __construct(string $name, string $path)
    {
        $this->type = self::TYPE_DIRECTORY;
        $this->name = $name;
        $this->path = $path;
    }

    public static function build(string $name, string $path): self
    {
        return new Directory($name, $path);
    }
}

/**
 * Class Image
 * @package Kontakt\GalleryUnplugged
 */
class Image extends Resource
{
    public string $image;
    public string $square_thumbnail;
    public string $proportional_thumbnail;

    /**
     * Image constructor.
     * @param string $image
     * @param string $square_thumbnail
     * @param string $proportional_thumbnail
     */
    public function __construct(
        string $image,
        ?string $square_thumbnail = null,
        ?string $proportional_thumbnail = null
    )
    {
        $this->type = self::TYPE_IMAGE;
        $this->image = $image;
        $this->square_thumbnail = $square_thumbnail ?? '';
        $this->proportional_thumbnail = $proportional_thumbnail ?? '';
    }

    public static function build(
        string $image,
        ?string $square_thumbnail = null,
        ?string $proportional_thumbnail = null
    ): self
    {
        return new Image(
            $image,
            $square_thumbnail,
            $proportional_thumbnail
        );
    }
}

/**
 * Class Api
 * @package Kontakt\GalleryUnplugged
 */
class Api
{
    public string $status;
    public array $data;
    public string $message;
    public int $code;

    const API_STATUS_SUCCESS = 'success';
    const API_STATUS_ERROR = 'error';

    public function __construct(string $status, array $data = [], string $message = '', int $code = 200)
    {
        $this->status = $status;
        $this->data = $data;
        $this->message = $message;
        $this->code = $code;
    }

    /**
     * Return Generic API response
     *
     * @param string $status
     * @param array $data
     * @param string $message
     * @param int $code
     */
    public static function response(string $status, array $data = [], string $message = '', int $code = 200): void
    {
        $response = new Api($status, $data, $message, $code);

        header('Content-Type: application/json');
        echo json_encode($response);
    }
}

/**
 * Class ImageProcessor
 * @package Kontakt\GalleryUnplugged
 */
class ImageProcessor
{
    protected string $sourceImagePath;
    protected int $width;
    protected int $height;
    protected string $imagetype;
    protected int $orientation;

    /**
     * ImageProcessor constructor.
     * @param string $sourceImagePath
     */
    public function __construct(string $sourceImagePath)
    {
        $this->sourceImagePath = $sourceImagePath;

        $this->getImageSize();

        // Get the orientation based on Exif data (if available)
        $exif = exif_read_data($this->sourceImagePath);
        $this->orientation = isset($exif['Orientation']) ? $exif['Orientation'] : 1;
    }

    protected function getImageSize(): void
    {
        $imageInfo = getimagesize($this->sourceImagePath);
        if ($imageInfo === false) {
            error_log("Cannot execute the getimagesize for: " . $this->sourceImagePath . " skipping...");
            return;
        }

        $this->width = $imageInfo[0];
        $this->height = $imageInfo[1];
        $this->imagetype = $imageInfo[2];
    }

    protected function hasImageSizeInfo(): bool
    {
        return ($this->width && $this->height && $this->imagetype);
    }

    /**
     * Create square Thumbnail from source Image
     *
     * @param int $maxSideLength
     * @param string $outputPath
     */
    function createSquareThumbnail(int $maxSideLength, string $outputPath): void
    {
        if (!$this->hasImageSizeInfo()) {
            return;
        }

        if ($this->width > $this->height) {
            $smallestSide = $this->height;
        } elseif ($this->height > $this->width) {
            $smallestSide = $this->width;
        }

        // Generate square thumbnail
        $this->createThumbnail($smallestSide, $smallestSide, $maxSideLength, $maxSideLength, $outputPath);
    }

    /**
     * Create proportional Thumbnail from source Image
     *
     * @param int $maxSideLength
     * @param string $outputPath
     */
    public function createPropotionalThumbnail(int $maxSideLength, string $outputPath): void
    {
        if (!$this->hasImageSizeInfo()) {
            return;
        }

        $srcWidth = $this->width;
        $srcHeight = $this->height;

        // Determine the target width and height while maintaining aspect ratio
        if ($this->orientation >= 5 && $this->orientation <= 8) {
            list($srcWidth, $srcHeight) = array($srcHeight, $srcWidth); // Swap dimensions for certain orientations
        }

        if ($srcWidth > $srcHeight) {
            $targetWidth = $maxSideLength;
            $targetHeight = intval($srcHeight * ($maxSideLength / $srcWidth));
        } else {
            $targetHeight = $maxSideLength;
            $targetWidth = intval($srcWidth * ($maxSideLength / $srcHeight));
        }

        // Generate square thumbnail
        $this->createThumbnail($srcWidth, $srcHeight, $targetWidth, $targetHeight, $outputPath);
    }

    /**
     * @param int $srcWidth
     * @param int $srcHeight
     * @param int $targetWidth
     * @param int $targetHeight
     * @param string $outputPath
     */
    protected function createThumbnail(
        int $srcWidth,
        int $srcHeight,
        int $targetWidth,
        int $targetHeight,
        string $outputPath
    ): void
    {
        // Create a blank canvas for the thumbnail
        $thumbnail = imagecreatetruecolor($targetWidth, $targetHeight);

        // Load the source image based on file type
        switch ($this->imagetype) {
            case IMAGETYPE_JPEG:
                $sourceImage = imagecreatefromjpeg($this->sourceImagePath);
                break;
            case IMAGETYPE_PNG:
                $sourceImage = imagecreatefrompng($this->sourceImagePath);
                break;
            case IMAGETYPE_GIF:
                $sourceImage = imagecreatefromgif($this->sourceImagePath);
                break;
            default:
                return; // Unsupported image type
        }

        // Apply orientation correction if needed
        if ($this->orientation >= 2 && $this->orientation <= 8) {
            $angle = [0, 0, 0, 180, 0, 0, -90, 0, 90][$this->orientation];
            $sourceImage = imagerotate($sourceImage, $angle, 0);
        }

        // Create the cropped thumbnail
        imagecopyresampled(
            $thumbnail,
            $sourceImage,
            0,
            0,
            0,
            0,
            $targetWidth,
            $targetHeight,
            $srcWidth,
            $srcHeight
        );

        // Save the thumbnail to a file
        imagejpeg($thumbnail, $outputPath, 80); // Change quality as needed

        // Free up memory
        imagedestroy($sourceImage);
        imagedestroy($thumbnail);
    }
}
