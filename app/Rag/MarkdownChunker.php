<?php

namespace App\Rag;

use LLPhant\Embeddings\Document;

/**
 * Divide um markdown em um chunk por seção "## ".
 *
 * Na DUDH cada artigo é uma seção, então cada chunk é um artigo inteiro
 * e o título ("Artigo XIV") vira metadado para citar a fonte na resposta.
 */
class MarkdownChunker
{
    /**
     * @return Document[]
     */
    public function chunk(string $markdown, string $sourceName): array
    {
        $sections = preg_split('/^(?=## )/m', $markdown);
        $documents = [];

        foreach ($sections as $section) {
            $section = trim($section);

            if (! str_starts_with($section, '## ')) {
                continue; // título do documento (# ...) e metadados de fonte
            }

            $heading = trim(strtok($section, "\n"), "# \t");
            $body = trim(substr($section, strlen(strtok($section, "\n"))));
            $body = trim(preg_replace('/\n---\n.*$/s', '', $body)); // remove o rodapé editorial

            $document = new Document;
            $document->sourceType = 'markdown';
            $document->sourceName = $sourceName;
            $document->chunkNumber = count($documents);
            $document->content = "[{$heading}]\n{$body}";
            $document->hash = hash('sha256', $document->content);
            $document->metadata = ['section' => $heading];

            $documents[] = $document;
        }

        return $documents;
    }
}
