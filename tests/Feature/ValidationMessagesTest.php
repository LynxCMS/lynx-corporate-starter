<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Validator;

/*
 * Forms check answers with Laravel's validator, whose messages come from lang/{locale}/validation.php.
 * Spanish visitors read them in Spanish, with the field's label as the attribute.
 */

beforeEach(function (): void {
    lynx()->languages->add('es', 'Español', locale: 'es-ES', fallback: 'en');
});

/** The first message for a value checked against rules, in a site language. */
function messageIn(string $language, mixed $value, string $rules): string
{
    return lynx()->language($language, fn (): string => (string) Validator::make(['nombre' => $value], ['nombre' => $rules], attributes: ['nombre' => 'Nombre'])->errors()->first('nombre'));
}

it('says a required field is missing in Spanish', function (): void {
    expect(messageIn('es', '', 'required'))->toBe('El campo Nombre es obligatorio.');
});

it('picks the message for the kind of value, in Spanish', function (): void {
    expect(messageIn('es', 'ab', 'string|min:3'))->toBe('El campo Nombre debe tener al menos 3 caracteres.')
        ->and(messageIn('es', 'ana', 'email'))->toBe('El campo Nombre debe ser una dirección de correo electrónico válida.');
});

it('keeps English for English visitors', function (): void {
    expect(messageIn('en', '', 'required'))->toBe('The Nombre field is required.');
});
