{{-- The page's counts in the editor's header (App\Stats\Admin\PageLine). --}}
<p class="text-muted-foreground text-sm tabular-nums" data-stats-line data-views="{{ $views }}" @if ($downloads !== null) data-downloads="{{ $downloads }}" @endif>
    {{ trans_choice('stats.views', $views, ['count' => \Illuminate\Support\Number::format($views, locale: app()->getLocale())]) }}@if ($downloads !== null) · {{ trans_choice('stats.downloads', $downloads, ['count' => \Illuminate\Support\Number::format($downloads, locale: app()->getLocale())]) }}@endif, {{ trans_choice('stats.last_days', $days, ['count' => $days]) }}
</p>
