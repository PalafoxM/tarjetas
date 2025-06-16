<?php $session = \Config\Services::session(); ?>
<div class="page-wrapper">
    <!-- Page Content-->
    <div class="page-content-tab">
        <div class="container-fluid">
            <!-- Page-Title -->
            <div class="row">
                <div class="col-sm-12">
                    <div class="page-title-box">
                        <div class="float-right">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="javascript:void(0);">Metrica</a></li>
                                <li class="breadcrumb-item"><a href="javascript:void(0);">Analytics</a></li>
                                <li class="breadcrumb-item active">Categoria</li>
                            </ol>
                        </div>
                        <h4 class="page-title">Categoria</h4>
                    </div>
                    <!--end page-title-box-->
                </div>
                <!--end col-->
            </div>
            <!-- end page title end breadcrumb -->
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="mb-12 position-relative" id="">
                                <label for="nombre_curso"
                                    class="form-label"><?= ($session->id_perfil === 1)?'CATEGORIA PADRE':'CATEGORIA' ?></label>
                            </div>
                            <div class="chart-demo">
                                <div id="apex_line1" class="apex-charts">
                                    <div class="card-body">

                                        <div id="categoryTree"></div> <!-- Contenedor del árbol -->
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!--end card-body-->
                    </div>
                    <!--end card-->
                </div>
                <!--end col-->
            </div>
            <!--end col-->
        </div>
    </div>
    <!-- end page content -->
</div>

<!-- jsTree CSS -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jstree/3.3.12/themes/default/style.min.css" />

<!-- jQuery (requerido por jsTree) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- jsTree JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jstree/3.3.12/jstree.min.js"></script>

<!-- Inicializar jsTree -->
<script>
$(document).ready(function() {
    // Convertir el array de PHP a JSON
    $('#categoryTree').jstree({
        'core': {
            'data': {
                "url": "<?= base_url('index.php/Inicio/getCurso') ?>",
                "dataType": "json"
            }
        },
        <?php if($session->id_perfil == 1): ?> "plugins": ["wholerow", "contextmenu",
            "checkbox"
        ], // Agrega el plugin "checkbox"
        <?php endif; ?>
        <?php if($session->id_perfil != 1): ?> "plugins": ["wholerow", "contextmenu"],
        <?php endif; ?>
        <?php if($session->id_perfil == 1): ?> "checkbox": {
            "keep_selected_style": false, // Evita que los nodos seleccionados cambien de estilo
            "three_state": false, // Si es true, permite un estado "indeterminado" para los nodos padres
            "cascade": "down" // Define cómo se propagan las selecciones (puedes usar "up", "down", o "undetermined")
        },
        <?php endif; ?> "contextmenu": {
            "items": function(node) {
                <?php if($session->id_perfil === 1): ?>
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
                <?php if($session->id_perfil  !== 1): ?>
                return {
                    "GoToCourse": {
                        "label": "Solicitar Curso",
                        "action": function() {
                            GoToCourse(node.id);
                        }
                    }
                };
                <?php endif ?>
            }
        }
    });
   
    $('#categoryTree').on("changed.jstree", function(e, data) {
        var selectedNodes = data.instance.get_top_selected();
        var allNodes = data.instance.get_json('#', {
            flat: true
        });
        // Recorrer todos los nodos
        allNodes.forEach(function(node) {
            if (node.parent === "#") {
                let x = selectedNodes.includes(node.id);
                if (x) {
                    activarCategoria(node.id,1);
                } else {
                    activarCategoria(node.id,0);
                }
            }
        });
    });
  
    function activarCategoria(id_categoria,id) {
        $.ajax({
            url: "<?= base_url('index.php/Inicio/activarCategoria') ?>",
            method: "POST",
            data: {
                id_categoria: id_categoria, // Enviar solo el ID del nodo
                id: id // Otros datos que necesites enviar
            },
            success: function(response) {
                console.log(response);
            },
            error: function(error) {
                Swal.fire('Error',
                    'Error en la solicitud: ' + error
                    .responseText, 'error');
            }
        });
    }

    $('#categoryTree2').on("changed.jstree", function(e, data) {
        // Obtener los nodos principales seleccionados
        console.log('ds: ' + data.changed.deselected);
        var topSelectedNodes = data.instance.get_top_selected();

        // Recorrer los nodos principales seleccionados
        topSelectedNodes.forEach(function(nodeId) {
            // Obtener el nodo completo usando el ID
            var node = data.instance.get_node(nodeId);

            // Verificar si el nodo es un nodo principal (su padre es el nodo raíz "#")
            if (node.parent === "#") {
                console.log("Categoría principal seleccionada - ID:", nodeId, "Nombre:", node
                    .text);

                // Mostrar confirmación al usuario
                Swal.fire({
                    title: "!Atención¡",
                    text: "¿Desea guardar la categoría seleccionada?",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#3085d6",
                    cancelButtonColor: "#d33",
                    confirmButtonText: "Guardar"
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Enviar el ID del nodo al backend
                        $.ajax({
                            url: "<?= base_url('index.php/Inicio/activarCategoria') ?>",
                            method: "POST",
                            data: {
                                id_categoria: nodeId, // Enviar solo el ID del nodo
                                id: 1 // Otros datos que necesites enviar
                            },
                            success: function(response) {
                                console.log(response);
                                if (!response.error) {
                                    Swal.fire('Éxito',
                                        'Se guardó correctamente en la base de datos',
                                        'success');
                                    // Refrescar el árbol para reflejar los cambios
                                    $('#categoryTree').jstree(true)
                                        .refresh();
                                } else {
                                    Swal.fire('Error',
                                        'Error al guardar: ' + response
                                        .message, 'error');
                                }
                            },
                            error: function(error) {
                                Swal.fire('Error',
                                    'Error en la solicitud: ' + error
                                    .responseText, 'error');
                            }
                        });
                    }
                });
            } else {
                console.log("Nodo seleccionado no es una categoría principal:", node.text);
            }
        });
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
                            Swal.fire('Éxito', 'Subcategoría creada correctamente',
                                'success');
                            $('#categoryTree').jstree(true)
                                .refresh(); // Refresca el árbol para mostrar la nueva subcategoría
                        } else {
                            Swal.fire('Error', 'Error al crear la subcategoría: ' + response
                                .message, 'error');
                        }
                    },
                    error: function(error) {
                        Swal.fire('Error', 'Error en la solicitud: ' + error.responseText,
                            'error');
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
                            Swal.fire('Error', 'Error al crear el curso: ' + response
                                .message, 'error');
                        }
                    },
                    error: function(error) {
                        Swal.fire('Error', 'Error en la solicitud: ' + error.responseText,
                            'error');
                    }
                });
            }
        });
    }
    // Función para crear un curso dentro de una categoría
    function GoToCourse(categoryId) {
        var baseUrl = "<?= base_url('index.php/Agregar/SolicitarCurso/') ?>";
        window.location.href = baseUrl + categoryId;
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
                            false
                        ); // Si hay error en la respuesta, asumimos que no está vacío
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