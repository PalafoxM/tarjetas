<style>
.contenedor {
    display: grid;
    grid-auto-flow: column;
    gap: 10px;
    /* Espacio entre los botones */
}

.boton {
    padding: 1px 1px;
    background-color: #4CAF50;
    color: white;
    text-align: center;
    border-radius: 50px;
}
</style>
<div class=" mt-3">
    <form id="formAgregarCurso" name="formAgregarCurso">
        <div class="row">
            <!-- seccion izquierdo incio -->
            <div class="col-md-12 ">
                <div class="card">
                    <!--init card -->
                    <div class="card-body">
                        <blockquote class="blockquote">
                            <h3 class="textoNegro"><?= ($perfil === 1)?'Crear nueva categoría':'Categorías' ?></h3>
                        </blockquote>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-12 position-relative" id="">
                                    <label for="nombre_curso" class="form-label"><?= ($perfil === 1)?'NOMBRE DE LA CATEGORIA PADRE':'CATEGORIA PADRE' ?></label>
                                    <?php if($perfil === 1): ?>
                                    <div class="input-group">
                                        <input type="text" oninput="this.value = this.value.toUpperCase();"
                                            class="form-control" id="nombre_curso" name="nombre_curso"
                                            placeholder="NOMBRE DEL CURSO" aria-label="Recipient's username">
                                        <button class="btn btn-dark" type="submit">Guardar</button>
                                    </div>
                                    <?php endif ?>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
                <!--end card -->
            </div>
            <!-- seccion izquierdo fin-->
            <!-- seccion derecha incio -->
        </div>

    </form>

</div>


<div id="categoryTree"></div>
<!-- Modal -->
<div class="modal fade" id="staticBack" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
    aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="labelCurso"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-hidden="true"></button>
            </div> <!-- end modal header -->
            <div class="modal-body">
                <form id="formEditCurso">
                    <div class="mb-3 ">
                        <input type="hidden" class="form-control" id="id_moodle_categoria" name="id_moodle_categoria">
                        <input type="hidden" class="form-control" id="editar" name="editar" value="1">
                    </div>
                    <div class="row g-2">
                        <div class="mb-6 col-md-6">
                            <label for="dsc_categoria" class="form-label">NOMBRE DEL CURSO</label>
                            <input type="text" class="form-control" id="dsc_categoria" name="dsc_categoria">
                        </div>

                        <div class="mb-6 col-md-6">
                            <label for="fec_reg" class="form-label">FECHA REGISTRO</label>
                            <input type="text" class="form-control" readonly id="fec_reg" name="fec_reg">
                        </div>
                    </div>



                    <button type="submit" class="btn btn-primary" id="btnGuardar">Guardar</button>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-warning" data-bs-dismiss="modal">Cerrar</button>
            </div> <!-- end modal footer -->
        </div> <!-- end modal content-->
    </div> <!-- end modal dialog-->
</div> <!-- end modal-->
<!-- jQuery (requerido por jstree) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<!-- jstree CSS y JS -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jstree/3.3.12/themes/default/style.min.css" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/jstree/3.3.12/jstree.min.js"></script>

<link href="<?php echo (base_url('/assets/bootstrap-table-master/dist_/bootstrap-table.min.css'));?>" rel="stylesheet">
<script src="<?php echo base_url('/assets/bootstrap-table-master/dist_/bootstrap-table.min.js');?>"></script>
<script src="<?php echo base_url('/assets/bootstrap-table-master/dist_/tableExport.min.js');?>"></script>
<script src="<?php echo base_url('/assets/bootstrap-table-master/dist_/bootstrap-table-locale-all.min.js');?>">
</script>
<script
    src="<?php echo base_url('/assets/bootstrap-table-master/dist_/extensions/export/bootstrap-table-export.min.js');?>">
