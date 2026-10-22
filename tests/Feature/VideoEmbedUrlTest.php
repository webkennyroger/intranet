<?php

use App\Models\Video;

test('a youtube watch url is converted to an embeddable url', function () {
    $video = new Video(['url' => 'https://www.youtube.com/watch?v=abc123XYZ']);

    expect($video->embedUrl())->toBe('https://www.youtube.com/embed/abc123XYZ');
});

test('a youtu.be short url is converted to an embeddable url', function () {
    $video = new Video(['url' => 'https://youtu.be/abc123XYZ']);

    expect($video->embedUrl())->toBe('https://www.youtube.com/embed/abc123XYZ');
});

test('a vimeo url is converted to an embeddable url', function () {
    $video = new Video(['url' => 'https://vimeo.com/123456789']);

    expect($video->embedUrl())->toBe('https://player.vimeo.com/video/123456789');
});

test('an unrecognized url is returned unchanged', function () {
    $video = new Video(['url' => 'https://example.com/my-video']);

    expect($video->embedUrl())->toBe('https://example.com/my-video');
});

test('thumbnailUrl derives a youtube thumbnail when no file was uploaded', function () {
    $video = new Video(['url' => 'https://www.youtube.com/watch?v=abc123XYZ']);

    expect($video->thumbnailUrl())->toBe('https://img.youtube.com/vi/abc123XYZ/hqdefault.jpg');
});

test('thumbnailUrl prefers the uploaded file over the youtube fallback', function () {
    $video = new Video(['url' => 'https://www.youtube.com/watch?v=abc123XYZ', 'thumbnail_path' => 'videos/capa.jpg']);

    expect($video->thumbnailUrl())->toContain('videos/capa.jpg');
});

test('thumbnailUrl returns null when there is no file and the url is not from youtube', function () {
    $video = new Video(['url' => 'https://vimeo.com/123456789']);

    expect($video->thumbnailUrl())->toBeNull();
});

test('platform identifies youtube, vimeo and other urls', function () {
    expect((new Video(['url' => 'https://www.youtube.com/watch?v=abc123']))->platform())->toBe('youtube')
        ->and((new Video(['url' => 'https://youtu.be/abc123']))->platform())->toBe('youtube')
        ->and((new Video(['url' => 'https://vimeo.com/123456789']))->platform())->toBe('vimeo')
        ->and((new Video(['url' => 'https://example.com/my-video']))->platform())->toBe('other');
});
