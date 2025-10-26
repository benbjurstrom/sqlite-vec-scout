<?php

namespace BenBjurstrom\SqliteVecScout;

use JsonException;
use RuntimeException;

class Vector implements \JsonSerializable
{
    /**
     * @var array<int, float>
     */
    protected array $values;

    /**
     * @param  array<int, float|int|string>  $values
     */
    public function __construct(array $values)
    {
        if ($values === []) {
            throw new RuntimeException('Vectors must contain at least one value.');
        }

        $this->values = array_map(static fn ($value): float => (float) $value, array_values($values));
    }

    /**
     * @return array<int, float>
     */
    public function toArray(): array
    {
        return $this->values;
    }

    public function count(): int
    {
        return count($this->values);
    }

    /**
     * Serialize vector to array for JSON encoding
     *
     * @return array<int, float>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function toJson(int $flags = 0): string
    {
        try {
            return json_encode($this->values, $flags | JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('Unable to encode vector to JSON: '.$exception->getMessage(), previous: $exception);
        }
    }

    /**
     * Create vector from JSON string
     *
     * @param string $json JSON array string like "[1.0, 2.0, 3.0]"
     * @throws RuntimeException if JSON is invalid
     */
    public static function fromJson(string $json): self
    {
        try {
            $values = json_decode($json, true, flags: JSON_THROW_ON_ERROR);

            if (!is_array($values)) {
                throw new RuntimeException('JSON must represent an array of numbers.');
            }

            return new self($values);
        } catch (JsonException $exception) {
            throw new RuntimeException('Invalid vector JSON: ' . $exception->getMessage(), previous: $exception);
        }
    }

    public function __toString(): string
    {
        return $this->toJson();
    }
}
