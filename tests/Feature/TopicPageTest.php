<?php

declare(strict_types=1);

/*
 * A topic's page lists the pages tagged with it, grouped by type: products in their order, then
 * insights and news, newest first. A group with nothing in it is left out.
 */

/** @return array<string, list<string>> group => the items' links, in order */
function topicGroups(string $url): array
{
    $html = (string) test()->get($url)->assertOk()->getContent();
    preg_match_all('/data-topic-group="([^"]+)"(.*?)(?=data-topic-group=|<\/main>)/s', $html, $groups, PREG_SET_ORDER);
    $found = [];

    foreach ($groups as [, $type, $body]) {
        preg_match_all('/<a href="([^"]+)"[^>]*data-topic-item/', $body, $links);
        $found[$type] = $links[1];
    }

    return $found;
}

it('lists the pages tagged with a topic, grouped by type', function (): void {
    $groups = topicGroups('/topics/security');

    expect(array_keys($groups))->toBe(['product', 'resource', 'news'])
        ->and($groups['product'])->toBe([lynx()->pages->get('/products/keyring/')?->url()])
        ->and($groups['resource'])->toHaveCount(3)
        ->and($groups['news'])->toHaveCount(3);
});

it('puts insights and news newest first', function (): void {
    $news = topicGroups('/topics/security')['news'];

    expect($news[0])->toBe(lynx()->pages->get('/news/agent-identity/')?->url())
        ->and(end($news))->toBe(lynx()->pages->get('/news/open-source-fixes/')?->url());
});

it('leaves out a type with no pages on the topic, and pages that are unpublished', function (): void {
    lynx()->pages->setStatus('/products/keyring/', add: ['unpublished']);

    expect(array_keys(topicGroups('/topics/security')))->toBe(['resource', 'news']);
});

it('lists them in the visitor\'s language, leaving out pages offline in it', function (): void {
    lynx()->pages->get('/news/agent-identity/')?->setLanguageName('pt', 'identidade-de-agentes', active: false)->save();

    $groups = topicGroups('/pt/temas/seguranca');

    expect($groups['news'])->toHaveCount(2)
        ->and($groups['product'][0])->toStartWith('/pt/');
});

it('lists the pages as rows, not cards', function (): void {
    expect(dataValues($this->get('/topics/security'), 'data-card'))->toBe([]);
});
