<?php

namespace BenBjurstrom\SqliteVecScout\Casts;

use BenBjurstrom\SqliteVecScout\Vector;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use RuntimeException;

/**
 * @implements CastsAttributes<Vector|null, string|null>
 */
class VectorCast implements CastsAttributes
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Vector
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof Vector) {
            return $value;
        }

        if (is_string($value)) {
            return Vector::fromJson($value);
        }

        throw new InvalidArgumentException('Unable to cast value to Vector.');
    }

    /**
     * @param  Vector|array<int|float>|string|null  $value
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof Vector) {
            return $value->toJson();
        }

        if (is_array($value)) {
            return (new Vector($value))->toJson();
        }

        if (is_string($value)) {
            // Validate and normalize JSON through Vector
            try {
                return Vector::fromJson($value)->toJson();
            } catch (RuntimeException $e) {
                throw new InvalidArgumentException('Invalid vector JSON: '.$e->getMessage(), previous: $e);
            }
        }

        // @phpstan-ignore-next-line
        throw new InvalidArgumentException('Unable to set vector attribute from given value.');
    }
}
