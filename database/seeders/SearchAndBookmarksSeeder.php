<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use LynxCms\Cache\Actions\SavePageCacheRules;
use LynxCms\Seo\Actions\EnableSeo;
use LynxCms\Seo\SeoSchema;

/**
 * The Search page, which also holds the search panel's suggestions (the pages editors pick and how
 * many to show), and the My bookmarks page, a shell the visitor's browser fills. Runs once: on a
 * site that has them, it changes nothing, so it can be run on its own after the other seeders.
 */
final class SearchAndBookmarksSeeder extends Seeder
{
    public function run(): void
    {
        if (lynx()->templates->find('search') !== null) {
            return;
        }

        lynx()->fields->add('suggested', 'page', label: ['en' => 'Suggested pages', 'pt' => 'Páginas sugeridas'], settings: [
            'templates' => SectionsSchemaSeeder::CARD_TEMPLATES,
            'notes' => ['en' => 'Shown first in the search panel, in this order. The most searched and newest pages fill the rest.', 'pt' => 'Aparecem primeiro no painel de pesquisa, por esta ordem. As páginas mais procuradas e as mais recentes completam a lista.'],
        ]);

        lynx()->templates->add('search', fields: [['name' => 'title', 'required' => true], 'summary', 'suggested', ['name' => 'list_size', 'label' => ['en' => 'Pages in the search panel', 'pt' => 'Páginas no painel de pesquisa']]], label: 'Search', singleton: true);
        lynx()->templates->add('bookmarks', fields: [['name' => 'title', 'required' => true], 'summary'], label: 'Bookmarks', singleton: true);

        $this->page('search', 'search', 'pesquisa', ['Search', 'Pesquisa'], ['Products, insights, case studies and news.', 'Produtos, análises, casos de estudo e notícias.'], ['list_size' => 6]);
        $this->page('bookmarks', 'my-bookmarks', 'os-meus-marcadores', ['My bookmarks', 'Os meus marcadores'], ['Pages you saved on this device. Share the list to open it on another one.', 'Páginas que guardou neste dispositivo. Partilhe a lista para a abrir noutro.']);

        // Results depend on the words searched, so they are never stored.
        lynx()->run(SavePageCacheRules::class, ['templates' => ['search' => ['cache' => false]]]);
    }

    /**
     * @param  array{string, string}  $title  English, Portuguese
     * @param  array{string, string}  $summary
     * @param  array<string, mixed>  $shared
     */
    private function page(string $template, string $name, string $portugueseName, array $title, array $summary, array $shared = []): void
    {
        lynx()->run(EnableSeo::class, ['template' => $template]);

        $page = lynx()->pages->new($template, '/');
        $page->name = $name;
        $page->setLanguageName('pt', $portugueseName);
        $page->title = $title[0];
        $page->setLanguageValue('pt', 'title', $title[1]);
        $page->summary = $summary[0];
        $page->setLanguageValue('pt', 'summary', $summary[1]);
        // A search box and a list kept in the browser: nothing for search engines.
        $page->{SeoSchema::NOINDEX} = true;

        foreach ($shared as $field => $value) {
            $page->{$field} = $value;
        }

        $page->save();
    }
}
