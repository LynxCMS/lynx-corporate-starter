<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use LynxCms\Admin\Branding\SaveBranding;
use LynxCms\Cache\Actions\SavePageCacheRules;
use LynxCms\Core\Pages\Page;
use LynxCms\Seo\Actions\EnableSeo;
use LynxCms\Seo\Actions\MapTemplate;
use LynxCms\Seo\SeoSchema;
use RuntimeException;

/**
 * What Lynx's modules do for the site: the mega-menu and the footer, SEO with structured data for
 * news, the admin's branding and the page cache's rules. Runs after the content the menus list.
 */
final class SiteFeaturesSeeder extends Seeder
{
    /** Templates with SEO fields in the page editor: every page with a URL. */
    private const array SEO_TEMPLATES = ['home', 'products', 'product', 'case-studies', 'case-study', 'insights', 'resource', 'newsroom', 'news', 'training', 'training-program', 'topics', 'topic'];

    /** The mega-menu: a top item per section, and the list pages under it as columns of their children. */
    private const array TOP = [
        [['en' => 'Products', 'pt' => 'Produtos'], ['/products/', '/topics/']],
        [['en' => 'Insights', 'pt' => 'Análises'], ['/insights/', '/news/']],
        [['en' => 'Customers', 'pt' => 'Clientes'], ['/case-studies/']],
        [['en' => 'Learn', 'pt' => 'Aprender'], ['/training/']],
    ];

    /** The footer: a column per label. */
    private const array FOOTER = [
        [['en' => 'Products', 'pt' => 'Produtos'], ['/products/', '/topics/']],
        [['en' => 'Learn', 'pt' => 'Aprender'], ['/insights/', '/training/']],
        [['en' => 'About Arcturus', 'pt' => 'Sobre a Arcturus'], ['/news/', '/case-studies/']],
    ];

    /** How many pages a mega-menu column lists. */
    private const int COLUMN_SIZE = 6;

    public function run(): void
    {
        $this->menu('top', ['en' => 'Main navigation', 'pt' => 'Navegação principal'], self::TOP, children: true);
        $this->menu('footer', ['en' => 'Footer', 'pt' => 'Rodapé'], self::FOOTER, children: false);
        $this->seo();

        lynx()->run(SaveBranding::class, ['name' => 'Arcturus Systems', 'background' => '#f4f4f4', 'accent' => '#0f62fe']);

        // The home page lists the latest news, so it is stored for 15 minutes at most.
        lynx()->run(SavePageCacheRules::class, ['templates' => ['home' => ['cache' => true, 'lifetime' => 900]]]);
    }

    /**
     * A menu of sections: each is a label with page items below it. With $children, each page item
     * also lists its own child pages, which is what makes the columns of the mega-menu.
     *
     * @param  array<string, string>  $title
     * @param  list<array{array<string, string>, list<string>}>  $sections
     */
    private function menu(string $name, array $title, array $sections, bool $children): void
    {
        lynx()->menus()->create($name, $title);

        foreach ($sections as [$label, $paths]) {
            $section = lynx()->menus()->saveItem(['menu' => $name, 'kind' => 'section', 'label' => $label]);

            if (! $section instanceof Page) {
                throw new RuntimeException("Could not add the section to the [{$name}] menu.");
            }

            foreach ($paths as $path) {
                lynx()->menus()->saveItem(['menu' => $name, 'parent' => $section->id, 'kind' => 'page', 'page' => $path]
                    + ($children ? ['children' => true, 'limit' => self::COLUMN_SIZE] : []));
            }
        }
    }

    private function seo(): void
    {
        foreach (self::SEO_TEMPLATES as $template) {
            lynx()->run(EnableSeo::class, ['template' => $template]);
        }

        $settings = lynx()->settings(SeoSchema::SETTINGS_TEMPLATE) ?? throw new RuntimeException('The SEO settings page is missing.');
        $settings->{SeoSchema::SITE_NAME} = 'Arcturus Systems';
        $settings->{SeoSchema::TITLE_PATTERN} = '{title} · {site}';
        $settings->{SeoSchema::DEFAULT_DESCRIPTION} = 'Software and consulting for companies putting AI, data and security to work.';
        $settings->setLanguageValue('pt', SeoSchema::DEFAULT_DESCRIPTION, 'Software e consultoria para empresas que põem a IA, os dados e a segurança a trabalhar.');
        $settings->{SeoSchema::ORG_TYPE} = 'organization';
        $settings->{SeoSchema::ORG_NAME} = 'Arcturus Systems';
        $settings->save();

        lynx()->run(MapTemplate::class, ['template' => 'news', 'type' => 'NewsArticle', 'map' => [
            'headline' => '@title',
            'description' => 'summary',
            'datePublished' => 'published_on',
            'dateModified' => '@modified',
            'author' => '@organization',
            'publisher' => '@organization',
        ]]);
    }
}
