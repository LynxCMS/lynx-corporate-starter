<?php

declare(strict_types=1);

namespace Database\Seeders;

use Database\Seeders\Support\Artwork;
use Database\Seeders\Support\Downloads;
use Database\Seeders\Support\Pages;
use Illuminate\Database\Seeder;

/**
 * The Resources page and two downloads: a report in every language the site has, and an ebook in
 * English only, which the other languages list too.
 */
final class DownloadsContentSeeder extends Seeder
{
    /** URL names and titles of the list page by language. */
    private const array LIST = [
        'en' => ['resources', 'Resources', 'Reports and ebooks from our research team, free to download.'],
        'pt' => ['recursos', 'Recursos', 'Relatórios e ebooks da nossa equipa de investigação, para descarregar gratuitamente.'],
        'es' => ['recursos', 'Recursos', 'Informes y ebooks de nuestro equipo de investigación, para descargar gratis.'],
    ];

    public function run(): void
    {
        $this->list();

        $languages = array_values(array_intersect(array_keys(self::LIST), lynx()->languages->all()->map(fn ($language): string => (string) $language->code)->all()));
        $report = Downloads::create('The state of cloud security 2026', 'report', $languages, 'What 400 security leaders changed this year, and what they plan next.');
        $guide = Downloads::create('A field guide to zero trust', 'ebook', ['en'], 'Ten steps from a flat network to access checked at every request.');

        // Pictures for the featured two, so their cards lead with a thumbnail; the rest show a cover.
        Artwork::attach($report);
        Artwork::attach($guide);

        $list = Pages::get(Downloads::LIST);
        $list->featured_downloads = [$guide->id, $report->id];
        $list->save();
    }

    private function list(): void
    {
        $page = lynx()->pages->new('downloads', '/');
        $page->name = self::LIST['en'][0];
        $page->title = self::LIST['en'][1];
        $page->summary = self::LIST['en'][2];

        foreach (self::LIST as $code => [$name, $title, $summary]) {
            if ($code !== 'en' && lynx()->languages->find($code) !== null) {
                $page->setLanguageName($code, $name);
                $page->setLanguageValue($code, 'title', $title);
                $page->setLanguageValue($code, 'summary', $summary);
            }
        }

        $page->save();
    }
}
