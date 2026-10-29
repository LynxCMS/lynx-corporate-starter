{{-- Lynx admin's sidebar footer (components/ui/sidebar-footer), with a notice above it on the public
     demo: anyone may sign in there, and site:reset-demo deletes their work every hour. --}}
@if (config('site.demo_reset') === true)
    <div class="px-2 group-data-[collapsible=icon]:hidden" data-demo-reset="hourly">
        <x-lynx-admin::ui.alert tone="warning" role="status">
            <x-lucide-rotate-ccw aria-hidden="true" />
            <x-lynx-admin::ui.alert-title>Public demo</x-lynx-admin::ui.alert-title>
            <x-lynx-admin::ui.alert-description>
                <p>Anyone can sign in, so every hour the site goes back to its demo content. Edits and uploads are deleted. Next reset in {{ now()->addHour()->startOfHour()->diffForHumans(['syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE]) }}.</p>
            </x-lynx-admin::ui.alert-description>
        </x-lynx-admin::ui.alert>
    </div>
@endif
<div data-slot="sidebar-footer" data-sidebar="footer" {{ $attributes->twMerge('flex flex-col gap-2 p-2') }}>
    {{ $slot }}
</div>
