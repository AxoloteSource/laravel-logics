# Laravel Logics

[![Latest Version on Packagist](https://img.shields.io/packagist/v/axolote-source/laravel-logics.svg?style=flat-square)](https://packagist.org/packages/axolote-source/laravel-logics)
[![Total Downloads](https://img.shields.io/packagist/dt/axolote-source/laravel-logics.svg?style=flat-square)](https://packagist.org/packages/axolote-source/laravel-logics)

A library for Laravel that provides a structured way to handle business logic using specialized classes (Logics) injected into controllers. This approach helps keep controllers thin and promotes code reuse and testability.

## Installation

You can install the package via composer:

```bash
composer require axolote-source/laravel-logics
```

## AI Agent Skills

This package includes a command to install "skills" (rules and guides) for AI agents like Codex, Claude, and Junie. These skills help the AI understand how to work with the Logics in your project.

To install the skills, run:

```bash
php artisan logics:install-skills
```

## Core Concept: Logics

Logics are classes that encapsulate a specific action on a model (or multiple models). They follow a predefined execution flow:

1.  **`before()`**: Validation or pre-processing logic. Returns `bool`.
2.  **`action()`**: The main business logic execution. Returns `self`.
3.  **`after()`**: Post-processing logic (e.g., logging, notifications). Returns `bool`.

### Base Logic Class

The `Logic` class is the foundation for all other logics. It provides common functionality like error handling and response formatting. Use it when your action doesn't fit into standard CRUD operations.

```php
use AxoloteSource\Logics\Logics\Logic;

class CustomActionLogic extends Logic {
    protected function before(): bool {
        // Validation logic
        return true;
    }

    protected function action(): self {
        // Business logic here
        return $this;
    }

    protected function after(): bool {
        // Post-action logic
        return true;
    }
}
```

## Specialized Logics

The package includes pre-built abstract classes for standard CRUD operations:

### 1. IndexLogic
Used for listing resources. It includes built-in support for:
-   **Pagination**: Automatic handling of `limit` and `page`.
-   **Filtering**: Flexible system for applying filters to the query.
-   **Search**: Basic search functionality on a specified column.

### 2. StoreLogic
Used for creating new resources. It handles:
-   Data mapping from `input` to the `model`.
-   Automatic saving of the model.

### 3. ShowLogic
Used for retrieving a single resource by its ID or a specific attribute.

### 4. UpdateLogic
Used for updating an existing resource. Similar to `StoreLogic`, but works on an existing model instance.

### 5. DeleteLogic
Used for removing a resource. It can handle both hard and soft deletes.

## Flow: Automated Logic Execution

The `Flow` sub-package allows for a more automated way to handle CRUD operations by mapping routes to models and resources dynamically. 

It uses traits like `FlowLogic` to:
-   Validate if a model is allowed for the current operation.
-   Automatically transform the result using the mapped Eloquent Resource.
-   Handle permissions based on user actions.

Example components of Flow:
-   `FlowIndexLogicBase`: Base for automated listing.
-   `FlowStoreLogicBase`: Base for automated creation.
-   `FlowShowLogicBase`: Base for automated single resource retrieval.
-   `FlowUpdateLogicBase`: Base for automated updating.
-   `FlowDeleteLogicBase`: Base for automated deletion.

## Benefits
-   **Structured Code**: Clearly defined execution steps (`before`, `action`, `after`).
-   **Consistency**: Standardized way of handling CRUD across the application.
-   **Reusability**: Core logic is decoupled from controllers.
-   **Extensibility**: Easily extend base classes to add custom behavior.

## Testing

To run the package tests, install dependencies and execute PHPUnit:

```bash
composer install
vendor/bin/phpunit
```

## License
MIT
