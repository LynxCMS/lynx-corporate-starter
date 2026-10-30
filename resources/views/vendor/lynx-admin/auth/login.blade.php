{{-- Lynx admin's sign-in page (auth/login), with one button per account above the form on the public
     demo (DemoSignInController). Compare with the vendor view when updating Lynx. --}}
<x-lynx-admin::auth-layout title="Sign in" description="Sign in to manage this site's content.">
    @if (config('site.demo_reset') === true)
        <div class="grid gap-2">
            <p class="text-muted-foreground text-center text-sm">Try the demo, no password needed</p>
            <div class="flex gap-2">
                @foreach (config('site.demo_accounts') as $key => $account)
                    <form method="POST" action="{{ route('demo.sign-in', $key) }}" class="flex-1" data-demo-sign-in="{{ $key }}">
                        @csrf
                        <x-lynx-admin::ui.button type="submit" class="w-full">{{ $account['label'] }}</x-lynx-admin::ui.button>
                    </form>
                @endforeach
            </div>
        </div>
        <x-lynx-admin::ui.separator />
    @endif
    @if (Route::has('passkey.login'))
        <div x-data="lynxPasskeyVerify({{ Js::from(['options' => route('passkey.login-options'), 'submit' => route('passkey.login')]) }})" x-show="supported" x-cloak class="grid gap-2">
            <x-lynx-admin::ui.button type="button" variant="outline" class="w-full" x-on:click="verify()" x-bind:disabled="loading"><x-lucide-key-round aria-hidden="true" /> Sign in with a passkey</x-lynx-admin::ui.button>
            <p x-show="error" x-text="error" role="alert" class="text-destructive text-center text-sm"></p>
            <p class="text-muted-foreground text-center text-xs uppercase tracking-wider">or with your email</p>
        </div>
    @endif
    <form method="POST" action="{{ route('login.store') }}" class="grid gap-5">
        @csrf
        <div class="grid gap-2">
            <x-lynx-admin::ui.label for="email">Email</x-lynx-admin::ui.label>
            <x-lynx-admin::ui.input id="email" name="email" type="email" :value="old('email')" required autofocus autocomplete="username" :aria-invalid="$errors->has('email') ? 'true' : null" />
            @error('email') <p role="alert" class="text-destructive text-sm">{{ $message }}</p> @enderror
        </div>
        <div class="grid gap-2">
            <div class="flex items-center justify-between">
                <x-lynx-admin::ui.label for="password">Password</x-lynx-admin::ui.label>
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="text-muted-foreground hover:text-foreground text-sm underline-offset-4 hover:underline">Forgot your password?</a>
                @endif
            </div>
            <x-lynx-admin::ui.input id="password" name="password" type="password" required autocomplete="current-password" />
            @error('password') <p role="alert" class="text-destructive text-sm">{{ $message }}</p> @enderror
        </div>
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" class="blat-checkbox" name="remember" @checked(old('remember'))> Keep me signed in</label>
        <x-lynx-admin::ui.button type="submit" class="w-full">Sign in</x-lynx-admin::ui.button>
    </form>
</x-lynx-admin::auth-layout>
