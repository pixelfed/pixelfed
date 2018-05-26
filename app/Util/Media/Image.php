<?php

namespace App\Util\Media;

use Storage;
use App\Media;
use Image as Intervention;

class Image {

  public $square;
  public $landscape;
  public $portrait;
  public $thumbnail;
  public $orientation;


  public function __construct()
  {
    ini_set('memory_limit', config('pixelfed.memory_limit', '1024M'));
    
    $this->square = $this->orientations()['square'];
    $this->landscape = $this->orientations()['landscape'];
    $this->portrait = $this->orientations()['portrait'];
    $this->thumbnail = [
      'width' => 293,
      'height' => 293
    ];
    $this->orientation = null;
  }

  public function orientations()
  {
    return [
      'square' => [
        'width' => 1080,
        'height' => 1080
      ],
      'landscape' => [
        'width' => 1920,
        'height' => 1080
      ],
      'portrait' => [
        'width' => 1080,
        'height' => 1350
      ]
    ];
  }

  public function getAspectRatio($mediaPath, $thumbnail = false)
  {
    if(!is_file($mediaPath)) {
      throw new \Exception('Invalid Media Path');
    }
    if($thumbnail) {
      return [
        'dimensions' => $this->thumbnail,
        'orientation' => 'thumbnail'
      ];
    }

    list($width, $height) = getimagesize($mediaPath);
    $aspect = $width / $height;
    $orientation = $aspect === 1 ? 'square' : 
      ($aspect > 1 ? 'landscape' : 'portrait');
    $this->orientation = $orientation;
    return [
      'dimensions' => $this->orientations()[$orientation],
      'orientation' => $orientation
    ];
  }

  public function resizeImage(Media $media)
  {
    $basePath = storage_path('app/' . $media->media_path);

    $this->handleResizeImage($media);
    return;
  }

  public function resizeThumbnail(Media $media)
  {
    $basePath = storage_path('app/' . $media->media_path);

    $this->handleThumbnailImage($media);
    return; 
  }

  public function handleResizeImage(Media $media)
  {
    $this->handleImageTransform($media, false);
  }

  public function handleThumbnailImage(Media $media)
  {
    $this->handleImageTransform($media, true);
  }

  public function handleImageTransform(Media $media, $thumbnail = false)
  {
    $path = $media->media_path;
    $file = storage_path('app/'.$path);
    $ratio = $this->getAspectRatio($file, $thumbnail);
    $aspect = $ratio['dimensions'];
    $orientation = $ratio['orientation'];

    try {
      $img = Intervention::make($file);
      $img->resize($aspect['width'], $aspect['height'], function ($constraint) {
        $constraint->aspectRatio();
      });
      $converted = $this->convertPngToJpeg($path, $thumbnail, $img->extension);
      $newPath = storage_path('app/'.$converted['path']);
      $is_png = false;

      if($img->extension == 'png' || $converted['png'] == true) {
        \Log::info('PNG detected, ' . json_encode([$img, $converted]));
        $is_png = true;
        $newPath = str_replace('.png', '.jpeg', $newPath);
        $img->encode('jpg', 80)->save($newPath);
        if(!$thumbnail) {
          @unlink($file);
        }
        \Log::info('PNG SAVED, ' . json_encode([$img, $newPath]));
      } else {
        \Log::info('PNG not detected, ' . json_encode([$img, $converted]));
        $img->save($newPath, 75);
      }
      
      if(!$thumbnail) {
        $media->orientation = $orientation;
      }

      if($is_png == true) {
        if($thumbnail == false) {
          $media->media_path = $converted['path'];
          $media->mime = $img->mime;
        }
      }

      if($thumbnail == true) {
        $media->thumbnail_path = $converted['path'];
        $media->thumbnail_url = url(Storage::url($converted['path']));
      }

      $media->save();

    } catch (Exception $e) {
      
    }
  }

  public function convertPngToJpeg($basePath, $thumbnail = false, $extension)
  {
    $png = false;
    $path = explode('.', $basePath);
    $name = ($thumbnail == true) ? $path[0] . '_thumb' : $path[0];
    $ext = last($path);
    $basePath = "{$name}.{$ext}";
    if($extension == 'png' || $ext == 'png') {
      $ext = 'jpeg';
      $basePath = "{$name}.{$ext}";
      $png = true;
    }
    return ['path' => $basePath, 'png' => $png];
  }

}