<?php

declare(strict_types=1);

namespace Database\Seeders\Support;

use Illuminate\Support\Str;
use LynxCms\Core\Pages\Page;
use LynxCms\Core\Support\Value;
use LynxCms\Media\Actions\StoreMedia;

/**
 * Download pages with a small PDF per language, for the demo content and the tests: the file of
 * each language is named after the title and tagged with the language's code.
 */
final class Downloads
{
    public const string LIST = '/resources/';

    /** @param  list<string>  $languages  codes of the languages the document has a file in */
    public static function create(string $title, string $kind, array $languages, string $summary = ''): Page
    {
        $page = lynx()->pages->new('download', Pages::get(self::LIST));
        $page->name = Str::slug($title);

        foreach (lynx()->languages->all() as $language) {
            if (! $language->isDefault()) {
                $page->setLanguageName((string) $language->code, Str::slug($title));
            }
        }

        $page->title = $title;
        $page->download_kind = $kind;
        $page->summary = $summary;
        $page->save();

        $page->document = array_map(fn (string $code): array => ['values' => self::store($page, $title, $code)], $languages);
        $page->save();

        return Pages::get(Pages::id($page));
    }

    public static function fileName(string $title, string $language): string
    {
        return Str::slug($title)."-{$language}.pdf";
    }

    /** The PDF of a title in a language: one page that names both. */
    public static function contents(string $title, string $language): string
    {
        $text = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], Str::ascii("{$title} ({$language})"));
        $stream = "BT /F1 24 Tf 72 720 Td ({$text}) Tj ET";
        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>',
            '<< /Length '.strlen($stream)." >>\nstream\n{$stream}\nendstream",
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        ];

        return self::pdf($objects);
    }

    /** @param  list<string>  $objects */
    private static function pdf(array $objects): string
    {
        $pdf = "%PDF-1.4\n";
        $offsets = [];

        foreach ($objects as $index => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($index + 1)." 0 obj\n{$object}\nendobj\n";
        }

        $xref = strlen($pdf);
        $pdf .= 'xref
0 '.(count($objects) + 1)."\n0000000000 65535 f \n";
        $pdf .= implode('', array_map(fn (int $offset): string => sprintf("%010d 00000 n \n", $offset), $offsets));

        return $pdf.'trailer
<< /Size '.(count($objects) + 1)." /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF\n";
    }

    /** @return array<string, mixed> the stored file's entry, tagged with its language */
    private static function store(Page $page, string $title, string $language): array
    {
        $result = lynx()->run(StoreMedia::class, [
            'page' => Pages::id($page),
            'field' => 'document',
            'base64' => base64_encode(self::contents($title, $language)),
            'filename' => self::fileName($title, $language),
        ]);

        return Value::array($result->value) + ['tags' => [$language]];
    }
}
