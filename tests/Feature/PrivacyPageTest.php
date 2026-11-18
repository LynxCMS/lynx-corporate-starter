<?php

declare(strict_types=1);

use Database\Seeders\LegalSeeder;
use Database\Seeders\Support\Downloads;

/*
 * The privacy notice: a page of its own in every language, linked from the footer and from the
 * note under each download form.
 */

it('shows the privacy notice in English', function (): void {
    $this->get('/privacy/')->assertOk()
        ->assertSee('Privacy notice')
        ->assertSee('What we collect');
});

it('shows the privacy notice in Portuguese', function (): void {
    $this->get('/pt/privacidade/')->assertOk()
        ->assertSee('Aviso de privacidade')
        ->assertSee('Que dados recolhemos');
});

it('shows the privacy notice in Spanish', function (): void {
    spanish();
    (new LegalSeeder)->run();

    $this->get('/es/privacidad/')->assertOk()
        ->assertSee('Aviso de privacidad')
        ->assertSee('Qué datos recogemos');
});

it('links the notice from the footer', function (): void {
    $this->get('/')->assertOk()->assertSeeInOrder(['data-menu="footer"', 'href="/privacy"'], escape: false);
});

it('links the notice under a download form', function (): void {
    $download = Downloads::create('Cloud outlook', 'report', ['en']);

    $this->get($download->url())->assertOk()
        ->assertSee('data-privacy-notice', escape: false)
        ->assertSee('href="/privacy"', escape: false);
});
