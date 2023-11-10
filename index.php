<?php
require 'vendor/autoload.php';

use Kontakt\GalleryUnplugged\GalleryUnplugged;

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $requestPath = isset($_GET['path']) ? $_GET['path'] : '';

    // Init Gallery
    $galleryUnplugged = new GalleryUnplugged();

    // Generate Thumbnails (cached)
    $galleryUnplugged->generate();

    // Get Images
    //$galleryUnplugged->get($requestPath); // without cache
    $galleryUnplugged->getWithCache($requestPath); // with cache
} else {
    http_response_code(405); // Method not allowed
}
?>