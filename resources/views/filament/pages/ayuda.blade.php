{{--
    Misma idea que ayuda.blade.php del sitio público, pero adentro del panel:
    estilos en resources/css/filament/admin.css, no en Tailwind, que acá no
    carga.
--}}
<x-filament-panels::page>
    <p class="aamevi-help-intro">
        Videos cortos que muestran cómo usar las pantallas más comunes de tu perfil.
    </p>

    @php $videos = $this->getVideos(); @endphp

    @if (empty($videos))
        <x-filament::section>
            <p>Todavía no hay videos instructivos para tu perfil.</p>
        </x-filament::section>
    @else
        <div class="aamevi-help-grid">
            @foreach ($videos as $video)
                <x-filament::section>
                    <video controls preload="none" class="aamevi-help-video"
                           src="{{ asset('videos-ayuda/'.$video['file']) }}">
                    </video>

                    <h3 class="aamevi-help-title">{{ $video['title'] }}</h3>
                    <p class="aamevi-help-description">{{ $video['description'] }}</p>
                </x-filament::section>
            @endforeach
        </div>
    @endif
</x-filament-panels::page>
