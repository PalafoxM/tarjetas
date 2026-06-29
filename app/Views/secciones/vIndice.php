<?php

?>
<hr>

<div class="row">
    <div class="col-12">
        <h2>Índice</h2>
    </div>
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <table id="indice"></table>
            </div>
        </div>
    </div>
</div>

<script>
var $table = $('#indice');

function construyeIndice(elemento, id_junta, id_fichero_padre) {
    $.ajax({
        type:       "post",
        datatype:   "json",
        cache:      false,
        url: "<?=base_url("/index.php/Junta/getIndice")?>",
        data: {
            id_junta: id_junta, 
            id_fichero_padre : id_fichero_padre
        },
        success: function( data, textStatus, jqXHR ) {
            creaTabla(elemento, data, id_junta, id_fichero_padre);
        }
    });
}

function creaTabla(elemento, data, id_junta, id_fichero_padre) {
    var columns = [
        {
            field: 'es_directorio',
            title: 'Tipo',
            sortable: false,
            formatter: 'jgob.junta.formatterIndiceTipo'
        },
        {
            field: 'dsc_fichero',
            title: 'Documento',
            sortable: false,
            formatter: 'jgob.junta.formatterIndiceDscFichero'
        },
        {
            field: 'id_fichero',
            title: 'Acciones',
            sortable: false,
            formatter: 'jgob.junta.formatterIndiceAcciones'
        },
    ];
    elemento.bootstrapTable({
        locale: 'es_MX',
        columns: columns,
        data: data,
        detailView: true,
        detailFilter: "detailFilter",
        //detailFormatter: "detailFormatter",
        detailViewIcon: true,
//        detailViewByClick: true,
        onExpandRow: function (index, row, $detail) {
                construyeIndice($detail, id_junta, row.id_fichero);
            }
        });
}

function detailFormatter(index, row) {
    return 'Holaaa';
}

function detailFilter(index, row) {
    if(row.es_directorio == 1) {
        return true;
    } else {
        return false;
    }
}

$(function() {
    construyeIndice($table, 1, null); // TODO: recibir este parametro por get
})
</script>