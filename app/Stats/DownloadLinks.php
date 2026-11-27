<?php

declare(strict_types=1);

namespace App\Stats;

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\URL;
use LynxCms\Media\Values\Pagefile;

/**
 * A counted link to a page's file: a signed address of the site, valid until $expires, that counts
 * one download the first time a visitor opens it, then sends them to the file
 * (Http\DownloadFileController). $subject tells files apart in the counts, such as a language.
 */
final readonly class DownloadLinks
{
    public static function for(Pagefile $file, string $field, CarbonInterface $expires, string $subject = ''): string
    {
        // A file's stored name is "{page id}/{file}".
        [$page, $name] = explode('/', $file->file(), 2) + [1 => ''];

        return URL::temporarySignedRoute('stats.file', $expires, [
            'page' => (int) $page,
            'field' => $field,
            'file' => $name,
            'subject' => PageStats::subject($subject),
        ]);
    }
}
