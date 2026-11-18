<?php

declare(strict_types=1);

namespace Database\Seeders;

use Database\Seeders\Support\Pages;
use Illuminate\Database\Seeder;
use LynxCms\Core\Pages\Page;

/**
 * The privacy notice: a "legal" template for pages of plain text, the notice in each language the
 * site has, and the Resources page pointing its download forms at it. Runs again without
 * duplicating the page, so a language added later gets its text.
 */
final class LegalSeeder extends Seeder
{
    private const string PATH = '/privacy/';

    public function run(): void
    {
        if (lynx()->templates->find('legal') === null) {
            lynx()->templates->add('legal', label: 'Legal page', fields: [
                ['name' => 'title', 'required' => true], 'summary', ['name' => 'body', 'searchable' => true],
            ]);
        }

        $notice = $this->notice();
        $resources = Pages::get('/resources/');
        $resources->privacy_page = $notice;
        $resources->save();
    }

    private function notice(): Page
    {
        /** @var array<string, array{name: string, title: string, summary: string, body: string}> $content */
        $content = require __DIR__.'/content/privacy.php';
        $page = lynx()->pages->get(self::PATH) ?? lynx()->pages->new('legal', '/');

        foreach ($content as $code => $text) {
            $this->fill($page, $code, $text);
        }

        $page->save();

        return $page;
    }

    /** @param array{name: string, title: string, summary: string, body: string} $text */
    private function fill(Page $page, string $code, array $text): void
    {
        if ($code === 'en') {
            $page->name = $text['name'];
            $page->title = $text['title'];
            $page->summary = $text['summary'];
            $page->body = $text['body'];

            return;
        }

        if (lynx()->languages->find($code) === null) {
            return;
        }

        $page->setLanguageName($code, $text['name']);

        foreach (['title', 'summary', 'body'] as $field) {
            $page->setLanguageValue($code, $field, $text[$field]);
        }
    }
}
