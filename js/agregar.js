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
        
    }
})();