<?php
// ============================================================
// routes.php - Definición de rutas del proyecto
// ============================================================
// Este archivo conecta la URL que el usuario visita con el código
// que debe ejecutarse. Actúa como un "mapa" del sitio.
// ============================================================

// Carga el archivo de arranque (bootstrap) que prepara:
// - Sesión, conexión DB, Router, Repositorio, Servicio, Controlador
// bootstrap.php crea las variables: $router, $medicoRepo, $medicoService, $pdo
require_once __DIR__ . '/core/bootstrap.php';

// ============================================================
// Ruta raíz: redirige al listado de médicos
// ============================================================
$router->get('/', function () {
    $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
    // header('Location: ...') envía una redirección HTTP al navegador
    // El navegador automáticamente va a la nueva URL
    header('Location: ' . $basePath . '/medicos');
    exit;  // Detiene la ejecución para que no siga procesando
});

// ============================================================
// Ruta: Listado de médicos
// ============================================================
$router->get('/medicos', function () use ($medicoService) {
    $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
    $medicos = $medicoService->obtenerTodos();
    include __DIR__ . '/lista_medicos.php';
});

// ============================================================
// Ruta: Listado de prescripciones (recetas)
// ============================================================
$router->get('/prescripciones', function () use ($pdo) {
    // use ($pdo): pasa la conexión a la base de datos
    // lista_prescripciones.php necesita $pdo para hacer consultas SQL
    include __DIR__ . '/lista_prescripciones.php';
});

// ============================================================
// Ruta: Dashboard del paciente (Mis Rx)
// ============================================================
$router->get('/mis-rx', function () {
    // Incluye la página HTML del dashboard
    include __DIR__ . '/mis_rx.php';
});

// ============================================================
// Ruta: Configuración / Ajustes del usuario
// ============================================================
$router->get('/configuracion', function () {
    include __DIR__ . '/configuracion.php';
});

// ============================================================
// Rutas para páginas secundarias (vistas con plantilla base)
// ============================================================
// Cada una usa la misma vista genérica views/base.php
// que detecta qué sección es por la URL y muestra el contenido adecuado

$router->get('/notificaciones', function () {
    $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
    include __DIR__ . '/views/base.php';
});

$router->get('/prestaciones', function () {
    $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
    include __DIR__ . '/views/base.php';
});

$router->get('/cuenta', function () {
    $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
    include __DIR__ . '/views/base.php';
});

$router->get('/faq', function () {
    $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
    include __DIR__ . '/views/base.php';
});

$router->get('/buscador-farmacias', function () {
    $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
    include __DIR__ . '/views/base.php';
});

$router->get('/preguntas-frecuentes', function () {
    $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
    include __DIR__ . '/views/base.php';
});

// ============================================================
// Ruta: Cerrar sesión (logout)
// session_destroy() elimina todos los datos de la sesión actual
// ============================================================
$router->get('/logout', function () {
    session_destroy();  // Destruye la sesión del usuario
    $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
    header('Location: ' . $basePath . '/');  // Redirige al inicio
    exit;
});

// ============================================================
// API REST para Médicos
// REST = Representational State Transfer (arquitectura de APIs)
// CRUD = Create (POST), Read (GET), Update (PUT/PATCH), Delete (DELETE)
// ============================================================

// GET /api/medicos - Obtener todos los médicos (listado JSON)
$router->get('/api/medicos', function () use ($medicoService) {
    $controller = new MedicoController($medicoService);
    $controller->index();
});

// GET /api/medicos/{id} - Obtener un médico por su ID (JSON)
// {id} es un parámetro dinámico: el Router captura el valor de la URL
// Ej: /api/medicos/5 -> $id = 5
$router->get('/api/medicos/{id}', function ($id) use ($medicoService) {
    $controller = new MedicoController($medicoService);
    $controller->show((int) $id);  // (int) convierte a entero por seguridad
});

// POST /api/medicos - Crear un nuevo médico (alta)
// Los datos se envían en el cuerpo de la petición como JSON
$router->post('/api/medicos', function () use ($medicoService) {
    $controller = new MedicoController($medicoService);
    $controller->store();
});

// PUT /api/medicos/{id} - Actualizar un médico completo
$router->put('/api/medicos/{id}', function ($id) use ($medicoService) {
    $controller = new MedicoController($medicoService);
    $controller->update((int) $id);
});

// PATCH /api/medicos/{id} - Actualizar parcialmente un médico
// Se usa específicamente para cambiar el estado activo/inactivo
$router->patch('/api/medicos/{id}', function ($id) use ($medicoService) {
    $controller = new MedicoController($medicoService);
    $controller->update((int) $id);
});

// DELETE /api/medicos/{id} - Eliminar un médico
$router->delete('/api/medicos/{id}', function ($id) use ($medicoService) {
    $controller = new MedicoController($medicoService);
    $controller->destroy((int) $id);
});

// ============================================================
// API REST para Prescripciones
// ============================================================

// DELETE /api/prescripciones/{id} - Eliminar una receta
$router->delete('/api/prescripciones/{id}', function ($id) use ($pdo) {
    try {
        $stmt = $pdo->prepare("DELETE FROM prescripciones WHERE id = ?");
        $stmt->execute([(int)$id]);
        if ($stmt->rowCount() > 0) {
            http_response_code(200);
            header('Content-Type: application/json');
            echo json_encode(['message' => 'Receta eliminada correctamente']);
        } else {
            http_response_code(404);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Receta no encontrada']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Error al eliminar la receta']);
    }
});

// ============================================================
// Manejador de rutas no encontradas (404)
// Redirige al listado de médicos en lugar de mostrar error
// ============================================================
$router->notFound(function () {
    $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
    header('Location: ' . $basePath . '/medicos');
    exit;
});
