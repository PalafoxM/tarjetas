<?php
$monto_total = round((float) str_replace(['$', ',', ' '], '', (string) ($monto_total ?? 0)), 2);

$apocoparUno = static function (string $texto): string {
    if (preg_match('/veintiuno$/u', $texto)) {
        return preg_replace('/veintiuno$/u', 'veintiún', $texto) ?? $texto;
    }
    if (preg_match('/ y uno$/u', $texto)) {
        return preg_replace('/ y uno$/u', ' y un', $texto) ?? $texto;
    }
    if ($texto === 'uno') {
        return 'un';
    }
    return preg_replace('/ uno$/u', ' un', $texto) ?? $texto;
};

$convertirEnteroALetras = null;
$convertirEnteroALetras = static function (int $numero) use (&$convertirEnteroALetras, $apocoparUno): string {
    $unidades = [
        0 => 'cero', 1 => 'uno', 2 => 'dos', 3 => 'tres', 4 => 'cuatro',
        5 => 'cinco', 6 => 'seis', 7 => 'siete', 8 => 'ocho', 9 => 'nueve',
        10 => 'diez', 11 => 'once', 12 => 'doce', 13 => 'trece', 14 => 'catorce',
        15 => 'quince', 16 => 'dieciséis', 17 => 'diecisiete', 18 => 'dieciocho',
        19 => 'diecinueve', 20 => 'veinte', 21 => 'veintiuno', 22 => 'veintidós',
        23 => 'veintitrés', 24 => 'veinticuatro', 25 => 'veinticinco',
        26 => 'veintiséis', 27 => 'veintisiete', 28 => 'veintiocho', 29 => 'veintinueve',
    ];
    $decenas = [30 => 'treinta', 40 => 'cuarenta', 50 => 'cincuenta', 60 => 'sesenta', 70 => 'setenta', 80 => 'ochenta', 90 => 'noventa'];
    $centenas = [200 => 'doscientos', 300 => 'trescientos', 400 => 'cuatrocientos', 500 => 'quinientos', 600 => 'seiscientos', 700 => 'setecientos', 800 => 'ochocientos', 900 => 'novecientos'];

    if ($numero < 30) {
        return $unidades[$numero];
    }
    if ($numero < 100) {
        $base = intdiv($numero, 10) * 10;
        $resto = $numero % 10;
        return $decenas[$base] . ($resto > 0 ? ' y ' . $convertirEnteroALetras($resto) : '');
    }
    if ($numero === 100) {
        return 'cien';
    }
    if ($numero < 1000) {
        $base = intdiv($numero, 100) * 100;
        $resto = $numero % 100;
        $prefijo = $base === 100 ? 'ciento' : $centenas[$base];
        return $prefijo . ($resto > 0 ? ' ' . $convertirEnteroALetras($resto) : '');
    }
    if ($numero < 1000000) {
        $miles = intdiv($numero, 1000);
        $resto = $numero % 1000;
        $prefijo = $miles === 1 ? 'mil' : $apocoparUno($convertirEnteroALetras($miles)) . ' mil';
        return $prefijo . ($resto > 0 ? ' ' . $convertirEnteroALetras($resto) : '');
    }
    if ($numero < 1000000000) {
        $millones = intdiv($numero, 1000000);
        $resto = $numero % 1000000;
        $prefijo = $millones === 1
            ? 'un millón'
            : $apocoparUno($convertirEnteroALetras($millones)) . ' millones';
        return $prefijo . ($resto > 0 ? ' ' . $convertirEnteroALetras($resto) : '');
    }

    $milesDeMillones = intdiv($numero, 1000000000);
    $resto = $numero % 1000000000;
    $prefijo = $milesDeMillones === 1
        ? 'mil millones'
        : $apocoparUno($convertirEnteroALetras($milesDeMillones)) . ' mil millones';
    return $prefijo . ($resto > 0 ? ' ' . $convertirEnteroALetras($resto) : '');
};

