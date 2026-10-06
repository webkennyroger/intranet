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
