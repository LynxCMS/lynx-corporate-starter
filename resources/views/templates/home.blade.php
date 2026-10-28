{{-- The home page: its sections, in the order editors set, each rendered by fields/sections/{type}. --}}
<x-layout :page="$page">
    @matrix($page->sections)
</x-layout>
