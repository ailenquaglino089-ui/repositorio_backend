<?php
// ============================================================
// controllers/MedicoController.php - Capa de controlador HTTP
// ============================================================
// Controller = Controlador
// Es la capa que maneja las peticiones HTTP (request) y genera
// respuestas HTTP (response).
//
// Responsabilidades:
// - Leer datos de la petición (JSON del body, parámetros de URL)
// - Llamar al Servicio (capa de negocio)
// - Devolver una respuesta JSON con el código HTTP adecuado
//
// NO debe contener lógica de negocio ni consultas SQL.
// ============================================================

class MedicoController
{
    // Servicio que contiene la lógica de negocio
    private MedicoService $service;

    /**
     * Constructor: recibe el servicio por inyección de dependencias
     * 
     * @param MedicoService $service Servicio de médicos
     */
    public function __construct(MedicoService $service)
    {
        $this->service = $service;
    }

    /**
     * GET /api/medicos - Listar todos los médicos
     * Devuelve un array JSON con todos los médicos
     */
    public function index(): void
    {
        $medicos = $this->service->obtenerTodos();
        $this->jsonResponse($medicos);  // 200 OK por defecto
    }

    /**
     * GET /api/medicos/{id} - Obtener un médico por ID
     * Devuelve el médico como JSON, o error 404 si no existe
     * 
     * @param int $id ID del médico
     */
    public function show(int $id): void
    {
        try {
            $medico = $this->service->obtenerPorId($id);
            $this->jsonResponse($medico);
        } catch (\RuntimeException $e) {
            // RuntimeException con código 404 -> médico no encontrado
            $this->jsonResponse(['error' => $e->getMessage()], 404);
        }
    }

    /**
     * POST /api/medicos - Crear un nuevo médico (alta)
     * Lee los datos del cuerpo de la petición (formato JSON)
     * Devuelve el médico creado con código 201 (Created)
     */
    public function store(): void
    {
        try {
            // file_get_contents('php://input') lee el cuerpo crudo de la petición HTTP
            // php://input es un flujo de solo lectura que contiene el body
            // json_decode() convierte el JSON string a un array PHP
            // true = devolver como array asociativo (no como objeto)
            $data = json_decode(file_get_contents('php://input'), true);

            $medico = $this->service->crear($data ?? []);
            // 201 = Created (recurso creado exitosamente)
            $this->jsonResponse($medico, 201);
        } catch (\InvalidArgumentException $e) {
            // InvalidArgumentException con código 400 -> datos inválidos
            $this->jsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * PUT/PATCH /api/medicos/{id} - Actualizar un médico
     * PUT = reemplazar todos los campos
     * PATCH = actualizar solo algunos campos
     * En este caso ambos se manejan igual (actualización parcial)
     * 
     * @param int $id ID del médico
     */
    public function update(int $id): void
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $medico = $this->service->actualizar($id, $data ?? []);
            $this->jsonResponse($medico);
        } catch (\RuntimeException $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 404);
        } catch (\InvalidArgumentException $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * DELETE /api/medicos/{id} - Eliminar un médico
     * 
     * @param int $id ID del médico
     */
    public function destroy(int $id): void
    {
        try {
            $this->service->eliminar($id);
            $this->jsonResponse(['mensaje' => 'Médico eliminado correctamente']);
        } catch (\RuntimeException $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 404);
        } catch (\Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Envía una respuesta JSON al cliente
     * 
     * @param mixed $data Datos a convertir a JSON
     * @param int $status Código HTTP (200, 201, 400, 404, etc.)
     * 
     * Códigos HTTP comunes:
     * 200 = OK (éxito)
     * 201 = Created (creado exitosamente)
     * 400 = Bad Request (solicitud incorrecta)
     * 404 = Not Found (no encontrado)
     * 500 = Internal Server Error (error interno)
     */
    private function jsonResponse($data, int $status = 200): void
    {
        // Establece el código de respuesta HTTP
        http_response_code($status);

        // Establece el header Content-Type como application/json
        // Esto le dice al navegador que la respuesta es JSON, no HTML
        header('Content-Type: application/json');

        // Convierte el array PHP a JSON y lo imprime
        // JSON_UNESCAPED_UNICODE: no escapa caracteres Unicode (tildes, ñ, etc.)
        // Ej: "Médico" en lugar de "M\u00e9dico"
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }
}
