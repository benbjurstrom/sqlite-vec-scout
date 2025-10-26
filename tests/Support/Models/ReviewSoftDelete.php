<?php

namespace BenBjurstrom\SqliteVecScout\Tests\Support\Models;

use BenBjurstrom\SqliteVecScout\Tests\Support\Factories\ReviewSoftDeleteFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReviewSoftDelete extends Review
{
    use HasFactory, SoftDeletes;

    public $table = 'reviews';

    protected static function newFactory(): Factory
    {
        return ReviewSoftDeleteFactory::new();
    }
}
