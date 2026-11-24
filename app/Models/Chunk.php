<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Chunk extends Model
{
    protected $fillable = [
        'source',
        'section',
        'chunk_number',
        'content',
        'hash',
        'embedding',
    ];

    protected function casts(): array
    {
        return [
            // pgvector aceita e devolve o formato "[0.1,0.2,...]", que é JSON válido.
            'embedding' => 'array',
        ];
    }
}
