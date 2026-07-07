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
        $meses = array("ENERO","FEBRERO","MARZO","ABRIL","MAYO","JUNIO","JULIO","AGOSTO","SEPTIEMBRE","OCTUBRE","NOVIEMBRE","DICIEMBRE");
        $fecha = strtotime($registro_pt->fecha_tramite);
        $fecha_texto = "SILAO, GTO. " . date("d", strtotime($registro_pt->fecha_tramite)) . " DE " . $meses[date("n", strtotime($registro_pt->fecha_tramite))-1] . " DEL " . date("Y", strtotime($registro_pt->fecha_tramite));
        
        $folio = isset($registro_pt->no_consecutivo) ? $registro_pt->no_consecutivo : 'SIN FOLIO';
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
        $importe_total = isset($registro_pt->importe_total_num) ? $registro_pt->importe_total_num : '0.00';
        $importe_letra = isset($registro_pt->importe_letra) ? $registro_pt->importe_letra : '';
        
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
        
        $proveedor_nombre = isset($registro_pt->nombre_proveedor_1) ? $registro_pt->nombre_proveedor_1 : 'PROVEEDOR';
        // Note: Concepto is missing in input form, check if 'contrato_convenio' is used or leave placeholder
        $concepto_text = isset($registro_pt->concepto) ? $registro_pt->concepto : '[CONCEPTO PENDIENTE]'; 
    ?>
    <div class="content">
        Por medio del presente, me permito solicitar su apoyo para que se realice el trámite de Pago a Tercero pago a terceros de
        folio <strong><?= $folio ?></strong> por la cantidad de <strong>$<?= $importe_total ?> (<?= $importe_letra ?>)</strong>,
        de comprobante(s) fiscal(es) No. <strong><?= $comprobantes_text ?></strong> por concepto de <strong><?= $concepto_text ?></strong> al proveedor <strong><?= $proveedor_nombre ?></strong>.
    </div>

    <!-- Content Body 2 -->
    <div class="content">
        Lo anterior con cargo al proyecto(s) <strong><?= $proyectos_text ?></strong> a las partida(s) presupuestal(es) <strong><?= $partidas_text ?></strong>
    </div>

    <!-- Content Body 3 - Legal -->
    <div class="content">
        Hago de su conocimiento que de acuerdo a lo que establece la cláusula <strong><?= ($registro_pt->clausula)?$registro_pt->clausula:'NO APLICA' ?></strong> de instrumento jurídico <strong><?= (!empty($registro_pt->no_convenio))?$registro_pt->no_convenio:'NO APLICA' ?></strong> recibí
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
        $responsable = isset($registro_pt->nombre_responsable_2) ? $registro_pt->nombre_responsable_2 : 'MARCO ANTONIO MORALES GARCÍA';
        // Title hardcoded in image or use variable if available? Image: DIRECTOR/A GENERAL DE INNOVACIÓN E INTELIGENCIA TURÍSTICA
        $cargo_responsable = $registro_pt->cargo_responsable_2; // Hardcoded default based on image
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
