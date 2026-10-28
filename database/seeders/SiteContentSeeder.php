<?php

declare(strict_types=1);

namespace Database\Seeders;

use Database\Seeders\Support\Artwork;
use Illuminate\Database\Seeder;
use LynxCms\Core\Pages\Page;
use RuntimeException;

/**
 * The pages the home page features, in English and Portuguese: topics, products, case studies,
 * insights, news and training under their list pages, and the hero slides and promos, which have no
 * URL. Each gets a picture of its own (Artwork). The home page's sections come next.
 */
final class SiteContentSeeder extends Seeder
{
    private const string PORTUGUESE = 'pt';

    /** List pages: template => [name, Portuguese name, English title, Portuguese title]. */
    private const array LISTS = [
        'topics' => ['topics', 'temas', 'Topics', 'Temas'],
        'products' => ['products', 'produtos', 'Products', 'Produtos'],
        'case-studies' => ['case-studies', 'casos', 'Case studies', 'Casos de estudo'],
        'insights' => ['insights', 'analises', 'Insights', 'Análises'],
        'newsroom' => ['news', 'noticias', 'Newsroom', 'Notícias'],
        'training' => ['training', 'formacao', 'Training', 'Formação'],
    ];

    public function run(): void
    {
        $home = $this->get('/');
        $home->title = 'Arcturus Systems';
        $home->save();

        foreach (self::LISTS as $template => [$name, $ptName, $title, $ptTitle]) {
            $this->page($template, '/', ['name' => $name, 'pt_name' => $ptName, 'en' => ['title' => $title], 'pt' => ['title' => $ptTitle]]);
        }

        $catalogue = $this->content('products');
        $stories = $this->content('stories');
        $news = $this->content('news');

        $this->items('topic', '/topics/', $catalogue['topics'], picture: false);
        $this->items('product', '/products/', $catalogue['products']);
        $this->items('case-study', '/case-studies/', $stories['case-studies'], picture: 'logo');
        $this->items('resource', '/insights/', $stories['insights']);
        $this->items('training-program', '/training/', $stories['training']);
        $this->items('news', '/news/', array_map(fn (array $item): array => $item + ['shared' => ['published_on' => now()->subDays($item['days_ago'])->toDateString()]], $news['news']));

        $this->slides($news['slides']);
        $this->promos($news['promos']);
    }

    /** @param  list<array<string, mixed>>  $items */
    private function items(string $template, string $parent, array $items, string|false $picture = 'image'): void
    {
        foreach ($items as $content) {
            $page = $this->page($template, $parent, $content);

            if ($picture !== false) {
                Artwork::attach($page, $picture);
            }
        }
    }

    /** @param  list<array<string, mixed>>  $slides */
    private function slides(array $slides): void
    {
        $branch = $this->page('hero-slides', '/', ['name' => 'homepage-hero', 'en' => ['title' => 'Home page hero']]);

        foreach ($slides as $content) {
            $slide = $this->page('hero-slide', $branch, $content + ['shared' => ['link_page' => $this->get($content['link'])->id]]);
            Artwork::attach($slide);

            if (isset($content['unpublish_in_days'])) {
                lynx()->pages->schedule($slide->id, '*', unpublishAt: now()->addDays($content['unpublish_in_days']));
            }
        }
    }

    /** @param  list<array<string, mixed>>  $promos */
    private function promos(array $promos): void
    {
        $branch = $this->page('promos', '/', ['name' => '_promos', 'en' => ['title' => 'Promos']]);

        foreach ($promos as $content) {
            $promo = $this->page('promo', $branch, $content + ['shared' => ['kind' => $content['kind'], 'link_page' => $this->get($content['link'])->id]]);
            Artwork::attach($promo);
        }
    }

    /**
     * A new page, written in both languages and saved. Pages with a URL get a Portuguese URL name,
     * which publishes them in Portuguese; topics and products they name are looked up by name.
     *
     * @param  array<string, mixed>  $content  name, pt_name, shared, topics, products, and en and pt field values
     */
    private function page(string $template, Page|string $parent, array $content): Page
    {
        $page = lynx()->pages->new($template, $parent);
        $page->name = $content['name'];

        if (isset($content['pt_name'])) {
            $page->setLanguageName(self::PORTUGUESE, $content['pt_name']);
        }

        foreach ([...$content['shared'] ?? [], ...$this->references($content)] as $field => $value) {
            $page->{$field} = $value;
        }

        foreach ($content['en'] as $field => $value) {
            $page->{$field} = $value;
        }

        foreach ($content['pt'] ?? [] as $field => $value) {
            $page->setLanguageValue(self::PORTUGUESE, $field, $value);
        }

        $page->save();

        return $page;
    }

    /**
     * @param  array<string, mixed>  $content
     * @return array<string, list<int>>
     */
    private function references(array $content): array
    {
        $references = [];

        foreach (['topics' => '/topics/', 'products' => '/products/'] as $field => $parent) {
            if (isset($content[$field])) {
                $references[$field] = array_map(fn (string $name): int => $this->get($parent.$name.'/')->id, $content[$field]);
            }
        }

        return $references;
    }

    private function get(string $path): Page
    {
        return lynx()->pages->get($path) ?? throw new RuntimeException("SiteContentSeeder needs [{$path}].");
    }

    /** @return array<string, list<array<string, mixed>>> */
    private function content(string $file): array
    {
        return require __DIR__."/content/{$file}.php";
    }
}
