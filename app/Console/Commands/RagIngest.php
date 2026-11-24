<?php

namespace App\Console\Commands;

use App\Models\Chunk;
use App\Rag\MarkdownChunker;
use App\Rag\PgVectorStore;
use App\Rag\Rag;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

#[Signature('rag:ingest {--fresh : Apaga os chunks e reindexa tudo}')]
#[Description('Lê os markdowns de storage/app/private/knowledge_base, gera embeddings e grava no pgvector')]
class RagIngest extends Command
{
    public function handle(MarkdownChunker $chunker): int
    {
        if ($this->option('fresh')) {
            Chunk::truncate();
        }

        $files = collect(Storage::files('knowledge_base'))->filter(fn ($f) => str_ends_with($f, '.md'));

        if ($files->isEmpty()) {
            $this->components->error('Nenhum .md encontrado em storage/app/private/knowledge_base.');

            return self::FAILURE;
        }

        $embeddings = Rag::embeddings();
        $store = new PgVectorStore;

        foreach ($files as $file) {
            $source = basename($file);
            $documents = $chunker->chunk(Storage::get($file), $source);

            // Só gera embedding para chunks novos ou alterados (ingestão idempotente).
            $existing = Chunk::where('source', $source)->pluck('hash', 'chunk_number');
            $changed = array_values(array_filter(
                $documents,
                fn ($doc) => ($existing[$doc->chunkNumber] ?? null) !== $doc->hash,
            ));

            if ($changed !== []) {
                $this->components->task(
                    "{$source}: embedding de ".count($changed).' chunk(s)',
                    fn () => $store->addDocuments($embeddings->embedDocuments($changed)),
                );
            }

            Chunk::where('source', $source)->where('chunk_number', '>=', count($documents))->delete();

            $this->components->info("{$source}: ".count($documents).' chunks indexados, '.count($changed).' atualizados.');
        }

        return self::SUCCESS;
    }
}
