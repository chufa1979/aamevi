{{--
    Grilla de seguimiento: una fila por alumno, una columna por clase.

    Los estilos van en resources/css/filament/admin.css y no en clases de
    Tailwind: el panel de Filament no carga el build de Vite del sitio, así que
    las utilidades no existen acá. Cada celda lleva su estado en un data-attr y
    el color sale de ahí.
--}}
<x-filament-panels::page>
    @if ($alumnos->isEmpty())
        <x-filament::section>
            <p>
                Todavía no hay alumnos cursando. Las inscripciones se aprueban en la solapa
                <strong>Alumnos del curso</strong>.
            </p>
        </x-filament::section>
    @elseif ($totalClases === 0)
        <x-filament::section>
            <p>
                Este curso todavía no tiene clases. Se cargan desde la solapa <strong>Contenidos</strong>.
            </p>
        </x-filament::section>
    @else
        <x-filament::section>
            <ul class="aamevi-tracking-legend">
                @foreach (\App\Enums\ClassProgressState::cases() as $estado)
                    <li>
                        <x-filament::icon
                            :icon="$estado->getIcon()"
                            class="aamevi-tracking-state"
                            :data-state="$estado->value"
                        />
                        <span>{{ $estado->getLabel() }}</span>
                    </li>
                @endforeach
            </ul>
        </x-filament::section>

        <div class="aamevi-tracking">
            <table>
                <thead>
                    <tr>
                        <th rowspan="2" class="aamevi-tracking-name">Alumno</th>
                        <th rowspan="2">Avance</th>
                        @foreach ($modules as $module)
                            @continue($module->classes->isEmpty())
                            <th colspan="{{ $module->classes->count() }}" class="aamevi-tracking-module">
                                {{ $module->title }}
                            </th>
                        @endforeach
                    </tr>
                    <tr>
                        @foreach ($modules as $module)
                            @foreach ($module->classes as $class)
                                <th
                                    @class(['aamevi-tracking-class', 'aamevi-tracking-module-start' => $loop->first])
                                    title="{{ $class->title }}"
                                >
                                    {{ $class->order_number }}
                                </th>
                            @endforeach
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($alumnos as $enrollment)
                        <tr>
                            <td class="aamevi-tracking-name">
                                {{ $enrollment->student?->user?->full_name ?? '—' }}
                            </td>
                            <td class="aamevi-tracking-progress">
                                {{ $avances[$enrollment->student_id] ?? 0 }}%
                            </td>
                            @foreach ($modules as $module)
                                @foreach ($module->classes as $class)
                                    @php
                                        $estado = $grilla[$enrollment->student_id][$class->id]
                                            ?? \App\Enums\ClassProgressState::Available;
                                    @endphp
                                    <td @class(['aamevi-tracking-module-start' => $loop->first])>
                                        <x-filament::icon
                                            :icon="$estado->getIcon()"
                                            class="aamevi-tracking-state"
                                            :data-state="$estado->value"
                                            :title="$class->title.' — '.$estado->getLabel()"
                                        />
                                    </td>
                                @endforeach
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-filament-panels::page>
