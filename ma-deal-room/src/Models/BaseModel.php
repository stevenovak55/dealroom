<?php

namespace MADealRoom\Models;

/**
 * Base Model Class
 *
 * Provides common functionality for all models
 *
 * @package MADealRoom
 * @since 2.1.0
 */
abstract class BaseModel {
    protected $table;
    protected $attributes = [];
    protected $fillable = [];
    protected $casts = [];

    /**
     * Constructor
     */
    public function __construct(array $attributes = []) {
        $this->fill($attributes);
    }

    /**
     * Fill model with attributes
     */
    public function fill(array $attributes): self {
        foreach ($attributes as $key => $value) {
            if (in_array($key, $this->fillable) || $key === 'id' ||
                $key === 'created_at' || $key === 'updated_at') {
                $this->setAttribute($key, $value);
            }
        }
        return $this;
    }

    /**
     * Set an attribute
     */
    public function setAttribute(string $key, $value): void {
        // Apply casting if defined
        if (isset($this->casts[$key])) {
            $value = $this->castAttribute($key, $value);
        }
        $this->attributes[$key] = $value;
    }

    /**
     * Get an attribute
     */
    public function getAttribute(string $key) {
        return $this->attributes[$key] ?? null;
    }

    /**
     * Cast attribute to appropriate type
     */
    protected function castAttribute(string $key, $value) {
        $cast = $this->casts[$key];

        if ($value === null) {
            return null;
        }

        switch ($cast) {
            case 'array':
                return is_string($value) ? json_decode($value, true) : (array)$value;
            case 'boolean':
            case 'bool':
                return (bool)$value;
            case 'integer':
            case 'int':
                return (int)$value;
            case 'float':
            case 'double':
                return (float)$value;
            case 'string':
                return (string)$value;
            case 'date':
            case 'datetime':
                return is_string($value) ? $value : date('Y-m-d H:i:s', $value);
            default:
                return $value;
        }
    }

    /**
     * Convert model to array
     */
    public function toArray(): array {
        $array = [];
        foreach ($this->attributes as $key => $value) {
            // Convert arrays to ensure they're properly formatted
            if (isset($this->casts[$key]) && $this->casts[$key] === 'array' && is_string($value)) {
                $array[$key] = json_decode($value, true) ?: [];
            } else {
                $array[$key] = $value;
            }
        }
        return $array;
    }

    /**
     * Convert model to JSON
     */
    public function toJson(): string {
        return json_encode($this->toArray());
    }

    /**
     * Magic getter
     */
    public function __get(string $key) {
        return $this->getAttribute($key);
    }

    /**
     * Magic setter
     */
    public function __set(string $key, $value): void {
        $this->setAttribute($key, $value);
    }

    /**
     * Check if attribute exists
     */
    public function __isset(string $key): bool {
        return isset($this->attributes[$key]);
    }

    /**
     * Get the table name
     */
    public function getTable(): string {
        global $wpdb;
        return $wpdb->prefix . $this->table;
    }
}