$convertirNumeroALetras = static function ($valor) use ($convertirEnteroALetras, $apocoparUno): string {
    $monto = round(abs((float) $valor), 2);
    $enteros = (int) floor($monto);
    $centavos = (int) round(($monto - $enteros) * 100);

    if ($centavos === 100) {
        $enteros++;
        $centavos = 0;
    }

    $letras = $apocoparUno($convertirEnteroALetras($enteros));
    $moneda = $enteros === 1 ? 'peso' : 'pesos';
    $separadorMoneda = $enteros >= 1000000 && $enteros % 1000000 === 0 ? ' de ' : ' ';

    $resultado = sprintf('%s%s%s %02d/100 M.N.', $letras, $separadorMoneda, $moneda, $centavos);
    if (function_exists('mb_strtoupper')) {
        return mb_strtoupper($resultado, 'UTF-8');
    }

    return strtr(strtoupper($resultado), [
        'á' => 'Á', 'é' => 'É', 'í' => 'Í', 'ó' => 'Ó', 'ú' => 'Ú', 'ñ' => 'Ñ',
    ]);
};
?>
<!DOCTYPE html>
<html>
<head>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10pt;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        th, td {
            border: 1px solid #000;
            padding: 5px;
            text-align: center;
        }
        .bg-black {
            background-color: #000;
            color: #fff;
        }
        .bg-grey {
            background-color: #e0e0e0;
            font-weight: bold;
        }
        .text-left { text-align: left; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .no-border { border: none; }
        .header-title {
            text-align: center;
            font-size: 14pt;
            font-weight: bold;
            margin-bottom: 20px;
        }
         .logo{
            /* border:3px solid red; */
            margin: 0;
            padding: 0;
            left:2%;
            top:0;
            position: absolute;
            width:32%;
            height: 10%;
            background-image: url('<?= $logo ?>');
            background-size:100% 100%;
            background-repeat: no-repeat;
            background-attachment: fixed;
        }

    </style>
    <div style="position: absolute;text-align: left;left:85%; font-size: 9px; color: #999; margin-bottom: 5px;">FORMATO PT - 26</div>
    <table width="100%" style="border: none; margin-bottom: 20px;">
        <tr>
            <td width="30%" style="border: none; vertical-align: middle; text-align: left;">
                <div class="logo"></div>
            </td>
            <td width="70%" style="border: none; vertical-align: top;">
                
                <div style="text-align: center;">
                    <div style="font-weight: bold; font-size: 12pt; color: #000;">GOBIERNO DEL ESTADO DE GUANAJUATO</div>
                    <div style="font-size: 10pt; color: #000; margin-top: 5px;">FORMATO DE PAGO A TERCEROS</div>
                </div>
            </td>
        </tr>
    </table>

    <!-- TABLA 1: RAMO -->
    <table>
        <thead>
            <tr class="bg-black">
                <th colspan="3" style="color:white;">RAMO O ENTIDAD REMITENTE</th>
            </tr>
        </thead>
        <tbody>
            <tr class="bg-grey">
                <td colspan="3">21 SECRETARIA DE TURISMO E IDENTIDAD</td>
            </tr>
            <tr class="bg-grey">
                <td width="33%">DIVISIÓN</td>
                <td width="33%">FECHA TRÁMITE</td>
                <td width="33%">FOLIO</td>
            </tr>
            <tr>
                <td>21</td>
                <td><?=  date('d/m/Y') ?></td>
            <td><h6>PT SECTURI/SSIDT/DGCT/FIC-TA/<?= $id_formateado ?>/2026</h6></td>
            </tr>
        </tbody>
    </table>

    <!-- TABLA 2: ITEMS -->
    <table>
        <thead>
            <tr class="bg-black">
                <th colspan="5" style="color:white;">DATOS PROPORCIONADOS POR LA DEPENDENCIA</th>
            </tr>
            <tr class="bg-grey">
                <td colspan="5">REFERENCIA AL DOCUMENTO</td>
            </tr>
            <tr>
                <th width="20%">COMPROBANTE</th>
                <th width="17%">PROYECTO</th>
                <th width="10%">PARTIDA</th>
                <th width="15%">IMPORTE</th>
                <th width="38%">OBSERVACIONES</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $rows = isset($periodo_factura_rows) && !empty($periodo_factura_rows) ? $periodo_factura_rows : [];
            $detalleRows = [];

            foreach ($rows as $row) {
                $tieneRetenciones = !empty($mostrar_retenciones_pt) && !empty($row->tiene_retenciones_calculadas);
                $importePrincipal = $tieneRetenciones
                    ? (float) ($row->subtotal_calculado ?? 0) + (float) ($row->iva_calculado ?? 0)
                    : (float) str_replace(',', '', (string) ($row->importe ?? 0));

                $detalleRows[] = [
                    'comprobante' => $row->no_comprobante ?? '',
                    'proyecto' => $tieneRetenciones ? 'SUBTOTAL' : ($row->proyecto ?? ''),
                    'partida' => $row->partida ?? '',
                    'importe' => $importePrincipal,
                ];

                if ($tieneRetenciones && isset($row->isr) && (float) $row->isr > 0) {
                    $detalleRows[] = [
                        'comprobante' => $row->no_comprobante ?? '',
                        'proyecto' => 'ISR',
                        'partida' => '',
                        'importe' => -(float) $row->isr,
                    ];
                }

                if ($tieneRetenciones && isset($row->impuesto_local) && (float) $row->impuesto_local > 0) {
                    $detalleRows[] = [
                        'comprobante' => $row->no_comprobante ?? '',
                        'proyecto' => 'ISR CEDULAR',
                        'partida' => '',
                        'importe' => -(float) $row->impuesto_local,
                    ];
                }
            }

            if (empty($detalleRows)) {
                $detalleRows[] = ['comprobante' => '', 'proyecto' => '', 'partida' => '', 'importe' => 0];
            }

            foreach($detalleRows as $index => $detalle):
            ?>
            <tr>
                <td><?= $detalle['comprobante'] ?></td>
                <td>E027QC04182601</td>
                <td>2210</td>
                <td><?= $monto_total < 0 ? '' : '$' ?><?= number_format(abs((float) $monto_total), 2) ?></td>
                
                <?php if($index === 0): ?>
                <td rowspan="<?= count($detalleRows) ?>" class="text-left" style="vertical-align: top;">
                    <div class="font-bold">DATOS DEL PROVEEDOR NACIONAL</div>
                   <!-- Hardcoded example structure as per requirements, could be dynamic -->
                   <div><span class="font-bold">No. PROVEEDOR:</span> <?= isset($establecimiento->no_proveedor) ? $establecimiento->no_proveedor : '' ?></div>
                   <div><span class="font-bold">RFC:</span> <?= isset($establecimiento->rfc) ? $establecimiento->rfc : '' ?></div>
                   <div><span class="font-bold">NOMBRE:</span> <?= isset($establecimiento->razon_social) ? $establecimiento->razon_social : '' ?></div>
                   <div><span class="font-bold">NO. CUENTA:</span> <?= isset($establecimiento->no_cuenta) ? $establecimiento->no_cuenta : '' ?></div>
                   <div><span class="font-bold">BANCO:</span> <?= isset($establecimiento->banco) ? $establecimiento->banco : '' ?></div>
                   <div><span class="font-bold">CLABE:</span> <?= isset($establecimiento->clabe) ? $establecimiento->clabe : '' ?></div>
                </td>
                <?php endif; ?>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- TABLE 3: TOTALS -->
    <table>
        <tr>
            <td colspan="3" class="text-left no-border">
                <span class="font-bold">No. CONTRATO y/o CONVENIO:</span> <?= isset($establecimiento->no_contrato) ? $establecimiento->no_contrato : '' ?>
                <br>
                <span class="font-bold">No. RESERVA:</span> 5413298
            </td>
            <td width="30%" class="font-bold">
                <?= $monto_total < 0 ? '' : '$' ?><?= number_format(abs((float) $monto_total), 2) ?>
            </td>
        </tr>
        <tr>
            <td colspan="4" class="text-center font-bold">
                <?= esc($convertirNumeroALetras($monto_total)) ?>
            </td>
        </tr>
    </table>

    <!-- TABLE 4: SIGNATURES -->
    <table style="margin-top: 20px;">
        <thead>
            <tr class="bg-black">
                <th colspan="3" style="color:white;">AUTORIZACIONES</th>
            </tr>
            <tr class="bg-grey">
                <th width="33%">DIRECTOR/A GENERAL ADMINISTRATIVO/A</th>
                <th width="33%">AUTORIZA</th>
                <th width="33%">RESPONSABLE DEL PROYECTO</th>
            </tr>
        </thead>
        <tbody>
            <tr style="height: 100px;">
                <td style="height: 80px; vertical-align: bottom;">
                    <br><br><br>
                    <strong>RODRIGO GONZALEZ GUERRERO</strong><br>
                    <span style="font-size: 8pt;">DIRECTOR/A GENERAL ADMINISTRATIVO/A</span>
                </td>
                <td style="height: 80px; vertical-align: bottom;">
                    <br><br><br>
                     <strong>MTRO. DAVID AYALA SAUCEDO</strong><br>
                    <span style="font-size: 6pt;">SUBSECRETARIO DE IDENTIDAD Y DESARROLLO TURÍSTICO POR ACUERDO SECRETARIAL N° 003-02/2026</span>
                </td>
                <td style="height: 80px; vertical-align: bottom;">
                    <br><br><br>
                     <strong>MTRO. DAVID AYALA SAUCEDO</strong><br>
                    <span style="font-size: 8pt;">SUBSECRETARIO DE IDENTIDAD Y DESARROLLO TURÍSTICO</span>
                </td>
            </tr>
            <tr>
                 <td colspan="2" class="no-border"></td>
                 <td class="bg-grey font-bold">RESPONSABLE DEL PROYECTO</td>
            </tr>
             <tr>
                 <td colspan="2" class="no-border"></td>
                 <td style="height: 80px; vertical-align: bottom;">
                      <br><br><br>
                     <strong>HUGO RAMÍREZ DUARTE</strong><br>
                    <span style="font-size: 8pt;">DIRECTOR GENERAL DE COMPETITIVIDAD TURÍSTICA</span>
                 </td>
            </tr>
        </tbody>
    </table>

    <div style="margin-top: 30px; font-size: 8pt; text-align: justify; font-style: italic;">
        El presente documento fue recibido y firmado de conformidad con la Ley sobre el uso de medios electrónicos y firma electrónica para el Estado de Guanajuato y sus Municipios. En virtud de la equivalencia funcional, la firma electrónica certificada se equipara a la firma autógrafa. Se privilegian las políticas de ahorro, racionalidad y austeridad del gasto público, contenidas en el artículo 55, de la Ley para el Ejercicio y Control de los Recursos Públicos para el Estado y los Municipios de Guanajuato; y, Artículos 1 y 15, segundo párrafo de los Lineamientos Generales de Racionalidad, Austeridad y Disciplina Presupuestal de la Administración Pública Estatal para el Ejercicio Fiscal 2026.
    </div>
</body>
</html>
