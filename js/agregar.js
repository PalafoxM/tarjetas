var st = window.ssa || {};

st.agregar = (function () {
    return {
        sha256: function(str) {
            var buffer = new TextEncoder("utf-8").encode(str);
            return crypto.subtle.digest("SHA-256", buffer).then(function(hash) {
                return Array.prototype.map.call(new Uint8Array(hash), function(x) {
                    return ('00' + x.toString(16)).slice(-2);
                }).join('');
            });
        },
       estado: function(value,row){
            let accion = ``;
            if(row.tiene_hospedaje == 1){
              accion += `<span class="badge bg-success">Sí</span>`;
            }
            if(row.tiene_hospedaje == 2){
              accion += `<span class="badge bg-danger">No</span>`;
            }
            if(row.tiene_hospedaje == ''){
                accion += `<span class="badge bg-danger">Pendiente</span>`;
           }
            return accion;
       },
        acciones: function(value, row) {
            var botones = `
            <div class="btn-group btn-group-sm">
                <button class="btn btn-warning" type="button" title="Editar" onclick="cajeros.editar(${row.id_usuario})">
                    <i class="mdi mdi-account-edit"></i>
                </button>
                <button class="btn btn-primary" type="button" title="Orden de Hospedaje" onclick="st.agregar.verPdf(${row.id_usuario})">
                    <i class="mdi mdi-file-pdf-box"></i>
                </button>
                <button class="btn btn-secondary" type="button" title="Orden de Alimentos" onclick="cajeros.descargarPdf(${row.id_usuario})">
                    <i class="mdi mdi-file-pdf"></i>
                </button>`;

            if (id_perfil == 1) {
                botones += `
                <button class="btn btn-danger" type="button" title="Eliminar" onclick="cajeros.eliminar(${row.id_usuario})">
                    <i class="mdi mdi-account-remove"></i>
                </button>`;
            }

            botones += `
            </div>`;

            return botones;
        },
        verPdf: function(id_usuario) {
         window.open(base_url + "index.php/Usuario/generarPdfHospedaje/" + id_usuario, '_blank');
        },
        
    }
})();