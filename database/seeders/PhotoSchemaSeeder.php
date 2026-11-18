<?php

// database/seeders/PhotoSchemaSeeder.php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * A photo album page: a Flickr album id that editors type, and what the site copies from the
 * album on every save (App\Flickr\FillFromFlickr). Safe to run again: it adds only what is missing.
 */
final class PhotoSchemaSeeder extends Seeder
{
    /** @var array<string, array{type: string, label: string, translatable: bool}> */
    private const array FIELDS = [
        'flickr_album_id' => ['type' => 'text', 'label' => 'Flickr album id', 'translatable' => false],
        'flickr_description' => ['type' => 'textarea', 'label' => 'Album description', 'translatable' => true],
        'photo_count' => ['type' => 'integer', 'label' => 'Photos', 'translatable' => false],
        'thumbnails' => ['type' => 'textarea', 'label' => 'Thumbnails', 'translatable' => false],
    ];

    public function run(): void
    {
        foreach (self::FIELDS as $name => $field) {
            if (lynx()->fields->find($name) === null) {
                lynx()->fields->add($name, $field['type'], translatable: $field['translatable'], label: $field['label']);
            }
        }

        $fields = ['title', ...array_keys(self::FIELDS)];

        lynx()->templates->find('photo') === null
            ? lynx()->templates->add('photo', fields: $fields, label: 'Photo album')
            : lynx()->templates->update('photo', ['add' => $fields]);
    }
}
