# Laravel Logics

[![Latest Version on Packagist](https://img.shields.io/packagist/v/axolote-source/laravel-logics.svg?style=flat-square)](https://packagist.org/packages/axolote-source/laravel-logics)
[![Total Downloads](https://img.shields.io/packagist/dt/axolote-source/laravel-logics.svg?style=flat-square)](https://packagist.org/packages/axolote-source/laravel-logics)

Una librería para Laravel que proporciona una forma estructurada de manejar la lógica de negocio mediante clases especializadas (Logics) inyectadas en los controladores. Este enfoque ayuda a mantener los controladores delgados y promueve la reutilización del código y la capacidad de prueba.

## Instalación

Puedes instalar el paquete vía composer:

```bash
composer require axolote-source/laravel-logics
```

## Skills para Agentes de IA

Este paquete incluye un comando para instalar "skills" (reglas y guías) para agentes de IA como Codex, Claude y Junie. Estos skills ayudan a la IA a entender cómo trabajar con los Logics en tu proyecto, proporcionando contexto sobre el ciclo de vida, validaciones y convenciones de nombres.

Para instalar los skills, ejecuta:

```bash
php artisan logics:install-skills
```

### Skills Disponibles
Una vez instalados, los siguientes skills estarán disponibles para tu agente de IA:

- **logics-index**: Instrucciones para listar recursos, paginación y filtrado.
- **logics-store**: Guía para crear nuevos recursos y llenado automático de modelos.
- **logics-show**: Instrucciones para recuperar un solo registro.
- **logics-update**: Guía para actualizar recursos existentes.
- **logics-delete**: Instrucciones para eliminar registros (eliminación física o lógica).
- **logics-endpoint**: Guía completa sobre cómo vincular rutas, controladores, permisos (`isAllow`) y requisitos para crear nuevas funcionalidades, incluyendo pruebas (tests).

### Cómo usarlos
Cuando usas un agente de IA (como Junie, Codex o Claude), este detectará automáticamente estas reglas si las soporta. También puedes pedirle explícitamente al agente:
- "Crea un nuevo endpoint de index para el modelo Product usando el skill logics-index."
- "Implementa un StoreLogic para el modelo Order siguiendo logics-store."
- "Agrega una nueva ruta en `routes/modules/` y un permiso en `config/actions.php` usando la guía logics-endpoint."

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

## Pruebas (Testing)

Para ejecutar las pruebas del paquete, instala las dependencias y ejecuta PHPUnit:

```bash
composer install
vendor/bin/phpunit
```

## Licencia
MIT
