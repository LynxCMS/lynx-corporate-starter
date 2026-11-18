<?php

declare(strict_types=1);

namespace App\Flickr;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

/** Reads one album from Flickr's REST API: its details, then its photos. */
final readonly class FlickrAlbum
{
    private const string ENDPOINT = 'https://www.flickr.com/services/rest/';

    private const int TIMEOUT_SECONDS = 5;

    /** Flickr's largest page. Albums with more photos show their first 500. */
    private const int MAX_PHOTOS = 500;

    /**
     * Flickr's image addresses, from its photo source URL format. Size "q" is a 150 pixel square.
     * https://www.flickr.com/services/api/misc.urls.html
     */
    private const string IMAGE_URL = 'https://live.staticflickr.com/%s/%s_%s_q.jpg';

    /**
     * @throws AlbumNotFound when Flickr has no such album
     * @throws ConnectionException|RequestException when Flickr cannot be reached or fails
     */
    public function find(string $albumId): Album
    {
        $info = $this->call('flickr.photosets.getInfo', $albumId, []);
        $photos = $this->call('flickr.photosets.getPhotos', $albumId, ['per_page' => self::MAX_PHOTOS]);

        return new Album(
            title: $this->text($info, 'photoset.title._content'),
            // Flickr allows a few HTML tags in descriptions; the page shows plain text.
            description: trim(strip_tags($this->text($info, 'photoset.description._content'))),
            photoCount: (int) data_get($info, 'photoset.count_photos', 0),
            thumbnails: $this->thumbnails($photos),
        );
    }

    /**
     * @param  array<string, string|int>  $parameters
     * @return array<string, mixed>
     *
     * @throws AlbumNotFound|ConnectionException|RequestException
     */
    private function call(string $method, string $albumId, array $parameters): array
    {
        $response = Http::timeout(self::TIMEOUT_SECONDS)->get(self::ENDPOINT, [
            'method' => $method,
            'api_key' => config('services.flickr.key'),
            'user_id' => config('services.flickr.user_id'),
            'photoset_id' => $albumId,
            'format' => 'json',
            'nojsoncallback' => 1,
            ...$parameters,
        ])->throw();

        if ($response->json('stat') !== 'ok') {
            throw AlbumNotFound::withId($albumId);
        }

        /** @var array<string, mixed> */
        return $response->json();
    }

    /**
     * @param  array<string, mixed>  $photos
     * @return list<string>
     */
    private function thumbnails(array $photos): array
    {
        /** @var list<array<string, mixed>> $list */
        $list = data_get($photos, 'photoset.photo', []);

        return array_map(fn (array $photo): string => sprintf(
            self::IMAGE_URL,
            $this->text($photo, 'server'),
            $this->text($photo, 'id'),
            $this->text($photo, 'secret'),
        ), $list);
    }

    /** @param  array<string, mixed>  $data */
    private function text(array $data, string $key): string
    {
        $value = data_get($data, $key);

        return is_string($value) ? $value : '';
    }
}
