<?php

namespace App\Filament\Tables;

use Filament\Tables\Table;

/**
 * Habilita el reordenamiento arrastrando filas, sin perder el unique de
 * `(padre_id, order_number)`.
 *
 * Filament resuelve el reordenamiento con un solo UPDATE y un CASE. Intercambiar
 * dos posiciones así choca contra el unique: al asignarle el 1 a la fila que
 * estaba segunda, la que estaba primera todavía tiene el 1.
 *
 * La salida es correr antes las filas afectadas por encima del máximo actual,
 * de modo que el rango 1..n quede libre cuando Filament escriba los valores
 * definitivos. Cada fila se mueve a una posición mayor que cualquier existente,
 * así que la fase previa tampoco puede chocar.
 *
 * Va de la mano con `paginated(false)`: el reordenamiento solo recibe las filas
 * visibles, y si quedaran filas fuera de la página, el rango 1..n no estaría
 * libre. Arrastrar entre páginas tampoco tendría sentido.
 */
class DragToReorder
{
    public static function apply(Table $table, string $column = 'order_number'): Table
    {
        return $table
            ->reorderable($column)
            ->paginated(false)
            ->beforeReordering(function (Table $table, array $order) use ($column): void {
                $libre = ((int) $table->getQuery()->max($column)) + 1;

                foreach (array_values($order) as $posicion => $key) {
                    $table->getQuery()
                        ->whereKey($key)
                        ->update([$column => $libre + $posicion]);
                }
            });
    }
}
