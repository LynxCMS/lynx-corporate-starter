<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Settings for the public demo (config site.demo_reset), where anyone signs in to the admin with
 * one click. Two-factor authentication can't apply there: nobody could set it up for the shared
 * accounts, and the demo holds nothing to protect.
 */
final class DemoSite
{
    public static function configure(): void
    {
        if (config('site.demo_reset') === true) {
            config(['lynx-admin.auth.require_two_factor' => false]);
        }
    }
}
