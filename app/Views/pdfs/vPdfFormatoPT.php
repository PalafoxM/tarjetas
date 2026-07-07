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
                <td><?= isset($registro_pt->fecha_tramite) ? date('d/m/Y', strtotime($registro_pt->fecha_tramite)) : '' ?></td>
                <td><?= isset($registro_pt->no_consecutivo) ? $registro_pt->no_consecutivo : '' ?></td>
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
            $total_rows = count($rows) > 0 ? count($rows) : 1;
            
            // Dummy row if empty
            if(empty($rows)) $rows = [(object)['encabezado' => '', 'proyecto_clave' => '', 'partida_clave' => '', 'importe' => '']];

            foreach($rows as $index => $row): 
            ?>
            <tr>
                <td><?= isset($row->no_comprobante) ? $row->no_comprobante : '' ?></td>
                <td><?= isset($row->proyecto) ? $row->proyecto : '' ?></td>
                <td><?= isset($row->partida) ? $row->partida : '' ?></td>
                <td>$<?= isset($row->importe) ? ($row->importe) : '0.00' ?></td>
                
                <?php if($index === 0): ?>
                <td rowspan="<?= count($rows) ?>" class="text-left" style="vertical-align: top;">
                    <div class="font-bold">DATOS DEL PROVEEDOR NACIONAL</div>
                    <?php if(!empty($row->nombre_proveedor_1)): ?>
                        <div><?= $row->nombre_proveedor_1 ?></div>
                    <?php endif; ?>
                    
                    <?php // Try to use row specific fields if they were saved in the item (which they weren't in the schema showed) 
                          // Or use variables passed to view if available
                    ?>
                   <!-- Hardcoded example structure as per requirements, could be dynamic -->
                   <div><span class="font-bold">No. PROVEEDOR:</span> <?= isset($registro_pt->no_proveedor) ? $registro_pt->no_proveedor : '' ?></div>
                   <div><span class="font-bold">RFC:</span> <?= isset($registro_pt->rfc_proveedor) ? $registro_pt->rfc_proveedor : '' ?></div>
                   <div><span class="font-bold">NOMBRE:</span> <?= isset($registro_pt->nombre_proveedor_1) ? $registro_pt->nombre_proveedor_1 : '' ?></div>
                   <div><span class="font-bold">NO. CUENTA:</span> <?= isset($registro_pt->no_cuenta) ? $registro_pt->no_cuenta : '' ?></div>
                   <div><span class="font-bold">BANCO:</span> <?= isset($registro_pt->banco) ? $registro_pt->banco : '' ?></div>
                   <div><span class="font-bold">CLABE:</span> <?= isset($registro_pt->clabe) ? $registro_pt->clabe : '' ?></div>
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
                <span class="font-bold">No. CONTRATO y/o CONVENIO:</span> <?= isset($registro_pt->no_convenio) ? $registro_pt->no_convenio : '' ?>
                <br>
                <span class="font-bold">No. RESERVA:</span> <?= isset($registro_pt->no_reserva) ? $registro_pt->no_reserva : '' ?>
            </td>
            <td width="30%" class="font-bold">
                 $<?= isset($registro_pt->importe_total_num) ? $registro_pt->importe_total_num : '' ?>
            </td>
        </tr>
        <tr>
            <td colspan="4" class="text-center font-bold">
                <?= isset($registro_pt->importe_letra) ? $registro_pt->importe_letra : '' ?>
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
                     <strong><?= (isset($registro_pt->nombre_autoriza) && $registro_pt->nombre_autoriza != 'NO APLICA') ? $registro_pt->nombre_autoriza : '' ?></strong><br>
                    <span style="font-size: 8pt;"><?= (isset($registro_pt->cargo_autoriza) && $registro_pt->cargo_autoriza != 'NO APLICA') ? $registro_pt->cargo_autoriza : '' ?></span>
                </td>
                <td style="height: 80px; vertical-align: bottom;">
                    <br><br><br>
                     <strong><?= (isset($registro_pt->nombre_responsable) && $registro_pt->nombre_responsable != 'NO APLICA') ? $registro_pt->nombre_responsable : '' ?></strong><br>
                    <span style="font-size: 8pt;"><?= (isset($registro_pt->cargo_responsable) && $registro_pt->cargo_responsable != 'NO APLICA') ? $registro_pt->cargo_responsable : '' ?></span>
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
                     <strong><?= (isset($registro_pt->nombre_responsable_2) && $registro_pt->nombre_responsable_2 != 'NO APLICA') ? $registro_pt->nombre_responsable_2 : '' ?></strong><br>
                    <span style="font-size: 8pt;"><?= (isset($registro_pt->cargo_responsable_2) && $registro_pt->cargo_responsable_2 != 'NO APLICA') ? $registro_pt->cargo_responsable_2 : '' ?></span>
                 </td>
            </tr>
        </tbody>
    </table>

    <div style="margin-top: 30px; font-size: 8pt; text-align: justify; font-style: italic;">
        El presente documento fue recibido y firmado de conformidad con la Ley sobre el uso de medios electrónicos y firma electrónica para el Estado de Guanajuato y sus Municipios. En virtud de la equivalencia funcional, la firma electrónica certificada se equipara a la firma autógrafa. Se privilegian las políticas de ahorro, racionalidad y austeridad del gasto público, contenidas en el artículo 55, de la Ley para el Ejercicio y Control de los Recursos Públicos para el Estado y los Municipios de Guanajuato; y, Artículos 1 y 15, segundo párrafo de los Lineamientos Generales de Racionalidad, Austeridad y Disciplina Presupuestal de la Administración Pública Estatal para el Ejercicio Fiscal 2026.
    </div>
</body>
</html>
