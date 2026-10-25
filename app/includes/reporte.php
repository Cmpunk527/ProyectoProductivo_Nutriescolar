<?php
/**
 * includes/reporte.php
 * -----------------------------------------------------------------------
 * Evalúa si el consumo de un usuario en un día está por debajo, dentro o
 * por encima de un rango de referencia, y arma/envía el reporte por correo.
 *
 * IMPORTANTE: los rangos de abajo son una referencia aproximada para un
 * ALMUERZO escolar (no para el día completo), basada en que un almuerzo
 * suele representar ~30% de las calorías diarias recomendadas para un
 * estudiante de colegio. Son valores con fines demostrativos/educativos,
 * no una recomendación médica o nutricional profesional.
 * -----------------------------------------------------------------------
 */

require_once __DIR__ . '/correo.php';

function valoresReferenciaAlmuerzo(): array
{
    return [
        'calorias'      => ['etiqueta' => 'Calorías',      'unidad' => 'kcal', 'min' => 500, 'max' => 700],
        'proteinas'     => ['etiqueta' => 'Proteínas',     'unidad' => 'g',    'min' => 15,  'max' => 25],
        'carbohidratos' => ['etiqueta' => 'Carbohidratos', 'unidad' => 'g',    'min' => 60,  'max' => 90],
        'grasas'        => ['etiqueta' => 'Grasas',        'unidad' => 'g',    'min' => 15,  'max' => 25],
    ];
}

/**
 * Clasifica un valor dentro de un rango: 'bajo' | 'adecuado' | 'alto'.
 */
function evaluarNutriente(float $valor, array $rango): string
{
    if ($valor < $rango['min']) return 'bajo';
    if ($valor > $rango['max']) return 'alto';
    return 'adecuado';
}

/**
 * Genera la evaluación completa (por nutriente) a partir de los totales
 * calculados de lo que el usuario realmente registró que comió.
 */
function generarEvaluacionConsumo(array $totales): array
{
    $evaluacion = [];
    foreach (valoresReferenciaAlmuerzo() as $clave => $rango) {
        $valor = (float) ($totales[$clave] ?? 0);
        $evaluacion[$clave] = [
            'etiqueta' => $rango['etiqueta'],
            'unidad'   => $rango['unidad'],
            'valor'    => $valor,
            'min'      => $rango['min'],
            'max'      => $rango['max'],
            'estado'   => evaluarNutriente($valor, $rango),
        ];
    }
    return $evaluacion;
}

/**
 * Arma el HTML del correo con el detalle del consumo y su evaluación,
 * y lo envía usando includes/correo.php (enviarCorreo()).
 */
function enviarReporteConsumo(string $destino, string $nombreUsuario, string $fecha, array $alimentos, array $evaluacion): bool
{
    $coloresEstado = ['bajo' => '#e53935', 'adecuado' => '#2e7d32', 'alto' => '#e53935'];
    $etiquetasEstado = ['bajo' => 'Bajo', 'adecuado' => 'Adecuado', 'alto' => 'Alto'];

    $huboBajo = false;
    $huboAlto = false;
    $filasHtml = '';
    foreach ($evaluacion as $item) {
        if ($item['estado'] === 'bajo') $huboBajo = true;
        if ($item['estado'] === 'alto') $huboAlto = true;
        $color = $coloresEstado[$item['estado']];
        $filasHtml .= '<tr>
          <td style="padding:8px 10px;border-bottom:1px solid #eee;">' . htmlspecialchars($item['etiqueta']) . '</td>
          <td style="padding:8px 10px;border-bottom:1px solid #eee;">' . number_format($item['valor'], 2) . ' ' . htmlspecialchars($item['unidad']) . '</td>
          <td style="padding:8px 10px;border-bottom:1px solid #eee;color:#888;">' . $item['min'] . '–' . $item['max'] . ' ' . htmlspecialchars($item['unidad']) . '</td>
          <td style="padding:8px 10px;border-bottom:1px solid #eee;color:' . $color . ';font-weight:bold;">' . $etiquetasEstado[$item['estado']] . '</td>
        </tr>';
    }

    if ($huboBajo && $huboAlto) {
        $veredicto = 'El consumo registrado tuvo nutrientes por debajo Y por encima de lo recomendado para un almuerzo escolar.';
    } elseif ($huboBajo) {
        $veredicto = 'El consumo registrado estuvo por debajo de lo recomendado en algunos nutrientes.';
    } elseif ($huboAlto) {
        $veredicto = 'El consumo registrado estuvo por encima de lo recomendado en algunos nutrientes.';
    } else {
        $veredicto = 'El consumo registrado estuvo dentro de los rangos recomendados. ¡Buen trabajo!';
    }

    $listaAlimentos = $alimentos ? implode(', ', $alimentos) : 'No se registraron alimentos.';

    $cuerpo = '
    <div style="font-family:Nunito,Arial,sans-serif;max-width:560px;margin:auto;">
      <h2 style="color:#f39c12;">NutriEscolar — Reporte nutricional</h2>
      <p>Hola ' . htmlspecialchars($nombreUsuario) . ',</p>
      <p>Este es el reporte del consumo registrado el <strong>' . htmlspecialchars($fecha) . '</strong>:</p>
      <p><strong>Alimentos consumidos:</strong> ' . htmlspecialchars($listaAlimentos) . '</p>
      <table style="width:100%;border-collapse:collapse;margin:16px 0;font-size:14px;">
        <thead>
          <tr style="background:#fdf1de;text-align:left;">
            <th style="padding:8px 10px;">Nutriente</th>
            <th style="padding:8px 10px;">Consumido</th>
            <th style="padding:8px 10px;">Recomendado (almuerzo)</th>
            <th style="padding:8px 10px;">Estado</th>
          </tr>
        </thead>
        <tbody>' . $filasHtml . '</tbody>
      </table>
      <p style="background:#fdf1de;padding:12px 16px;border-radius:8px;">' . htmlspecialchars($veredicto) . '</p>
      <p style="font-size:12px;color:#999;">Los valores de referencia son aproximados y con fines educativos;
      no reemplazan la orientación de un profesional en nutrición.</p>
    </div>';

    $textoPlano = "Reporte nutricional del {$fecha} para {$nombreUsuario}. {$veredicto}";

    return enviarCorreo($destino, $nombreUsuario, 'Tu reporte nutricional - NutriEscolar', $cuerpo, $textoPlano);
}
