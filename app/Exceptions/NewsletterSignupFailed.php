<?php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Validation\ValidationException;
use RuntimeException;

/** A download request asked to join the newsletter, but the newsletter form wants more than it has. */
final class NewsletterSignupFailed extends RuntimeException
{
    /** @param  array<string, array<int, string>>  $errors */
    public function __construct(public readonly array $errors, ValidationException $previous)
    {
        parent::__construct('A download request could not join the newsletter: '.implode(' ', array_merge(...array_values($errors))), previous: $previous);
    }

    /** @return array<string, mixed> */
    public function context(): array
    {
        return ['errors' => $this->errors];
    }
}
