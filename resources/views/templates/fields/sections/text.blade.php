{{-- A heading and a few paragraphs, for anything the other sections don't cover. --}}
<x-band :block="$block" type="text" :heading="$block->heading">
    <div class="max-w-3xl text-lg leading-relaxed [&_a]:text-signal [&_a]:underline [&_p+p]:mt-4">@field($block->body)</div>
</x-band>
