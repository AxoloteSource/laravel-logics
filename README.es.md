# Laravel Logics

Una librería para Laravel que proporciona una forma estructurada de manejar la lógica de negocio mediante clases especializadas (Logics) inyectadas en los controladores. Este enfoque ayuda a mantener los controladores delgados y promueve la reutilización del código y la capacidad de prueba.

## Instalación

Puedes instalar el paquete vía composer:

```bash
composer require axolote-source/laravel-logics
```

## Concepto Principal: Logics

Los Logics son clases que encapsulan una acción específica sobre un modelo (o varios modelos). Siguen un flujo de ejecución predefinido:

1.  **`before()`**: Lógica de validación o preprocesamiento. Devuelve `bool`.
2.  **`action()`**: Ejecución de la lógica de negocio principal. Devuelve `self`.
3.  **`after()`**: Lógica de posprocesamiento (por ejemplo, registros, notificaciones). Devuelve `bool`.

### Clase Logic Base

La clase `Logic` es la base para todos los demás logics. Proporciona funcionalidad común como el manejo de errores y el formateo de respuestas. Úsala cuando tu acción no encaje en las operaciones CRUD estándar.

```php
use AxoloteSource\Logics\Logics\Logic;

class CustomActionLogic extends Logic {
    protected function before(): bool {
        // Lógica de validación
        return true;
    }

    protected function action(): self {
        // Lógica de negocio aquí
        return $this;
    }

    protected function after(): bool {
        // Lógica pos-acción
        return true;
    }
}
```

## Logics Especializados

El paquete incluye clases abstractas preconstruidas para operaciones CRUD estándar:

### 1. IndexLogic
Se utiliza para listar recursos. Incluye soporte integrado para:
-   **Paginación**: Manejo automático de `limit` y `page`.
-   **Filtrado**: Sistema flexible para aplicar filtros a la consulta.
-   **Búsqueda**: Funcionalidad de búsqueda básica en una columna especificada.

### 2. StoreLogic
Se utiliza para crear nuevos recursos. Maneja:
-   Mapeo de datos desde `input` al `model`.
-   Guardado automático del modelo.

### 3. ShowLogic
Se utiliza para recuperar un solo recurso por su ID o un atributo específico.

### 4. UpdateLogic
Se utiliza para actualizar un recurso existente. Similar a `StoreLogic`, pero trabaja sobre una instancia de modelo existente.

### 5. DeleteLogic
Se utiliza para eliminar un recurso. Puede manejar eliminaciones permanentes y lógicas (soft deletes).

## Flow: Ejecución de Logics Automatizada

El subpaquete `Flow` permite una forma más automatizada de manejar las operaciones CRUD mapeando rutas a modelos y recursos de forma dinámica.

Utiliza traits como `FlowLogic` para:
-   Validar si un modelo está permitido para la operación actual.
-   Transformar automáticamente el resultado utilizando el Eloquent Resource mapeado.
-   Manejar permisos basados en las acciones del usuario.

Componentes de ejemplo de Flow:
-   `FlowIndexLogicBase`: Base para listado automatizado.
-   `FlowStoreLogicBase`: Base para creación automatizada.
-   `FlowShowLogicBase`: Base para recuperación automatizada de un solo recurso.
-   `FlowUpdateLogicBase`: Base para actualización automatizada.
-   `FlowDeleteLogicBase`: Base para eliminación automatizada.

## Beneficios
-   **Código Estructurado**: Pasos de ejecución claramente definidos (`before`, `action`, `after`).
-   **Consistencia**: Forma estandarizada de manejar CRUD en toda la aplicación.
-   **Reutilización**: La lógica central está desacoplada de los controladores.
-   **Extensibilidad**: Fácil de extender las clases base para agregar comportamientos personalizados.

## Licencia
MIT
