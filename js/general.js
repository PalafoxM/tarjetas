var jgob = window.jgob || {};

jgob.general = (function () {
    return {
        cambiar_foto_perfil: function(){
            $('#mdl_subir_foto_perfil').on('hidden.bs.modal', '.modal', function () {
                //$(this).removeData('bs.modal').find(".modal-content").empty();
              });
              $('#mdl_subir_foto_perfil').modal('show'); 
        }
        
    }
})();