<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use LynxCms\Core\Support\Value;

/**
 * Signs a visitor in to one of the public demo's accounts (config site.demo_accounts), without a
 * password. Answers 404 anywhere else: on a real site this would let anyone in.
 */
final class DemoSignInController
{
    public function __invoke(Request $request, string $account): RedirectResponse
    {
        $details = config('site.demo_accounts')[$account] ?? null;

        abort_unless(config('site.demo_reset') === true && is_array($details), 404);

        $user = User::query()->where('email', $details['email'])->first();

        if ($user === null) {
            return to_route('login')->with('status', 'A visitor removed that account. It comes back at the next reset.');
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect(Value::string($details['home']));
    }
}
