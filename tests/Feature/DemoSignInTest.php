<?php

declare(strict_types=1);

use App\Models\User;

/*
 * The public demo's sign-in page offers one button per account, so nobody has to copy a password.
 * Anywhere else those accounts need their passwords, like any other.
 */

it('offers the demo accounts on the sign-in page of the demo', function (): void {
    config(['site.demo_reset' => true]);

    expect(dataValues($this->get(route('login'))->assertOk(), 'data-demo-sign-in'))->toBe(['admin', 'editor']);
});

it('offers no accounts on other sites', function (): void {
    config(['site.demo_reset' => false]);

    expect(dataValues($this->get(route('login'))->assertOk(), 'data-demo-sign-in'))->toBe([]);
});

it('signs in with one click', function (string $account, string $email): void {
    config(['site.demo_reset' => true]);

    $this->post(route('demo.sign-in', $account))->assertRedirect('/lynx/pages');

    $this->assertAuthenticatedAs(User::query()->where('email', $email)->sole());
})->with([
    'the admin' => ['admin', 'owner@arcturus.example'],
    'the editor' => ['editor', 'editor@arcturus.example'],
]);

it('signs nobody in on other sites', function (): void {
    config(['site.demo_reset' => false]);

    $this->post(route('demo.sign-in', 'admin'))->assertNotFound();

    $this->assertGuest();
});

it('knows only the demo accounts', function (): void {
    config(['site.demo_reset' => true]);

    $this->post(route('demo.sign-in', 'somebody'))->assertNotFound();

    $this->assertGuest();
});

it('goes back to the sign-in page when a visitor deleted the account', function (): void {
    config(['site.demo_reset' => true]);
    User::query()->where('email', 'editor@arcturus.example')->sole()->delete();

    $this->post(route('demo.sign-in', 'editor'))->assertRedirect(route('login'));

    $this->assertGuest();
});

it('lets the editor edit pages but not manage people', function (): void {
    $this->actingAs(User::query()->where('email', 'editor@arcturus.example')->sole());

    $this->get(route('lynx.admin.pages'))->assertOk();
    $this->get(route('lynx.admin.users'))->assertForbidden();
});
