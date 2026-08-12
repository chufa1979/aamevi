@props(['items'])

<ul class="flex justify-center gap-2 py-5 md:py-0">
    @foreach ($items as $item)
        <li>
            <a href="{{ $item['href'] }}"
               @if (! str_starts_with($item['href'], 'mailto:')) target="_blank" rel="noreferrer" @endif
               aria-label="{{ $item['label'] }}"
               class="block p-1 text-2xl transition-colors hover:text-primary">
                <x-ui.icon :name="$item['icon']" />
            </a>
        </li>
    @endforeach
</ul>
