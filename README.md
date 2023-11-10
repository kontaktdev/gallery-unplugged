# Gallery Unplugged : PHP API-Driven Gallery

Welcome to the Gallery Unplugged, a lightweight and efficient solution for managing image 
galleries without the need for a database. This gallery is designed to be simple, fast, and easy to use, 
making it an ideal choice for projects where a database is NOT required.

## Table of Contents
- [Introduction](#introduction)
- [Features](#features)
- [Installation](#installation)
- [Usage](#usage)
    - [Generate Thumbnails](#generate-thumbnails)
    - [Get Gallery Images Without Caching](#get-gallery-images-without-caching)
    - [Get Gallery with Cache](#get-gallery-with-cache)
- [Examples](#examples)
- [Contributing](#contributing)

## Introduction

The Gallery Unplugged is a non-database solution for creating and managing image galleries in PHP. It provides a set 
of simple and effective methods to generate thumbnails, retrieve gallery images in API fashion with option to
cache the responses.

## Features

- **No Database Required:** This gallery eliminates the need for a database, simplifying setup and maintenance.
- **Efficient Thumbnail Generation:** Generates the thumbnails on the first load only (or in preload script).
- **Cache Support:** Optionally cache responses for improved performance.

## Installation

To get started, clone the repository and copy the `GalleryUnplugged.php` into your preferred location. Include it
in your script and that's it!

```bash
git clone https://github.com/your-username/php-api-gallery.git
```

## Usage

Create **gallery** folder at root level and copy few pictures over.

### Generate Thumbnails

Use the **generate** method to generate image gallery thumbnails.

```php
// if not using Composer
require_once('GalleryUnplugged.php');
```

```php
$galleryUnplugged = new GalleryUnplugged();
$galleryUnplugged->generate();
```
For the thumbnails, a new **thumbs** folder will be created at root level with the generated thumbnails.

### Get Gallery Images Without Caching

```php
$galleryUnplugged = new GalleryUnplugged();

$requestPath = isset($_GET['path']) ? $_GET['path'] : '';
$galleryUnplugged->get($requestPath);
```

### Get Gallery Images with Cached response

```php
$galleryUnplugged = new GalleryUnplugged();

$requestPath = isset($_GET['path']) ? $_GET['path'] : '';
$galleryUnplugged->getWithCache($requestPath);
```
For the caching, a new **cache** folder will be created at root level with the cached content.

## Examples
Navigate to your script e.g: **localhost:8080**

```json
{
  "status": "success",
  "data": [
    {
      "type": "directory",
      "name": "hiking_day_1",
      "path": "hiking_day_1"
    }
  ],
  "message": "",
  "code": 200
}
```

Navigate to your script e.g: **localhost:8000/?path=hiking_day_1**

```json
{
  "status": "success",
  "data": [
      {
        "type": "image",
        "image": "hiking_day_1/DSC_0810.jpg",
        "square_thumbnail": "thumbs/square_DSC_0810.jpg",
        "proportional_thumbnail": "thumbs/proportional_DSC_0810.jpg"
      },
      {
        "type": "image",
        "image": "hiking_day_1/DSC_0812.jpg",
        "square_thumbnail": "thumbs/square_DSC_0812.jpg",
        "proportional_thumbnail": "thumbs/proportional_DSC_0812.jpg"
      },
  ],
  "message": "",
  "code": 200
}
```

Alternatively, if you are using other Routing method you can pass your route to the **get** method accordingly 

## Contributing
Contributions are welcome! Fork the repository, make your changes, and submit a pull request.

