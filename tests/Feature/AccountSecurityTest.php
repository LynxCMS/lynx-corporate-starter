<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Validation\Rules\Password;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

/*
 * The admin's Account security screen offers two-factor authentication and passkeys only to a
 * user model that has Fortify's traits, and every password form follows Password::defaults().
 */

it('lets users turn on two-factor authentication and passkeys', function (): void {
    expect(class_uses(User::class))->toContain(TwoFactorAuthenticatable::class, PasskeyAuthenticatable::class)
        ->and(new User)->toBeInstanceOf(PasskeyUser::class);
});

it('keeps two-factor secrets out of arrays and JSON', function (): void {
    expect((new User)->getHidden())->toContain('two_factor_secret', 'two_factor_recovery_codes');
});

it('asks for passwords of at least 12 characters', function (): void {
    expect(Password::default()->appliedRules()['min'])->toBe(12);
});
