<?php

namespace App\Console\Commands;

use App\Rag\Rag;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

use function Laravel\Prompts\spin;
use function Laravel\Prompts\table;
use function Laravel\Prompts\text;

#[Signature('rag:ask {question? : Pergunta sobre a DUDH} {--k=4 : Quantidade de chunks recuperados} {--debug : Mostra os chunks recuperados}')]
#[Description('Responde perguntas sobre a Declaração Universal dos Direitos Humanos usando RAG')]
class RagAsk extends Command
{
    public function handle(): int
    {
        $question = $this->argument('question') ?? text('Qual a sua pergunta?', required: true);
        $qa = Rag::questionAnswering();

        $answer = spin(
            fn () => $qa->answerQuestion($question, (int) $this->option('k')),
            'Buscando na Declaração...',
        );

        $docs = $qa->getRetrievedDocuments();

        $this->newLine();
        $this->line($answer);
        $this->newLine();

        if ($docs === []) {
            $this->components->warn('Nenhum chunk passou do limite de similaridade.');
        } else {
            $this->components->twoColumnDetail('<fg=gray>Fontes</>', collect($docs)->pluck('metadata.section')->implode(', '));
        }

        if ($this->option('debug') && $docs !== []) {
            table(
                ['Seção', 'Similaridade', 'Trecho'],
                collect($docs)->map(fn ($d) => [
                    $d->metadata['section'],
                    $d->metadata['similarity'],
                    str($d->content)->after("\n")->limit(70)->toString(),
                ])->all(),
            );
        }

        return self::SUCCESS;
    }
}