</script>
<script>
/* $(document).ready(function() {
    ini.inicio.agregarCategoria();
    //ini.inicio.formEditCurso();
    $('#categoryTree').jstree({
            'core': {
                'data': {
                    "url": "<?= base_url('/index.php/Inicio/getCurso') ?>",
                    "dataType": "json"
                }
            },
            "plugins": ["wholerow", "checkbox"]
        });

        // Manejador de eventos para clics en el árbol
        $('#categoryTree').on("changed.jstree", function (e, data) {
            var selectedNode = data.instance.get_node(data.selected[0]);
            console.log("Nodo seleccionado: ", selectedNode);
            let id = selectedNode.id;
            window.location.href = `${base_url}index.php/Agregar/Evento/${id}`;
        });

}); */
$(document).ready(function() {
    ini.inicio.agregarCategoria();
    ini.inicio.formEditCurso();
    $('#categoryTree').jstree({
        'core': {
            'data': {
                "url": "<?= base_url('index.php/Inicio/getCurso') ?>",
                "dataType": "json"
            }
        },
        "plugins": ["wholerow", "contextmenu"],
        "contextmenu": {
            "items": function(node) {
                <?php if($perfil === 1): ?>
                return {
                    "CreateSubcategory": {
                        "label": "Agregar Subcategoría",
                        "action": function() {
                            addSubcategory(node.id);
                        }
                    },
                    "CreateCourse": {
                        "label": "Crear Curso",
                        "action": function() {
                            createCourse(node.id);
                        }
                    },
                    "GoToCourse": {
                        "label": "Gestionar Curso",
                        "action": function() {
                            GoToCourse(node.id);
                        }
                    }
                };
                <?php endif ?>
                <?php if($perfil !== 1): ?>
                return {
                    "GoToCourse": {
                        "label": "Gestionar Curso",
                        "action": function() {
                            GoToCourse(node.id);
                        }
                    }
                };
                <?php endif ?>
            }
        }
    });

    // Función para agregar una subcategoría usando SweetAlert2
    function addSubcategory(categoryId) {
        Swal.fire({
            title: 'Ingrese el nombre de la nueva subcategoría',
            input: 'text',
            inputLabel: 'Nombre de la subcategoría',
            inputPlaceholder: 'Ej: Subcategoría 1',
            showCancelButton: true,
            confirmButtonText: 'Crear',
            cancelButtonText: 'Cancelar',
            inputValidator: (value) => {
                if (!value) {
                    return '¡El nombre de la subcategoría es obligatorio!';
                }
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const subcategoryName = result.value;
                $.ajax({
                    url: `${base_url}index.php/Agregar/crearSubCategoria`,
                    method: "POST",
                    data: {
                        parent: categoryId,
                        name: subcategoryName
                    },
                    success: function(response) {
                        if (!response.error) {
                            Swal.fire('Éxito', 'Subcategoría creada correctamente', 'success');
                            $('#categoryTree').jstree(true).refresh(); // Refresca el árbol para mostrar la nueva subcategoría
                        } else {
                            Swal.fire('Error', 'Error al crear la subcategoría: ' + response.message, 'error');
                        }
                    },
                    error: function(error) {
                        Swal.fire('Error', 'Error en la solicitud: ' + error.responseText, 'error');
                    }
                });
            }
        });
    }

    // Función para crear un curso dentro de una categoría
    function createCourse(categoryId) {
        Swal.fire({
            title: 'Ingrese el nombre del curso',
            input: 'text',
            inputLabel: 'Nombre del curso',
            inputPlaceholder: 'Ej: Curso 1',
            showCancelButton: true,
            confirmButtonText: 'Crear',
            cancelButtonText: 'Cancelar',
            inputValidator: (value) => {
                if (!value) {
                    return '¡El nombre del curso es obligatorio!';
                }
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const courseName = result.value;
                $.ajax({
                    url: `${base_url}index.php/Agregar/createCourse`,
                    method: "POST",
                    data: {
                        category: categoryId,
                        fullname: courseName,
                        shortname: courseName.toLowerCase().replace(/\s+/g, '_')
                    },
                    success: function(response) {
                        if (!response.error) {
                            Swal.fire('Éxito', 'Curso creado correctamente', 'success');
                        } else {
                            Swal.fire('Error', 'Error al crear el curso: ' + response.message, 'error');
                        }
                    },
                    error: function(error) {
                        Swal.fire('Error', 'Error en la solicitud: ' + error.responseText, 'error');
                    }
                });
            }
        });
    }
    // Función para crear un curso dentro de una categoría
    function GoToCourse(categoryId) {
        window.location.href = `${base_url}index.php/Agregar/Evento/${categoryId}`;
    }
    function validarCurso(categoryId) {
        return new Promise((resolve, reject) => {
            $.ajax({
                url: `${base_url}index.php/Agregar/validarCurso`,
                method: "POST",
                data: {
                    categoryId: categoryId
                },
                success: function(response) {
                    console.log(response);

                    if (!response.error) {
                        const data = response.data;

                        // Verifica si `data` es un array vacío o un objeto sin propiedades
                        const isEmpty =
                            (Array.isArray(data) && data.length === 0) ||
                            (typeof data === 'object' && !Array.isArray(data) && Object
                                .keys(data).length === 0);

                        if (isEmpty) {
                            console.log("El array u objeto está vacío");
                            resolve(true); // Devuelve `true` si está vacío
                        } else {
                            console.log("El array u objeto no está vacío");
                            resolve(false); // Devuelve `false` si tiene contenido
                        }
                    } else {
                        console.log('Error al obtener los datos:', response.message);
                        resolve(
                        false); // Si hay error en la respuesta, asumimos que no está vacío
                    }
                },
                error: function(error) {
                    Swal.fire('Error', 'Error en la solicitud: ' + error.responseText,
                        'error');
                    reject(error);
                }
            });
        });
    }
});

</script>