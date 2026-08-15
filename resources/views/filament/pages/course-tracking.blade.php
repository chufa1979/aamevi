{{--
    Grilla de seguimiento: una fila por alumno, una columna por clase.

    La tabla scrollea sola en horizontal —un curso de veinte clases no entra en
    pantalla— y la primera columna queda fija para no perder de vista de quién
    es cada fila.
--}}
<x-filament-panels::page>
    @if ($alumnos->isEmpty())
        <x-filament::section>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Todavía no hay alumnos cursando. Las inscripciones se aprueban en la solapa
                <strong>Alumnos del curso</strong>.
            </p>
        </x-filament::section>
    @elseif ($totalClases === 0)
        <x-filament::section>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Este curso todavía no tiene clases. Se cargan desde la solapa <strong>Contenidos</strong>.
            </p>
        </x-filament::section>
    @else
        <x-filament::section>
            <div class="flex flex-wrap gap-3 text-xs">
                @foreach (\App\Enums\ClassProgressState::cases() as $estado)
                    <span class="inline-flex items-center gap-1.5">
                        <x-filament::icon
                            :icon="$estado->getIcon()"
                            @class([
                                'h-4 w-4',
                                'text-success-600 dark:text-success-400' => $estado->getColor() === 'success',
                                'text-warning-600 dark:text-warning-400' => $estado->getColor() === 'warning',
                                'text-danger-600 dark:text-danger-400' => $estado->getColor() === 'danger',
                                'text-info-600 dark:text-info-400' => $estado->getColor() === 'info',
                                'text-gray-400 dark:text-gray-500' => $estado->getColor() === 'gray',
                            ])
                        />
                        <span class="text-gray-600 dark:text-gray-400">{{ $estado->getLabel() }}</span>
                    </span>
                @endforeach
            </div>
        </x-filament::section>

        <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white dark:border-white/10 dark:bg-gray-900">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-white/10">
                        <th rowspan="2" class="sticky left-0 z-10 bg-white px-4 py-2 text-left font-medium text-gray-950 dark:bg-gray-900 dark:text-white">
                            Alumno
                        </th>
                        <th rowspan="2" class="px-3 py-2 text-center font-medium text-gray-950 dark:text-white">
                            Avance
                        </th>
                        @foreach ($modules as $module)
                            @continue($module->classes->isEmpty())
                            <th colspan="{{ $module->classes->count() }}"
                                class="border-l border-gray-200 px-3 py-2 text-center text-xs font-medium text-gray-500 dark:border-white/10 dark:text-gray-400">
                                {{ $module->title }}
                            </th>
                        @endforeach
                    </tr>
                    <tr class="border-b border-gray-200 dark:border-white/10">
                        @foreach ($modules as $module)
                            @foreach ($module->classes as $class)
                                <th class="@if ($loop->first) border-l border-gray-200 dark:border-white/10 @endif px-2 py-2 text-center text-xs font-normal text-gray-500 dark:text-gray-400"
                                    title="{{ $class->title }}">
                                    {{ $class->order_number }}
                                </th>
                            @endforeach
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                    @foreach ($alumnos as $enrollment)
                        <tr>
                            <td class="sticky left-0 z-10 whitespace-nowrap bg-white px-4 py-2 text-gray-950 dark:bg-gray-900 dark:text-white">
                                {{ $enrollment->student?->user?->full_name ?? '—' }}
                            </td>
                            <td class="px-3 py-2 text-center tabular-nums text-gray-600 dark:text-gray-400">
                                {{ $avances[$enrollment->student_id] ?? 0 }}%
                            </td>
                            @foreach ($modules as $module)
                                @foreach ($module->classes as $class)
                                    @php
                                        $estado = $grilla[$enrollment->student_id][$class->id] ?? \App\Enums\ClassProgressState::Available;
                                    @endphp
                                    <td class="@if ($loop->first) border-l border-gray-200 dark:border-white/10 @endif px-2 py-2 text-center">
                                        <x-filament::icon
                                            :icon="$estado->getIcon()"
                                            :title="$class->title.' — '.$estado->getLabel()"
                                            @class([
                                                'mx-auto h-5 w-5',
                                                'text-success-600 dark:text-success-400' => $estado->getColor() === 'success',
                                                'text-warning-600 dark:text-warning-400' => $estado->getColor() === 'warning',
                                                'text-danger-600 dark:text-danger-400' => $estado->getColor() === 'danger',
                                                'text-info-600 dark:text-info-400' => $estado->getColor() === 'info',
                                                'text-gray-300 dark:text-gray-600' => $estado->getColor() === 'gray',
                                            ])
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
