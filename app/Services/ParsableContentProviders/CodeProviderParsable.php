<?php

declare(strict_types=1);

namespace App\Services\ParsableContentProviders;

use App\Contracts\ParsableContentProvider;
use Highlight\Highlighter;

final readonly class CodeProviderParsable implements ParsableContentProvider
{
    /**
     * {@inheritDoc}
     */
    public function parse(string $content): string
    {
        return (string) preg_replace_callback(
            '/```(?<language>[a-z]+)?\n(?<code>.*?)\n```/s',
            function (array $matches): string {
                $code = $matches['code'];
                $language = empty($matches['language'])
                    ? 'plaintext'
                    : $matches['language'];

                $highlighter = new Highlighter();

                $code = htmlspecialchars_decode($code, ENT_QUOTES);

                $highlighted = $highlighter->highlight($language, $code);

                $highlightedCode = $highlighted->value;
                $highlightedLanguage = $highlighted->language;

                return '<pre><code class="p-4 rounded-lg hljs '.$highlightedLanguage.' text-xs" style="background-color: #23262E">'.$highlightedCode.'</code></pre>';
            },
            $content
        );
    }
}