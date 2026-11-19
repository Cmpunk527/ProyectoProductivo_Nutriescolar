<?php
/**
 * includes/estadisticas.php
 * -----------------------------------------------------------------------
 * Consulta y resume lo que un estudiante comió (tabla ConsumoAlimento,
 * que ahora registra el ADMINISTRADOR) para mostrar historial y
 * estadísticas al estudiante y a su acudiente.
 * Usa los mismos rangos de referencia de includes/reporte.php.
 * -----------------------------------------------------------------------
 */

require_once __DIR__ . '/reporte.php';

/**
 * Agrupa por día lo consumido por un usuario.
 * Devuelve: [ 'YYYY-MM-DD' => ['fecha', 'alimentos' => [nombres], 'totales' => [calorias, proteinas, carbohidratos, grasas]] ]
 * ordenado del día más reciente al más antiguo.
 *
 * @param string|null $desde fecha mínima (YYYY-MM-DD) o null para todo el historial
 */
function consumoPorDia(PDO $pdo, int $usuarioId, ?string $desde = null): array
{
    $sql = 'SELECT ca.Fecha, a.Nombre, i.Calorias, i.Proteinas, i.Carbohidratos, i.Grasas
            FROM ConsumoAlimento ca
            INNER JOIN Alimento a ON a.AlimentoId = ca.AlimentoId
            LEFT JOIN InformacionNutricional i ON i.AlimentoId = a.AlimentoId
            WHERE ca.UsuarioId = :uid'
         . ($desde !== null ? ' AND ca.Fecha >= :desde' : '')
         . ' ORDER BY ca.Fecha DESC, a.Nombre';

    $parametros = [':uid' => $usuarioId];
    if ($desde !== null) {
        $parametros[':desde'] = $desde;
    }
    $consulta = $pdo->prepare($sql);
    $consulta->execute($parametros);

    $dias = [];
    foreach ($consulta->fetchAll() as $fila) {
        $fecha = $fila['Fecha'];
        if (!isset($dias[$fecha])) {
            $dias[$fecha] = [
                'fecha'     => $fecha,
                'alimentos' => [],
                'totales'   => ['calorias' => 0.0, 'proteinas' => 0.0, 'carbohidratos' => 0.0, 'grasas' => 0.0],
            ];
        }
        $dias[$fecha]['alimentos'][]            = $fila['Nombre'];
        $dias[$fecha]['totales']['calorias']      += (float) $fila['Calorias'];
        $dias[$fecha]['totales']['proteinas']     += (float) $fila['Proteinas'];
        $dias[$fecha]['totales']['carbohidratos'] += (float) $fila['Carbohidratos'];
        $dias[$fecha]['totales']['grasas']        += (float) $fila['Grasas'];
    }
    return $dias;
}

/**
 * Resume varios días: promedio diario por nutriente, su estado frente al
 * rango recomendado y cuántos días estuvo bajo / adecuado / alto.
 */
function resumenEstadisticas(array $dias): array
{
    $totalDias = count($dias);
    $resumen   = ['dias' => $totalDias, 'nutrientes' => []];

    foreach (valoresReferenciaAlmuerzo() as $clave => $rango) {
        $suma   = 0.0;
        $conteo = ['bajo' => 0, 'adecuado' => 0, 'alto' => 0];
        foreach ($dias as $dia) {
            $valor = (float) $dia['totales'][$clave];
            $suma += $valor;
            $conteo[evaluarNutriente($valor, $rango)]++;
        }
        $promedio = $totalDias > 0 ? $suma / $totalDias : 0.0;

        $resumen['nutrientes'][$clave] = [
            'etiqueta' => $rango['etiqueta'],
            'unidad'   => $rango['unidad'],
            'min'      => $rango['min'],
            'max'      => $rango['max'],
            'promedio' => $promedio,
            'estado'   => $totalDias > 0 ? evaluarNutriente($promedio, $rango) : null,
            'conteo'   => $conteo,
        ];
    }
    return $resumen;
}

/** Fecha mínima del período elegido ('30' | '90' | 'todo'); null = sin límite */
function fechaDesdePeriodo(string $periodo): ?string
{
    if ($periodo === '30' || $periodo === '90') {
        return date('Y-m-d', strtotime('-' . (int) $periodo . ' days'));
    }
    return null;
}
