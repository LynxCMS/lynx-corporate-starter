<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;

/*
 * The hero's slides are pages without a URL under /homepage-hero/: all of them are in the HTML, in
 * tree order, so the page cache keeps the whole carousel.
 */

it('prints every published slide of its branch, in tree order', function (): void {
    expect(dataValues($this->get('/'), 'data-slide'))->toBe(['summit', 'orchestrate-launch', 'breach-report']);
});

it('drops a slide once its unpublish time has passed', function (): void {
    $this->travel(15)->days();
    Artisan::call('lynx:schedule:run');

    expect(dataValues($this->get('/'), 'data-slide'))->toBe(['orchestrate-launch', 'breach-report']);
});

it('drops an unpublished slide', function (): void {
    lynx()->pages->setStatus(lynx()->pages->findOne('template=hero-slide, name=breach-report')?->id ?? 0, add: ['unpublished']);

    expect(dataValues($this->get('/'), 'data-slide'))->toBe(['summit', 'orchestrate-launch']);
});

it('shows the same slides in every language, since slides have no URL to publish per language', function (): void {
    expect(dataValues($this->get('/pt'), 'data-slide'))->toBe(dataValues($this->get('/'), 'data-slide'));
});
