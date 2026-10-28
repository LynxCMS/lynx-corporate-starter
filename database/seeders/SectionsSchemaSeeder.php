<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use LynxCms\Core\Pages\Page;
use LynxCms\Forms\Actions\CreateForm;
use LynxCms\Forms\FormSchema;

/**
 * The home page is a list of sections that editors add, sort and switch off. Each section type is a
 * block type of the `sections` matrix, rendered by resources/views/templates/fields/sections/{type}.
 * A new kind of section is one more type here and one more view: nothing else changes.
 */
final class SectionsSchemaSeeder extends Seeder
{
    /** Templates a card section may feature: each renders through <x-card>. */
    public const array CARD_TEMPLATES = ['product', 'case-study', 'resource', 'news', 'training-program', 'promo'];

    public function run(): void
    {
        // The newsletter form, made first: creating a form installs the form picker the newsletter section uses.
        $this->newsletter();
        $this->fields();

        lynx()->fields->add('sections', 'matrix', label: 'Sections', settings: [
            'types' => $this->types(),
            'item_label' => '{heading}',
            'add_label' => ['en' => 'Add a section', 'pt' => 'Adicionar uma secção'],
            'collapse' => 'collapsed',
        ]);
        lynx()->templates->update('home', ['add' => ['sections']]);
    }

    private function fields(): void
    {
        lynx()->fields->add('heading', 'text', translatable: true, label: 'Heading');
        lynx()->fields->add('intro', 'textarea', translatable: true, label: 'Introduction');
        lynx()->fields->add('cta_page', 'page', label: 'Button links to', settings: ['multiple' => false]);
        lynx()->fields->add('cards', 'page', label: 'Cards', settings: ['templates' => self::CARD_TEMPLATES]);
        lynx()->fields->add('feature', 'page', label: 'Featured page', settings: ['multiple' => false, 'templates' => ['news', 'resource', 'product']]);
        lynx()->fields->add('links', 'page', label: 'Links', settings: ['templates' => ['product', 'news', 'resource']]);
        lynx()->fields->add('slides_from', 'page', label: 'Slides', settings: ['multiple' => false, 'templates' => ['hero-slides'], 'input' => 'select']);
        lynx()->fields->add('autoplay', 'toggle', label: 'Turn slides by themselves');
        lynx()->fields->add('topic', 'page', label: 'Only this topic', settings: ['multiple' => false, 'templates' => ['topic'], 'input' => 'select']);
        lynx()->fields->add('list_size', 'integer', label: 'How many');
        lynx()->fields->add('layout', 'choice', label: 'Layout', settings: ['input' => 'radios', 'options' => [
            ['value' => 'grid-4', 'label' => ['en' => 'Four columns', 'pt' => 'Quatro colunas']],
            ['value' => 'grid-3', 'label' => ['en' => 'Three columns', 'pt' => 'Três colunas']],
            ['value' => 'carousel', 'label' => ['en' => 'Carousel', 'pt' => 'Carrossel']],
        ]]);
        lynx()->fields->add('theme', 'choice', label: 'Background', settings: ['input' => 'radios', 'options' => [
            ['value' => 'white', 'label' => ['en' => 'White', 'pt' => 'Branco']],
            ['value' => 'gray', 'label' => ['en' => 'Gray', 'pt' => 'Cinzento']],
            ['value' => 'dark', 'label' => ['en' => 'Dark', 'pt' => 'Escuro']],
        ]]);
        lynx()->fields->add('source', 'choice', label: 'List', settings: ['input' => 'radios', 'options' => [
            ['value' => 'news', 'label' => ['en' => 'Latest news', 'pt' => 'Últimas notícias']],
            ['value' => 'resource', 'label' => ['en' => 'Latest insights', 'pt' => 'Análises mais recentes']],
        ]]);
    }

    /** @return list<array<string, mixed>> */
    private function types(): array
    {
        $heading = ['name' => 'heading', 'required' => true];
        $button = [['name' => 'cta_page', 'width' => 50], ['name' => 'cta_label', 'width' => 50]];
        $look = [['name' => 'layout', 'width' => 50], ['name' => 'theme', 'width' => 50]];

        return [
            ['slug' => 'hero', 'label' => 'Hero carousel', 'fields' => [['name' => 'heading'], ['name' => 'slides_from', 'required' => true], ['name' => 'autoplay']]],
            ['slug' => 'cards', 'label' => 'Cards', 'fields' => [$heading, ['name' => 'intro'], ['name' => 'cards', 'required' => true], ...$look, ...$button]],
            ['slug' => 'feature-links', 'label' => 'Feature and links', 'fields' => [$heading, ['name' => 'intro'], ['name' => 'feature'], ['name' => 'links'], ...$button]],
            ['slug' => 'latest', 'label' => 'Latest pages', 'fields' => [$heading, ['name' => 'source', 'required' => true], ['name' => 'topic', 'width' => 50], ['name' => 'list_size', 'width' => 50], ['name' => 'theme']]],
            ['slug' => 'newsletter', 'label' => 'Newsletter and latest news', 'fields' => [$heading, ['name' => 'intro'], ['name' => FormSchema::PICKER, 'required' => true], ['name' => 'list_size']]],
            ['slug' => 'text', 'label' => 'Text', 'fields' => [$heading, ['name' => 'body'], ['name' => 'theme'], ...$button]],
        ];
    }

    private function newsletter(): void
    {
        $form = lynx()->run(CreateForm::class, ['name' => 'newsletter', 'label' => ['en' => 'Newsletter', 'pt' => 'Newsletter']])->valueAs(Page::class);
        $form->{FormSchema::FIELDS} = [
            ['type' => 'email', 'values' => [
                FormSchema::LABEL => ['en' => 'Work email', 'pt' => 'Email profissional'],
                FormSchema::NAME => 'email',
                FormSchema::REQUIRED => true,
            ]],
        ];
        $form->{FormSchema::SUCCESS} = 'Thank you. The next issue is on its way to you.';
        $form->setLanguageValue('pt', FormSchema::SUCCESS, 'Obrigado. A próxima edição segue para si.');
        $form->save();
        lynx()->pages->setStatus($form->id, remove: ['unpublished']);
    }
}
