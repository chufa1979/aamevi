@php
    $contact = config('navigation.contact');

    $social = [
        ['href' => $contact['whatsapp'], 'label' => 'WhatsApp', 'icon' => 'whatsapp'],
        ['href' => $contact['email'], 'label' => 'E-Mail', 'icon' => 'email'],
    ];

    $networks = [
        ['href' => $contact['instagram'], 'label' => 'Instagram', 'icon' => 'instagram'],
        ['href' => $contact['linkedin'], 'label' => 'LinkedIn', 'icon' => 'linkedin'],
    ];
@endphp

<footer class="bg-ink text-center text-white">
    <div class="container-site flex flex-col py-5 md:flex-row md:items-end md:justify-between md:py-8">
        <x-footer-icons :items="$social" />

        <ul class="md:flex">
            @foreach (\App\Support\Navigation::footer(auth()->user()) as $item)
                <li class="my-2 md:mx-6 md:my-0">
                    <a href="{{ $item['href'] }}"
                       @class(['text-sm uppercase no-underline', 'font-semibold' => request()->is($item['match'])])>
                        {{ $item['label'] }}
                    </a>
                </li>
            @endforeach
        </ul>

        <x-footer-icons :items="$networks" />
    </div>

    <p class="container-site pb-6 text-[11px] text-white/60">
        AAMEVi — Asociación Argentina de Medicina del Estilo de Vida ·
        <a href="{{ $contact['site'] }}" target="_blank" rel="noreferrer" class="underline">www.aamevi.ar</a>
    </p>
</footer>
