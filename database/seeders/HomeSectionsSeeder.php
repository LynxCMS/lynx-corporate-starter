<?php

declare(strict_types=1);

namespace Database\Seeders;

use Database\Seeders\Support\Pages;
use Illuminate\Database\Seeder;
use LynxCms\Forms\FormSchema;

/**
 * The home page's sections, in the order a company manager would start with. Editors change the
 * order, the pages each section features and which sections show, in the page editor.
 */
final class HomeSectionsSeeder extends Seeder
{
    public function run(): void
    {
        $home = Pages::get('/');
        $home->sections = [
            $this->section('hero', ['slides_from' => $this->id('template=hero-slides'), 'autoplay' => true], ['heading' => ['Featured', 'Em destaque']]),
            $this->section('cards', [
                'cards' => $this->ids(['/_promos/pilot-trial', '/_promos/developer-tools', '/insights/modernizing-core-systems/', '/_promos/partner-program']),
                'layout' => 'grid-4', 'theme' => 'white',
            ], ['heading' => ['Recommended for you', 'Recomendado para si']]),
            $this->section('feature-links', [
                'feature' => $this->id('template=news, name=streamline-ga'),
                'links' => $this->ids(['/products/orchestrate/', '/products/keyring/', '/products/connect/', '/products/ledger/', '/news/agent-identity/', '/news/connect-file-gateway/']),
                'cta_page' => $this->id('template=newsroom'),
            ], [
                'heading' => ['Summit 2026 news', 'Novidades do Summit 2026'],
                'intro' => ['New products and capabilities built to move enterprise technology forward.', 'Novos produtos e capacidades para levar a tecnologia empresarial mais longe.'],
                'cta_label' => ['All the news', 'Todas as notícias'],
            ]),
            $this->section('cards', [
                'cards' => $this->ids(array_map(fn (string $name): string => "/products/{$name}/", ['streamline', 'pilot', 'connect', 'orchestrate', 'ledger', 'keyring'])),
                'layout' => 'grid-3', 'theme' => 'gray', 'cta_page' => $this->id('template=products'),
            ], [
                'heading' => ['The essentials to scale AI', 'O essencial para escalar a IA'],
                'intro' => ['Data, code, integration, agents, spend and secrets: the six things every AI project ends up needing.', 'Dados, código, integração, agentes, custos e segredos: as seis coisas de que todos os projetos de IA acabam por precisar.'],
                'cta_label' => ['Explore all products', 'Ver todos os produtos'],
            ]),
            $this->section('cards', [
                'cards' => $this->ids(['/insights/cost-of-a-breach-2026/', '/insights/recovery-you-can-prove/', '/news/open-source-fixes/', '/insights/open-source-security-ai/', '/news/emea-incident-study/']),
                'layout' => 'carousel', 'theme' => 'dark',
            ], ['heading' => ['Cybersecurity Month spotlight', 'Destaques do Mês da Cibersegurança']]),
            $this->section('cards', [
                'cards' => $this->ids(array_map(fn (string $name): string => "/case-studies/{$name}/", ['velaris-motorsport', 'northwind-footwear', 'halcyon-health', 'arcturus-it'])),
                'layout' => 'grid-4', 'theme' => 'white', 'cta_page' => $this->id('template=case-studies'),
            ], ['heading' => ['Smarter impact, powered by Arcturus', 'Mais impacto, com a Arcturus'], 'cta_label' => ['Every case study', 'Todos os casos']]),
            $this->section('cards', [
                'cards' => $this->ids(['/training/professional/', '/training/foundations/', '/training/students/']),
                'layout' => 'grid-3', 'theme' => 'gray',
            ], ['heading' => ['Training for what comes next', 'Formação para o que aí vem']]),
            $this->section('newsletter', [FormSchema::PICKER => $this->id('template=form, name=newsletter, include=all'), 'list_size' => 7], [
                'heading' => ['Stay connected', 'Mantenha-se a par'],
                'intro' => ['What is new at Arcturus, once a month, in your inbox.', 'As novidades da Arcturus, uma vez por mês, no seu email.'],
            ]),
        ];
        $home->save();
    }

    /**
     * @param  array<string, mixed>  $shared  values every language shares
     * @param  array<string, array{string, string}>  $texts  field => [English, Portuguese]
     * @return array{type: string, values: array<string, mixed>}
     */
    private function section(string $type, array $shared, array $texts): array
    {
        $values = $shared;

        foreach ($texts as $field => [$english, $portuguese]) {
            $values[$field] = ['en' => $english, 'pt' => $portuguese];
        }

        return ['type' => $type, 'values' => $values];
    }

    /**
     * @param  list<string>  $references  paths, or "/_promos/name" for a promo, which has no path
     * @return list<int>
     */
    private function ids(array $references): array
    {
        return array_map(fn (string $reference): int => str_starts_with($reference, '/_promos/')
            ? $this->id('template=promo, name='.substr($reference, strlen('/_promos/')))
            : Pages::id(Pages::get($reference)), $references);
    }

    private function id(string $selector): int
    {
        return Pages::id(Pages::findOne($selector));
    }
}
