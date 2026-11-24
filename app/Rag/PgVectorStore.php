<?php

namespace App\Rag;

use App\Models\Chunk;
use LLPhant\Embeddings\Document;
use LLPhant\Embeddings\VectorStores\VectorStoreBase;

/**
 * Vector store do LLPhant apoiado no Eloquent + pgvector nativo do Laravel,
 * evitando a dependência do Doctrine exigida pelo DoctrineVectorStore.
 */
class PgVectorStore extends VectorStoreBase
{
    public function __construct(private readonly float $minSimilarity = 0.3) {}

    public function addDocument(Document $document): void
    {
        Chunk::updateOrCreate(
            ['source' => $document->sourceName, 'chunk_number' => $document->chunkNumber],
            [
                'section' => $document->metadata['section'] ?? '',
                'content' => $document->content,
                'hash' => $document->hash,
                'embedding' => $document->embedding,
            ],
        );
    }

    public function addDocuments(array $documents): void
    {
        foreach ($documents as $document) {
            $this->addDocument($document);
        }
    }

    public function similaritySearch(array $embedding, int $k = 4, array $additionalArguments = []): iterable
    {
        return Chunk::query()
            ->select(['id', 'source', 'section', 'chunk_number', 'content', 'hash'])
            ->selectVectorDistance('embedding', $embedding, 'distance')
            ->whereVectorSimilarTo('embedding', $embedding, $this->minSimilarity)
            ->limit($k)
            ->get()
            ->map(function (Chunk $chunk): Document {
                $document = new Document;
                $document->id = $chunk->id;
                $document->content = $chunk->content;
                $document->sourceType = 'markdown';
                $document->sourceName = $chunk->source;
                $document->chunkNumber = $chunk->chunk_number;
                $document->hash = $chunk->hash;
                $document->metadata = [
                    'section' => $chunk->section,
                    'similarity' => round(1 - (float) $chunk->distance, 4),
                ];

                return $document;
            })
            ->all();
    }
}
