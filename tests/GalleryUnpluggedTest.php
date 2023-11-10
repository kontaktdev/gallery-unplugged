<?php
use PHPUnit\Framework\TestCase;
use Kontakt\GalleryUnplugged\GalleryUnplugged;

class GalleryUnpluggedTest extends TestCase
{
    protected $galleryUnplugged;

    protected function setUp(): void {
        $this->galleryUnplugged = new GalleryUnplugged();

        $this->assertTrue(is_dir(GalleryUnplugged::GALLERY_FOLDER));
        $this->assertNotEmpty(glob(GalleryUnplugged::GALLERY_FOLDER . '/*'));
    }

    public function testGenerate(): void
    {
        // Remove the content of the 'cache' folder
        $this->removeDir(GalleryUnplugged::CACHE_FOLDER);

        // Remove the content of the 'thumb' folder
        $this->removeDir(GalleryUnplugged::THUMBNAIL_FOLDER);

        // Call the generate method
        $this->galleryUnplugged->generate();

        // Assert that the 'thumbs' folder now exists
        $this->assertTrue(is_dir(GalleryUnplugged::THUMBNAIL_FOLDER));
        $this->assertNotEmpty(glob(GalleryUnplugged::THUMBNAIL_FOLDER . '/*'));

        // Assert that the 'thumbs.json' cache file exists
        $this->assertFileExists(GalleryUnplugged::THUMBNAIL_CACHE);
    }

    public function testGet(): void
    {
        // Call the get method
        $response = $this->galleryUnplugged->get('', false);

        // Assert that the output contains the expected JSON content
        $this->assertJson($response);

        // If needed, you can further assert the JSON structure or content
        $decodedResult = json_decode($response, true);

        // Example assertions for the JSON structure
        $this->assertArrayHasKey('status', $decodedResult);
        $this->assertArrayHasKey('data', $decodedResult);
        $this->assertArrayHasKey('message', $decodedResult);
        $this->assertArrayHasKey('code', $decodedResult);

        // Assert that the data array is not empty
        $this->assertNotEmpty($decodedResult['data']);

        // Assert that the array has at least one element
        $this->assertGreaterThan(0, count($decodedResult['data']));
    }

    public function testGetWithCache(): void
    {
        // Remove the content of the 'cache' folder
        $this->removeDir(GalleryUnplugged::CACHE_FOLDER);

        // Call the get method
        $response = $this->galleryUnplugged->getWithCache('', false);

        // Assert that the output contains the expected JSON content
        $this->assertJson($response);

        // If needed, you can further assert the JSON structure or content
        $decodedResult = json_decode($response, true);

        // Example assertions for the JSON structure
        $this->assertArrayHasKey('status', $decodedResult);
        $this->assertArrayHasKey('data', $decodedResult);
        $this->assertArrayHasKey('message', $decodedResult);
        $this->assertArrayHasKey('code', $decodedResult);

        // Assert that the data array is not empty
        $this->assertNotEmpty($decodedResult['data']);

        // Assert that the array has at least one element
        $this->assertGreaterThan(0, count($decodedResult['data']));

        $this->assertTrue(is_dir(GalleryUnplugged::CACHE_FOLDER));
        $this->assertNotEmpty(glob(GalleryUnplugged::CACHE_FOLDER . '/*'));
    }

    private function removeDir(string $folderPath): void
    {
        if (!is_dir($folderPath)) {
            return;
        }

        $files = glob($folderPath . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }
}