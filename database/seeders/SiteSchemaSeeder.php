<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Arcturus Systems' content: American English and European Portuguese, and the templates of what
 * the home page features: products, case studies, insights, news, training programs and topics.
 * Hero slides and promos have no URL of their own; they only show inside other pages. The home
 * page's sections come next (SectionsSchemaSeeder).
 */
final class SiteSchemaSeeder extends Seeder
{
    /** Kinds of insight and promo, shown as the eyebrow of their cards. */
    public const array KINDS = [
        'report' => ['en' => 'Report', 'pt' => 'Relatório'],
        'webinar' => ['en' => 'Webinar', 'pt' => 'Webinar'],
        'guidebook' => ['en' => 'Guidebook', 'pt' => 'Guia'],
        'trial' => ['en' => 'Free trial', 'pt' => 'Avaliação gratuita'],
        'program' => ['en' => 'Program', 'pt' => 'Programa'],
        'tool' => ['en' => 'Developer tools', 'pt' => 'Ferramentas para programadores'],
    ];

    public function run(): void
    {
        lynx()->languages->update('default', ['code' => 'en', 'name' => 'English', 'locale' => 'en-US']);
        lynx()->languages->add('pt', 'Português', locale: 'pt-PT', fallback: 'en');
        $this->fields();
        $this->templates();
    }

    private function fields(): void
    {
        // Text visitors read in their language
        lynx()->fields->add('summary', 'textarea', translatable: true, label: 'Summary');
        lynx()->fields->add('body', 'textarea', translatable: true, label: 'Text', settings: ['format' => 'html']);
        lynx()->fields->add('eyebrow', 'text', translatable: true, label: 'Eyebrow');
        lynx()->fields->add('claim', 'text', translatable: true, label: 'Claim (one line)');
        lynx()->fields->add('cta_label', 'text', translatable: true, label: 'Button text');

        // A figure and what it counts: the number is the same in every language
        lynx()->fields->add('stat_value', 'text', label: 'Figure');
        lynx()->fields->add('stat_label', 'text', translatable: true, label: 'What the figure counts');

        lynx()->fields->add('image', 'images', label: 'Image', settings: ['max_files' => 1]);
        lynx()->fields->add('logo', 'images', label: 'Logo', settings: ['max_files' => 1]);
        lynx()->fields->add('client', 'text', label: 'Client');
        lynx()->fields->add('kind', 'choice', label: 'Kind', settings: ['input' => 'select', 'options' => array_map(
            fn (string $value, array $label): array => ['value' => $value, 'label' => $label],
            array_keys(self::KINDS),
            self::KINDS,
        )]);
        lynx()->fields->add('date', 'date', label: 'Date');
        lynx()->fields->add('published_on', 'date', label: 'Published on');
        lynx()->fields->add('external_url', 'url', label: 'Address elsewhere');
        lynx()->fields->add('video_url', 'url', label: 'Video');

        // Links between pages
        lynx()->fields->add('topics', 'page', label: 'Topics', settings: ['templates' => ['topic'], 'input' => 'checkboxes']);
        lynx()->fields->add('products', 'page', label: 'Products', settings: ['templates' => ['product']]);
        lynx()->fields->add('link_page', 'page', label: 'Links to', settings: ['multiple' => false]);
        lynx()->fields->add('link_url', 'url', label: 'Or links to an address');
    }

    private function templates(): void
    {
        $title = ['name' => 'title', 'required' => true];
        $searchable = fn (string $name): array => ['name' => $name, 'searchable' => true];

        $lists = [
            'products' => ['Products', 'product', 'Product', [$title, $searchable('summary'), 'claim', 'stat_value', 'stat_label', 'image', $searchable('body'), 'topics']],
            'case-studies' => ['Case studies', 'case-study', 'Case study', [$title, 'client', 'logo', 'stat_value', 'stat_label', $searchable('summary'), $searchable('body'), 'products']],
            'insights' => ['Insights', 'resource', 'Insight', [$title, ['name' => 'kind', 'required' => true], 'date', 'image', $searchable('summary'), $searchable('body'), 'external_url', 'topics']],
            'newsroom' => ['Newsroom', 'news', 'News', [$title, ['name' => 'published_on', 'required' => true], 'image', $searchable('summary'), $searchable('body'), 'topics']],
            'training' => ['Training', 'training-program', 'Training program', [$title, $searchable('summary'), 'image', $searchable('body'), 'cta_label']],
            'topics' => ['Topics', 'topic', 'Topic', [$title, 'summary']],
        ];

        foreach ($lists as $list => [$listLabel, $item, $itemLabel, $fields]) {
            lynx()->templates->add($item, fields: $fields, label: $itemLabel);
            lynx()->templates->add($list, fields: [$title, 'summary'], label: $listLabel, singleton: true, allowedTemplates: [$item]);
        }

        // Pages without a URL: they appear inside the pages that show them, in every language.
        $card = [$title, 'eyebrow', 'summary', 'image', 'link_page', 'link_url', 'cta_label'];
        lynx()->templates->add('hero-slide', fields: [...$card, 'video_url'], label: 'Hero slide', hasUrl: false);
        lynx()->templates->add('hero-slides', fields: [$title], label: 'Hero slides', hasUrl: false, allowedTemplates: ['hero-slide']);
        lynx()->templates->add('promo', fields: [...$card, 'kind'], label: 'Promo', hasUrl: false);
        lynx()->templates->add('promos', fields: [$title], label: 'Promos', hasUrl: false, singleton: true, allowedTemplates: ['promo']);
    }
}
