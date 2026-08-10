<?php
// ============================================================
// core/bootstrap.php - Archivo de arranque (bootstrap) del sistema
// ============================================================
// bootstrap significa "arranque" o "inicialización".
// Es el primer archivo que se ejecuta, prepara todo lo necesario
// para que el resto de la aplicación funcione.
// ============================================================

// Inicia la sesión del usuario
// session_start() permite usar $_SESSION para guardar datos
// entre distintas páginas (ej: usuario logueado, preferencias)
session_start();

// Carga la clase Router (enrutador de URLs)
require_once __DIR__ . '/Router.php';

// Carga la conexión a la base de datos
// Esto ejecuta db.php que crea la variable $pdo (objeto PDO)
require_once __DIR__ . '/../db.php';

// Carga la capa de persistencia (repositorio de médicos)
require_once __DIR__ . '/../persistence/MedicoRepository.php';

// Carga la capa de negocio (servicio de médicos)
require_once __DIR__ . '/../services/MedicoService.php';

// Carga el controlador de médicos (maneja HTTP)
require_once __DIR__ . '/../controllers/MedicoController.php';

// Calcula la ruta base del proyecto
// dirname($_SERVER['SCRIPT_NAME']) devuelve algo como "/Organizacion_Modulos"
// rtrim() saca la barra del final si la hay
// Ej: si la URL es http://localhost/Organizacion_Modulos/index.php,
// entonces SCRIPT_NAME es "/Organizacion_Modulos/index.php"
// y basePath queda como "/Organizacion_Modulos"
$basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');

// Crea el enrutador (Router) pasándole la ruta base
// El Router usará esto para recortar el prefijo de las URLs
// y trabajar solo con rutas relativas como /medicos, /api/medicos, etc.
$router = new Router($basePath);

// Crea el repositorio de médicos, inyectándole la conexión PDO
// Inyección de dependencias: en lugar de crear la conexión adentro,
// se la pasamos desde afuera. Esto hace el código más testeable y flexible.
$medicoRepo = new MedicoRepository($pdo);

// Crea el servicio de médicos, inyectándole el repositorio
// El servicio contiene la lógica de negocio (validaciones, reglas)
$medicoService = new MedicoService($medicoRepo);

// Las variables $router, $medicoRepo, $medicoService y $pdo
// quedan disponibles en routes.php que es quien incluye este archivo
