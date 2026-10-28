<?php

declare(strict_types=1);

namespace Database\Seeders\Support;

use GdImage;
use LynxCms\Core\Pages\Page;
use LynxCms\Core\Support\Value;
use LynxCms\Media\Actions\StoreMedia;
use RuntimeException;

/**
 * Abstract pictures for the demo content, drawn with GD so the seeders need no image files or
 * downloads: a deep background, a grid of fine lines and a few blocks and circles in the site's
 * blues, placed from the page's name so every page gets its own and reseeding gives the same one.
 */
final class Artwork
{
    private const int WIDTH = 1600;

    private const int HEIGHT = 900;

    private const int GRID = 50;

    /** Background, then accents, as RGB. */
    private const array PALETTES = [
        [[0, 29, 108], [15, 98, 254], [69, 137, 255], [166, 200, 255]],
        [[22, 22, 22], [15, 98, 254], [8, 189, 186], [190, 230, 255]],
        [[28, 15, 72], [138, 63, 252], [69, 137, 255], [212, 187, 255]],
        [[0, 45, 44], [0, 157, 154], [61, 219, 217], [158, 240, 240]],
    ];

    /** Stores a picture for the page's image field (or another images field) and adds it there. */
    public static function attach(Page $page, string $field = 'image'): void
    {
        $result = lynx()->run(StoreMedia::class, [
            'page' => $page->id,
            'field' => $field,
            'base64' => base64_encode(self::png($page->name)),
            'filename' => $page->name.'.png',
        ]);
        $entry = Value::array($result->value);

        $page = lynx()->pages->get($page->id) ?? throw new RuntimeException("Page {$page->id} disappeared while storing its picture.");
        $page->{$field} = [['values' => $entry + ['description' => ['en' => (string) $page->title]]]];
        $page->save();
    }

    public static function png(string $seed): string
    {
        mt_srand(crc32($seed));
        $palette = self::PALETTES[mt_rand(0, count(self::PALETTES) - 1)];
        $image = imagecreatetruecolor(self::WIDTH, self::HEIGHT) ?: throw new RuntimeException('GD could not make an image.');
        imagefill($image, 0, 0, self::color($image, $palette[0]));

        self::grid($image, $palette[3]);
        self::shapes($image, $palette);

        ob_start();
        imagepng($image, null, 9);

        return (string) ob_get_clean();
    }

    private static function grid(GdImage $image, array $rgb): void
    {
        $line = imagecolorallocatealpha($image, $rgb[0], $rgb[1], $rgb[2], 112);

        for ($x = 0; $x <= self::WIDTH; $x += self::GRID) {
            imageline($image, $x, 0, $x, self::HEIGHT, $line);
        }

        for ($y = 0; $y <= self::HEIGHT; $y += self::GRID) {
            imageline($image, 0, $y, self::WIDTH, $y, $line);
        }
    }

    /** @param  list<array{int, int, int}>  $palette */
    private static function shapes(GdImage $image, array $palette): void
    {
        for ($i = 0; $i < 7; $i++) {
            $color = self::color($image, $palette[mt_rand(1, 3)], mt_rand(0, 50));
            $x = mt_rand(0, self::WIDTH / self::GRID) * self::GRID;
            $y = mt_rand(0, self::HEIGHT / self::GRID) * self::GRID;
            $size = mt_rand(2, 8) * self::GRID;

            if ($i % 3 === 0) {
                imagefilledellipse($image, $x, $y, $size, $size, $color);
            } else {
                imagefilledrectangle($image, $x, $y, $x + $size, $y + (int) ($size / mt_rand(1, 4)), $color);
            }
        }
    }

    /** @param  array{int, int, int}  $rgb */
    private static function color(GdImage $image, array $rgb, int $alpha = 0): int
    {
        return imagecolorallocatealpha($image, $rgb[0], $rgb[1], $rgb[2], $alpha) ?: 0;
    }
}
