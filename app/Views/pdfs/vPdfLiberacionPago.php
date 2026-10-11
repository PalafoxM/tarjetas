<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Formato de Liberación de Pago</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11pt;
            line-height: 1.5;
            color: #000;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .header-table td {
            vertical-align: top;
        }
        .date-folio {
            text-align: right;
            font-weight: bold;
            margin-bottom: 30px;
            margin-top:10%;
        }
        .recipient {
            font-weight: bold;
            margin-bottom: 20px;
        }
        .content {
            text-align: justify;
            margin-bottom: 20px;
        }
        .signature-section {
            margin-top: 60px;
            text-align: center;
            font-weight: bold;
        }
        .signature-line {
            border-top: 1px solid #000;
            width: 60%;
            margin: 10px auto;
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
</head>
<body>

    <!-- Header with ロゴ -->
    <table class="header-table">
        <tr>
              <td width="50%" style="border: none; vertical-align: middle; text-align: left;">
                <div class="logo"></div>
            </td>
        </tr>
    </table>

    <!-- Date and Folio -->
    <?php
        $registro_pt = isset($registro_pt) && is_object($registro_pt) ? $registro_pt : (object) [];
        $meses = array("ENERO","FEBRERO","MARZO","ABRIL","MAYO","JUNIO","JULIO","AGOSTO","SEPTIEMBRE","OCTUBRE","NOVIEMBRE","DICIEMBRE");
        $fecha = date("d/m/Y");
        $fecha_texto = "SILAO, GTO. " . date("d") . " DE " . $meses[date("n", strtotime($fecha))-2] . " DEL " . date("Y");
        
    ?>
    <div class="date-folio">
        <div><?= $fecha_texto ?></div>
        <div style="margin-top: 10px;">FOLIO <span style="font-style: italic;"><?= $folio ?></span></div>
    </div>

    <!-- Recipient -->
    <div class="recipient">
        <div>L.R.I. Rodrigo González Guerrero</div> <!-- Hardcoded as per image -->
        <div>Director General Administrativo</div>
        <div>Presente</div>
    </div>

    <!-- Content Body 1 -->
    <?php
        // Prepare dynamic values
        $importe_total = (string) ($registro_pt->importe_total_num ?? '0.00');
        $importe_letra = (string) (($importe_letra ?? '') !== '' ? $importe_letra : ($registro_pt->importe_letra ?? ''));
        
        // Comprobantes (comma separated)
        $comprobantes = [];
        $proyectos = [];
        $partidas = [];
        
        if(isset($periodo_factura_rows) && !empty($periodo_factura_rows)){
            foreach($periodo_factura_rows as $row){
                if(isset($row->no_comprobante) && $row->no_comprobante) $comprobantes[] = $row->no_comprobante;
                if(isset($row->proyecto) && $row->proyecto) {
                     $descProj = (isset($row->dsc_proyecto) && $row->dsc_proyecto) ? ' (' . $row->dsc_proyecto . ')' : '';
                     $proyectos[] = $row->proyecto . $descProj;
                }
                if(isset($row->partida) && $row->partida) {
                     $desc = (isset($row->dsc_partida) && $row->dsc_partida) ? ' (' . $row->dsc_partida . ')' : '';
                     $partidas[] = $row->partida . $desc;
                }
            }
        }
        $comprobantes_text = implode(', ', array_unique($comprobantes));
        $proyectos_text = implode(', ', array_unique($proyectos));
        $partidas_text = implode(', ', array_unique($partidas));
        
        $proveedor_nombre = (string) ($registro_pt->nombre_proveedor_1 ?? 'PROVEEDOR');
        $concepto_text = (string) ($registro_pt->concepto ?? 'Servicios registrados en factura');
        $proyectos_text = $proyectos_text !== '' ? $proyectos_text : 'E027QC04182601';
        $partidas_text = $partidas_text !== '' ? $partidas_text : '';
        $clausula_text = (string) ($registro_pt->clausula ?? $registro_pt->no_convenio ?? 'NO APLICA');
    ?>
    <div class="content">
        Por medio del presente, me permito solicitar su apoyo para que se realice el trámite de Pago a Tercero del
        folio <strong><?= esc($folio) ?></strong> por la cantidad de <strong>$<?= number_format(abs((float) $montoXml), 2) ?> (<?= esc($importe_letra) ?>)</strong>,
        de comprobante(s) fiscal(es) No. <strong><?= esc($folioXml) ?></strong> por concepto de <strong>Consumo de alimentos durante la edición 54 del FIC del 17 de Sep al 19 Oct. 2026</strong> al proveedor <strong><?= esc($razon_social) ?></strong>.
    </div>

    <!-- Content Body 2 -->
    <div class="content">
        Lo anterior con cargo al proyecto(s) <strong>E027QC04182601</strong> a la partida presupuestal <strong>2210</strong>
    </div>

    <!-- Content Body 3 - Legal -->
    <div class="content">
        Hago de su conocimiento que de acuerdo a lo que establece la cláusula <strong>PRIMERA</strong> de instrumento jurídico <strong><?= esc($no_contrato) ?></strong> recibí
        el producto, atendiendo lo que establece el marco normativo aplicable. El producto recibido se nos ha
        entregado a entera satisfacción en tiempo y forma, quedando bajo mi responsabilidad el uso y/o
        distribución, así como el resguardo y custodia de los expedientes originales y entregables correspondientes.
    </div>

    <!-- Content Body 4 -->
    <div class="content">
        Daremos seguimiento al instrumento jurídico, con la finalidad de asegurar y garantizar que los recursos
        erogados cumplan con lo establecido, así como dar continuidad a las acciones del mencionado instrumento.
    </div>

    <!-- Content Body 5 -->
    <div class="content">
        La adquisición del producto se realizó garantizando las mejores condiciones en cuanto a precio, calidad,
        financiamiento, oportunidad y demás elementos, en términos de la normatividad del gasto público.
    </div>

    <!-- Closing -->
    <div class="content">
        Sin otro particular por el momento, aprovecho la ocasión para enviarle un cordial saludo.
    </div>

    <!-- Signature -->
    <?php
        $responsable = (string) ($registro_pt->nombre_responsable ?? 'MTRO. DAVID AYALA SAUCEDO');
        $cargo_responsable = (string) ($registro_pt->cargo_responsable ?? 'SUBSECRETARIO DE IDENTIDAD Y DESARROLLO TURÍSTICO');
    ?>
    <div class="signature-section">
        ATENTAMENTE
        <br><br><br>
        <div class="signature-line"></div>
        <?= $responsable ?><br>
        <span style="font-size: 9pt; font-weight: normal;"><?= $cargo_responsable ?></span>
    </div>

</body>
</html>
