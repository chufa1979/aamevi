{{-- El borde inferior verde de 6px es la firma visual del sitio institucional --}}
<header class="border-b-[6px] border-primary bg-canvas pt-5 pb-5 lg:pt-0">
    <div class="container-site flex flex-wrap items-center justify-between lg:items-end">
        <a href="/" class="w-[200px] xl:w-[250px]" aria-label="AAMEVi — Inicio">
            <x-brand-logo />
        </a>

        <button type="button" data-menu-toggle
                class="p-2 text-2xl lg:hidden"
                aria-expanded="false" aria-controls="main-menu" aria-label="Abrir menú">
            <x-ui.icon name="menu" data-icon-open />
            <x-ui.icon name="close" data-icon-close class="hidden" />
        </button>

        <nav id="main-menu" data-menu
             class="max-h-0 w-full overflow-hidden transition-[max-height] duration-500 lg:max-h-none lg:w-auto lg:overflow-visible">
            <ul class="mt-5 text-center lg:mt-0 lg:flex lg:justify-between">
                @foreach (config('navigation.main') as $item)
                    @php $active = request()->is($item['match']); @endphp

                    <li class="group relative lg:mx-6">
                        <a href="{{ $item['href'] }}"
                           @class(['nav-link block py-1 text-[15px] lg:text-[13px]', 'text-accent' => $active])>
                            {{ $item['label'] }}
                        </a>

                        @isset($item['children'])
                            {{--
                                Desktop: panel naranja translúcido que aparece al pasar el mouse,
                                igual que el submenú de www.aamevi.ar
                            --}}
                            <ul class="lg:pointer-events-none lg:absolute lg:top-full lg:left-0 lg:z-10 lg:min-w-[9rem]
                                       lg:bg-accent-overlay lg:px-4 lg:pt-2 lg:pb-4 lg:opacity-0
                                       lg:transition-opacity lg:duration-500
                                       lg:group-hover:pointer-events-auto lg:group-hover:opacity-100">
                                @foreach ($item['children'] as $child)
                                    <li class="lg:border-b-2 lg:border-white">
                                        <a href="{{ $child['href'] }}"
                                           @class([
                                               'nav-link block py-1 pl-4 text-sm lg:pl-0 lg:text-left lg:text-[11px]',
                                               'lg:font-bold lg:italic lg:normal-case lg:text-white lg:hover:text-white',
                                               'text-accent lg:text-white' => request()->is($child['match']),
                                           ])>
                                            {{ $child['label'] }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        @endisset
                    </li>
                @endforeach
            </ul>
        </nav>

        <form action="/buscar" method="GET" role="search"
              class="mt-5 flex w-full items-center justify-center lg:mt-0 lg:w-auto">
            <button type="submit" class="px-2" aria-label="Buscar">
                <x-ui.icon name="search" />
            </button>
            {{-- Radio asimétrico y bordes parciales: detalle propio del sitio madre --}}
            <input type="text" name="q" value="{{ request('q') }}"
                   aria-label="Buscar en la plataforma"
                   class="w-[110px] rounded-none rounded-br-[15px] border-0 border-r-[1.5px] border-b-[1.5px]
                          border-primary bg-transparent pb-1 text-xs focus:outline-none">
        </form>
    </div>
</header>
