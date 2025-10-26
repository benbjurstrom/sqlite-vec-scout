<?php

namespace BenBjurstrom\SqliteVecScout\Tests\Support\Models;

use BenBjurstrom\SqliteVecScout\Models\Concerns\HasEmbeddings;
use BenBjurstrom\SqliteVecScout\Tests\Support\Factories\ReviewFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class Review extends Model
{
    use HasEmbeddings, HasFactory, Searchable;

    protected static function newFactory(): Factory
    {
        return ReviewFactory::new();
    }

    /**
     * Get the indexable data array for the model.
     *
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        return [
            'summary' => $this->summary,
            'text' => $this->text,
        ];
    }

    /**
     * Get the name of the index associated with the model.
     */
    public function searchableAs(): string
    {
        return 'fake';
    }
}
