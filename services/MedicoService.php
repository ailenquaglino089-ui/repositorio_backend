<?php
// ============================================================
// services/MedicoService.php - Capa de negocio (lógica de aplicación)
// ============================================================
// Service = Servicio (capa de negocio)
// Es la capa intermedia entre el Controlador y el Repositorio.
// Contiene TODA la lógica de negocio: validaciones, reglas,
// transformaciones de datos, decisiones.
//
// NO debe tener código SQL ni código HTTP.
// Solo llama al repositorio para guardar/obtener datos.
// ============================================================

class MedicoService
{
    // Repositorio que usa para acceder a los datos
    private MedicoRepository $repo;

    /**
     * Constructor: recibe el repositorio por inyección de dependencias
     * 
     * @param MedicoRepository $repo Repositorio de médicos
     */
    public function __construct(MedicoRepository $repo)
    {
        $this->repo = $repo;
    }

    /**
     * Obtiene todos los médicos
     * Pasa directamente la llamada al repositorio (sin lógica adicional)
     * 
     * @return array Lista de médicos
     */
    public function obtenerTodos(): array
    {
        // Delega la consulta al repositorio (capa de persistencia)
        return $this->repo->obtenerTodos();
    }

    /**
     * Obtiene un médico por su ID
     * VALIDA que el médico exista, si no lanza una excepción
     * 
     * @param int $id ID del médico
     * @return array Datos del médico
     * @throws RuntimeException Si el médico no existe (código 404)
     */
    public function obtenerPorId(int $id): array
    {
        $medico = $this->repo->obtenerPorId($id);
        if (!$medico) {
            // RuntimeException = excepción en tiempo de ejecución
            // El código 404 indica "No encontrado"
            throw new \RuntimeException('Médico no encontrado', 404);
        }
        return $medico;
    }

    /**
     * Crea un nuevo médico
     * VALIDA que el nombre sea obligatorio y no esté vacío
     * 
     * @param array $data Datos del médico: nombre, matricula, especialidad
     * @return array El médico recién creado (con su ID)
     * @throws InvalidArgumentException Si el nombre está vacío
     */
    public function crear(array $data): array
    {
        // trim() elimina espacios en blanco al inicio y final de un string
        // ?? null: si no existe la clave 'nombre', usa null
        // empty() verifica si está vacío (null, '', false, 0, [])
        if (empty(trim($data['nombre'] ?? ''))) {
            // InvalidArgumentException = excepción por argumento inválido
            // El código 400 indica "Bad Request" (solicitud incorrecta)
            throw new \InvalidArgumentException('El nombre del médico es obligatorio', 400);
        }

        // Inserta el médico llamando al repositorio
        $id = $this->repo->crear([
            'nombre' => trim($data['nombre']),
            'matricula' => trim($data['matricula'] ?? ''),
            'especialidad' => trim($data['especialidad'] ?? ''),
            'activo' => 1,  // Por defecto el médico se crea ACTIVO
        ]);

        // Devuelve el médico recién creado (con todos sus datos)
        return $this->repo->obtenerPorId($id);
    }

    /**
     * Actualiza un médico existente
     * VALIDA que el médico exista antes de actualizar
     * Solo actualiza los campos que vienen en $data
     * 
     * @param int $id ID del médico
     * @param array $data Campos a actualizar
     * @return array El médico actualizado
     * @throws RuntimeException Si el médico no existe
     * @throws InvalidArgumentException Si no hay datos para actualizar
     */
    public function actualizar(int $id, array $data): array
    {
        // Verifica que el médico exista (si no, lanza excepción)
        $this->obtenerPorId($id);

        // Filtra SOLO los campos permitidos y los limpia
        $limpios = [];

        // isset() verifica si la clave existe y no es null
        if (isset($data['nombre'])) {
            $limpios['nombre'] = trim($data['nombre']);
        }
        if (isset($data['matricula'])) {
            $limpios['matricula'] = trim($data['matricula']);
        }
        if (isset($data['especialidad'])) {
            $limpios['especialidad'] = trim($data['especialidad']);
        }
        if (isset($data['activo'])) {
            // Operador ternario: condición ? valor_si_true : valor_si_false
            // Convierte cualquier valor a 1 o 0 (booleano a entero)
            $limpios['activo'] = $data['activo'] ? 1 : 0;
        }

        // Si después de filtrar no quedó ningún campo, lanza error
        if (empty($limpios)) {
            throw new \InvalidArgumentException('No hay datos para actualizar', 400);
        }

        // Ejecuta la actualización en el repositorio
        $this->repo->actualizar($id, $limpios);

        // Devuelve los datos actualizados del médico
        return $this->repo->obtenerPorId($id);
    }

    /**
     * Elimina un médico
     * VALIDA que el médico exista antes de eliminar
     * 
     * @param int $id ID del médico a eliminar
     * @throws RuntimeException Si el médico no existe
     */
    public function eliminar(int $id): void
    {
        // Verifica que exista (lanza excepción si no)
        $this->obtenerPorId($id);
        // Desvincula las recetas asociadas antes de borrar
        $this->repo->desvincularPrescripciones($id);
        // Elimina el médico
        $this->repo->eliminar($id);
    }
}
