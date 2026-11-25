<?php

declare(strict_types=1);

namespace App\Downloads;

use App\Stats\DownloadLinks;
use Carbon\CarbonInterface;
use LynxCms\Core\Models\Language;
use LynxCms\Core\Pages\Page;
use LynxCms\Media\Values\Pagefile;

/**
 * The PDF of a download page, one file per language: each file is tagged with its language's code.
 * A visitor may download it in any language it has, whatever language they read the site in.
 */
final readonly class Document
{
    /** The files field holding the document. */
    private const string FIELD = 'document';

    public function __construct(private Page $page) {}

    /**
     * Codes of the languages the document has a file in, in the site's order.
     *
     * @return list<string>
     */
    public function languages(): array
    {
        $codes = lynx()->languages->all()->map(fn (Language $language): string => (string) $language->code)->all();

        return array_values(array_filter($codes, fn (string $code): bool => $this->file($code) instanceof Pagefile));
    }

    /** The visitor's language when the document has it, else the site's first one it has. */
    public function defaultLanguage(string $current): ?string
    {
        $languages = $this->languages();

        return in_array($current, $languages, true) ? $current : ($languages[0] ?? null);
    }

    /** A link to the file in a language that counts one download when first opened, until $expires. */
    public function link(string $language, CarbonInterface $expires): ?string
    {
        $file = $this->file($language);

        return $file === null ? null : DownloadLinks::for($file, self::FIELD, $expires, $language);
    }

    public function file(string $language): ?Pagefile
    {
        return $this->page->get(self::FIELD)->tagged($language)[0] ?? null;
    }
}
