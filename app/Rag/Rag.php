<?php

namespace App\Rag;

use LLPhant\Chat\OpenAIChat;
use LLPhant\Embeddings\EmbeddingGenerator\OpenAI\OpenAI3SmallEmbeddingGenerator;
use LLPhant\OpenAIConfig;
use LLPhant\Query\SemanticSearch\QuestionAnswering;

/**
 * Monta as peças do LLPhant a partir da configuração do Laravel.
 */
class Rag
{
    public static function config(): OpenAIConfig
    {
        $config = new OpenAIConfig(
            apiKey: config('services.openai.key'),
            model: config('services.openai.model'),
        );

        if (! $config->apiKey) {
            throw new \RuntimeException('Defina OPENAI_API_KEY no .env.');
        }

        return $config;
    }

    public static function embeddings(): OpenAI3SmallEmbeddingGenerator
    {
        return new OpenAI3SmallEmbeddingGenerator(self::config());
    }

    public static function questionAnswering(): QuestionAnswering
    {
        $qa = new QuestionAnswering(
            new PgVectorStore,
            self::embeddings(),
            new OpenAIChat(self::config()),
        );

        $qa->systemMessageTemplate = <<<'PROMPT'
        Você é um assistente que responde perguntas sobre a Declaração Universal dos Direitos Humanos.
        Responda em português, usando APENAS o contexto abaixo.
        Cite os artigos usados entre colchetes, por exemplo: [Artigo XIV].
        Se o contexto não contiver a resposta, diga: "Não encontrei essa informação na Declaração."

        Contexto:
        {context}
        PROMPT;

        return $qa;
    }
}
