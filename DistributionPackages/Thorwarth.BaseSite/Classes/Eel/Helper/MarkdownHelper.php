<?php

declare(strict_types=1);

namespace Thorwarth\BaseSite\Eel\Helper;

use Neos\Eel\ProtectedContextAwareInterface;

/**
 * Eel-Helper "Markdown" (registriert in Neos.Fusion.defaultContext) für die Markdown-Fassung der
 * Seiten unter <Seiten-URL>.md (https://llmstxt.org, siehe Resources/Private/Fusion/Markdown).
 *
 * Umwandlung von Inhalten:
 * - fromHtml(html): Rich-Text aus dem CKEditor (p, br, strong, em, ul/ol, a, h1–h6, blockquote,
 *   table, …) wird zu Markdown-Blöcken; Links bleiben erhalten (vorher mit Neos.Neos:ConvertUris
 *   absolut auflösen)
 * - inline(html): wie fromHtml, aber einzeilig – für Überschriften und Labels mit Inline-Markup
 * - text(html): reiner Text ohne Markup und ohne Markdown-Escaping (z. B. für Beschreibungen)
 * - escape(text): Klartext (keine HTML-Verarbeitung), Markdown-Sonderzeichen werden maskiert
 *
 * Bausteine für die Renderer – die Texte werden hier als fertiges Inline-Markdown erwartet, also
 * vorher mit inline()/escape() aufbereiten:
 * - heading(level, text), link(label, url), image(alt, url), quote(text)
 * - list(items, ordered), definitionList({Label: Wert}), table(header, rows)
 * - join(blocks): verbindet Blöcke mit Leerzeilen, leere Blöcke werden übersprungen
 */
class MarkdownHelper implements ProtectedContextAwareInterface
{
    /**
     * Platzhalter für <br> innerhalb eines Absatzes; wird in finalize() zu einem harten
     * Zeilenumbruch ("  \n"), zwei oder mehr hintereinander zu einem Absatzwechsel.
     */
    private const HARD_BREAK = "\u{E000}";

    private const BLOCK_ELEMENTS = [
        'p', 'div', 'section', 'article', 'header', 'footer', 'main', 'aside', 'nav', 'figure',
        'figcaption', 'address', 'details', 'summary', 'dl', 'dt', 'dd', 'li', 'center',
    ];

    private const IGNORED_ELEMENTS = [
        'script', 'style', 'noscript', 'template', 'iframe', 'svg', 'video', 'audio', 'object',
        'embed', 'canvas', 'form', 'input', 'button', 'select', 'textarea', 'head', 'title', 'meta', 'link',
    ];

    /**
     * Rich-Text-HTML -> Markdown-Blöcke.
     *
     * @param mixed $html HTML (string) oder null
     * @param int $minHeadingLevel Kleinste erlaubte Überschriftenebene (Seitentitel ist "#", Inhalte beginnen bei "##")
     */
    public function fromHtml(mixed $html, int $minHeadingLevel = 2): string
    {
        $html = $this->stringify($html);
        if (trim($html) === '') {
            return '';
        }
        if (!str_contains($html, '<') && !str_contains($html, '&')) {
            return $this->finalize($this->paragraph($this->escapeInline($this->collapseWhitespace($html))));
        }

        $body = $this->parseHtml($html);
        if ($body === null) {
            return $this->finalize($this->paragraph($this->escapeInline($this->text($html))));
        }

        return $this->finalize($this->renderChildren($body, max(1, min(6, $minHeadingLevel)), false));
    }

    /**
     * Rich-Text-HTML -> einzeiliges Inline-Markdown (Überschriften, Labels, Tabellenzellen).
     */
    public function inline(mixed $html): string
    {
        $markdown = $this->fromHtml($html);
        if ($markdown === '') {
            return '';
        }
        $markdown = preg_replace('/\s*\n+\s*/u', ' ', $markdown) ?? $markdown;
        return trim($markdown);
    }

    /**
     * Reiner Text: Tags entfernt, Entities aufgelöst, Whitespace zusammengefasst – kein Markdown-Escaping.
     */
    public function text(mixed $html): string
    {
        $html = $this->stringify($html);
        if ($html === '') {
            return '';
        }
        $text = str_ireplace(['<br>', '<br/>', '<br />', '</p>', '</li>', '</div>', '</h1>', '</h2>', '</h3>', '</h4>', '</h5>', '</h6>'], ' ', $html);
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return $this->collapseWhitespace($text);
    }

    /**
     * Klartext (keine HTML-Verarbeitung) für Markdown absichern.
     */
    public function escape(mixed $text): string
    {
        $text = $this->collapseWhitespace(html_entity_decode($this->stringify($text), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        if ($text === '') {
            return '';
        }
        return $this->protectLineStart($this->escapeInline($text));
    }

    public function heading(int $level, mixed $text): string
    {
        $text = trim($this->stringify($text));
        if ($text === '') {
            return '';
        }
        $text = preg_replace('/\s*\n+\s*/u', ' ', $text) ?? $text;
        return str_repeat('#', max(1, min(6, $level))) . ' ' . $text;
    }

    public function link(mixed $label, mixed $url, mixed $title = null): string
    {
        $label = trim($this->stringify($label));
        $url = trim($this->stringify($url));
        if ($url === '') {
            return $label;
        }
        if ($label === '') {
            $label = $this->escapeInline($url);
        }
        $titleSuffix = trim($this->stringify($title));
        $titleSuffix = $titleSuffix !== '' ? ' "' . str_replace('"', '\\"', $titleSuffix) . '"' : '';
        return '[' . $label . '](' . $this->formatUrl($url) . $titleSuffix . ')';
    }

    public function image(mixed $alt, mixed $url, mixed $title = null): string
    {
        $url = trim($this->stringify($url));
        if ($url === '') {
            return '';
        }
        $alt = str_replace(['[', ']'], ['(', ')'], $this->collapseWhitespace($this->stringify($alt)));
        $titleSuffix = trim($this->stringify($title));
        $titleSuffix = $titleSuffix !== '' ? ' "' . str_replace('"', '\\"', $titleSuffix) . '"' : '';
        return '![' . $alt . '](' . $this->formatUrl($url) . $titleSuffix . ')';
    }

    /**
     * Mehrzeiliger Text als Zitat ("> …"); leere Eingabe ergibt einen leeren String.
     */
    public function quote(mixed $text): string
    {
        $text = trim($this->stringify($text));
        if ($text === '') {
            return '';
        }
        $lines = explode("\n", $text);
        return implode("\n", array_map(static fn(string $line): string => rtrim('> ' . $line), $lines));
    }

    /**
     * Aufzählung; leere Einträge werden übersprungen, mehrzeilige Einträge eingerückt.
     *
     * @param iterable<mixed> $items
     */
    public function list(iterable $items, bool $ordered = false): string
    {
        $lines = [];
        $index = 1;
        foreach ($items as $item) {
            $item = trim($this->stringify($item));
            if ($item === '') {
                continue;
            }
            $marker = $ordered ? $index . '. ' : '- ';
            $index++;
            $lines[] = $this->indentListItem($marker, $item);
        }
        return implode("\n", $lines);
    }

    /**
     * "- **Label:** Wert"-Liste aus einem Objekt {Label: Wert}; Einträge ohne Wert entfallen.
     *
     * @param iterable<mixed, mixed> $pairs
     */
    public function definitionList(iterable $pairs): string
    {
        $lines = [];
        foreach ($pairs as $label => $value) {
            $value = trim($this->stringify($value));
            $label = trim($this->stringify($label));
            if ($value === '' || $label === '') {
                continue;
            }
            $lines[] = $this->indentListItem('- ', '**' . $label . ':** ' . $value);
        }
        return implode("\n", $lines);
    }

    /**
     * GitHub-Flavored-Markdown-Tabelle.
     *
     * @param iterable<mixed> $header Spaltenüberschriften
     * @param iterable<iterable<mixed>> $rows Zeilen (jeweils Liste der Zellen)
     */
    public function table(iterable $header, iterable $rows): string
    {
        $headerCells = [];
        foreach ($header as $cell) {
            $headerCells[] = $this->tableCell($cell);
        }
        if ($headerCells === []) {
            return '';
        }
        $lines = [];
        foreach ($rows as $row) {
            $cells = [];
            foreach ($row as $cell) {
                $cells[] = $this->tableCell($cell);
            }
            if ($cells === []) {
                continue;
            }
            $cells = array_pad(array_slice($cells, 0, count($headerCells)), count($headerCells), '');
            $lines[] = '| ' . implode(' | ', $cells) . ' |';
        }
        if ($lines === []) {
            return '';
        }
        array_unshift(
            $lines,
            '| ' . implode(' | ', $headerCells) . ' |',
            '|' . str_repeat(' --- |', count($headerCells))
        );
        return implode("\n", $lines);
    }

    /**
     * Verbindet Blöcke mit Leerzeilen; leere Blöcke (null, '', nur Whitespace) werden übersprungen.
     *
     * @param iterable<mixed> $blocks
     */
    public function join(iterable $blocks, string $glue = "\n\n"): string
    {
        $parts = [];
        foreach ($blocks as $block) {
            $block = trim($this->stringify($block));
            if ($block !== '') {
                $parts[] = $block;
            }
        }
        return $this->normalize(implode($glue, $parts));
    }

    /**
     * Mehr als eine Leerzeile wird zusammengefasst, Zeilenenden werden bereinigt.
     */
    public function normalize(mixed $markdown): string
    {
        $markdown = str_replace("\r\n", "\n", $this->stringify($markdown));
        $lines = array_map(static fn(string $line): string => rtrim($line) === '' ? '' : rtrim($line, "\t"), explode("\n", $markdown));
        $markdown = implode("\n", $lines);
        $markdown = preg_replace("/\n{3,}/", "\n\n", $markdown) ?? $markdown;
        return trim($markdown);
    }

    /**
     * Ganzzahliger Euro-Betrag: 12500 -> "12.500 €"; 0/leer -> "".
     */
    public function euro(mixed $value): string
    {
        if ($value === null || $value === '' || $value === false) {
            return '';
        }
        $number = (float)$value;
        if ($number == 0.0) {
            return '';
        }
        $decimals = floor($number) == $number ? 0 : 2;
        return number_format($number, $decimals, ',', '.') . ' €';
    }

    /**
     * Dateigröße in Bytes lesbar: 1536000 -> "1,5 MB".
     */
    public function fileSize(mixed $bytes): string
    {
        $bytes = (float)$bytes;
        if ($bytes <= 0) {
            return '';
        }
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1, ',', '.') . ' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 0, ',', '.') . ' KB';
        }
        return number_format($bytes, 0, ',', '.') . ' Bytes';
    }

    public function allowsCallOfMethod($methodName): bool
    {
        return true;
    }

    // --- HTML -> Markdown ---------------------------------------------------------------------

    private function parseHtml(string $html): ?\DOMElement
    {
        $document = new \DOMDocument('1.0', 'UTF-8');
        $previousErrorHandling = libxml_use_internal_errors(true);
        $loaded = $document->loadHTML(
            '<!DOCTYPE html><html><head><meta charset="utf-8"></head><body>' . $html . '</body></html>',
            LIBXML_NONET | LIBXML_NOWARNING | LIBXML_NOERROR
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previousErrorHandling);
        if ($loaded === false) {
            return null;
        }
        $body = $document->getElementsByTagName('body')->item(0);
        return $body instanceof \DOMElement ? $body : null;
    }

    private function renderChildren(\DOMNode $node, int $minHeadingLevel, bool $preformatted): string
    {
        $output = '';
        foreach ($node->childNodes as $child) {
            $output .= $this->renderNode($child, $minHeadingLevel, $preformatted);
        }
        return $output;
    }

    private function renderNode(\DOMNode $node, int $minHeadingLevel, bool $preformatted): string
    {
        if ($node instanceof \DOMText) {
            $text = $node->nodeValue ?? '';
            if ($preformatted) {
                return $text;
            }
            return $this->escapeInline($this->collapseWhitespace($text, false));
        }
        if (!$node instanceof \DOMElement) {
            return '';
        }

        $tagName = strtolower($node->tagName);
        if (in_array($tagName, self::IGNORED_ELEMENTS, true)) {
            return '';
        }

        switch ($tagName) {
            case 'br':
                return self::HARD_BREAK;
            case 'hr':
                return $this->block('---');
            case 'strong':
            case 'b':
                return $this->wrapInline('**', $node, $minHeadingLevel, $preformatted);
            case 'em':
            case 'i':
            case 'cite':
            case 'dfn':
            case 'var':
                return $this->wrapInline('*', $node, $minHeadingLevel, $preformatted);
            case 's':
            case 'del':
            case 'strike':
                return $this->wrapInline('~~', $node, $minHeadingLevel, $preformatted);
            case 'code':
            case 'kbd':
            case 'samp':
            case 'tt':
                return $this->codeSpan($node->textContent);
            case 'a':
                return $this->renderLink($node, $minHeadingLevel, $preformatted);
            case 'img':
                return $this->image($node->getAttribute('alt'), $node->getAttribute('src'), $node->getAttribute('title'));
            case 'h1':
            case 'h2':
            case 'h3':
            case 'h4':
            case 'h5':
            case 'h6':
                $level = max($minHeadingLevel, (int)substr($tagName, 1));
                $text = $this->inlineOnly($this->renderChildren($node, $minHeadingLevel, false));
                return $text === '' ? '' : $this->block($this->heading($level, $text));
            case 'ul':
            case 'ol':
                return $this->renderList($node, $minHeadingLevel);
            case 'blockquote':
                $content = $this->finalize($this->renderChildren($node, $minHeadingLevel, false));
                return $content === '' ? '' : $this->block($this->quote($content));
            case 'pre':
                $code = rtrim($node->textContent, "\n");
                return trim($code) === '' ? '' : $this->block("```\n" . $code . "\n```");
            case 'table':
                return $this->renderTable($node, $minHeadingLevel);
            default:
                if (in_array($tagName, self::BLOCK_ELEMENTS, true)) {
                    $content = $this->renderChildren($node, $minHeadingLevel, $preformatted);
                    // Beginnt der Block mit Inline-Text (kein verschachtelter Block), den Zeilenanfang absichern,
                    // damit z. B. "# Hinweis" oder "- 5 %" nicht als Überschrift oder Liste gelesen werden
                    $content = ltrim($content, " \t" . self::HARD_BREAK);
                    if ($content !== '' && !str_starts_with($content, "\n")) {
                        $content = $this->protectLineStart($content);
                    }
                    return $this->block($content);
                }
                // span, u, mark, small, sub, sup, font, time, abbr, label, …: nur der Inhalt
                return $this->renderChildren($node, $minHeadingLevel, $preformatted);
        }
    }

    private function wrapInline(string $marks, \DOMElement $node, int $minHeadingLevel, bool $preformatted): string
    {
        $inner = $this->renderChildren($node, $minHeadingLevel, $preformatted);
        $leading = preg_match('/^[\s' . self::HARD_BREAK . ']/u', $inner) === 1 ? ' ' : '';
        $trailing = preg_match('/[\s' . self::HARD_BREAK . ']$/u', $inner) === 1 ? ' ' : '';
        $inner = trim($inner, " \t\n\r\0\x0B" . self::HARD_BREAK);
        if ($inner === '') {
            return $leading !== '' || $trailing !== '' ? ' ' : '';
        }
        if (str_contains($inner, "\n\n")) {
            // Blockinhalt (z. B. <strong><p>…</p></strong>) lässt sich nicht als Inline-Markup auszeichnen
            return $leading . $inner . $trailing;
        }
        return $leading . $marks . $inner . $marks . $trailing;
    }

    private function renderLink(\DOMElement $node, int $minHeadingLevel, bool $preformatted): string
    {
        $inner = $this->renderChildren($node, $minHeadingLevel, $preformatted);
        $leading = preg_match('/^\s/u', $inner) === 1 ? ' ' : '';
        $trailing = preg_match('/\s$/u', $inner) === 1 ? ' ' : '';
        $label = trim(str_replace(self::HARD_BREAK, ' ', $inner));
        $href = trim($node->getAttribute('href'));

        // Nicht auflösbare interne Links (node://, asset://) oder Anker: nur der Text
        if ($href === '' || str_starts_with($href, '#') || preg_match('/^(node|asset):\/\//', $href) === 1) {
            return $leading . $label . $trailing;
        }
        if ($label === '') {
            $label = $this->escapeInline($href);
        }
        return $leading . $this->link($label, $href, $node->getAttribute('title')) . $trailing;
    }

    private function renderList(\DOMElement $list, int $minHeadingLevel): string
    {
        $ordered = strtolower($list->tagName) === 'ol';
        $index = $ordered ? max(1, (int)($list->getAttribute('start') ?: 1)) : 1;
        $items = [];
        foreach ($list->childNodes as $child) {
            if (!$child instanceof \DOMElement) {
                continue;
            }
            $childTag = strtolower($child->tagName);
            if ($childTag === 'ul' || $childTag === 'ol') {
                // Verschachtelte Liste ohne eigenes <li>: an den vorherigen Eintrag anhängen
                $nested = trim($this->finalize($this->renderList($child, $minHeadingLevel)));
                if ($nested !== '' && $items !== []) {
                    $items[count($items) - 1] .= "\n" . $this->indentLines($nested, $ordered ? '   ' : '  ');
                }
                continue;
            }
            if ($childTag !== 'li') {
                continue;
            }
            $content = $this->renderChildren($child, $minHeadingLevel, false);
            // Innerhalb eines Listeneintrags: <br> und Absätze werden zu einfachen Zeilenumbrüchen
            $content = str_replace(self::HARD_BREAK, "\n", $content);
            $content = preg_replace("/[ \t]*\n[ \t]*/", "\n", trim($content)) ?? trim($content);
            $content = preg_replace("/\n{2,}/", "\n", $content) ?? $content;
            if ($content === '') {
                continue;
            }
            $marker = $ordered ? $index . '. ' : '- ';
            $index++;
            $items[] = $this->indentListItem($marker, $content);
        }
        if ($items === []) {
            return '';
        }
        return $this->block(implode("\n", $items));
    }

    private function renderTable(\DOMElement $table, int $minHeadingLevel): string
    {
        $rows = [];
        $headerRow = null;
        foreach ($this->collectTableRows($table) as [$row, $inHead]) {
            $cells = [];
            $allHeaderCells = true;
            foreach ($row->childNodes as $cell) {
                if (!$cell instanceof \DOMElement) {
                    continue;
                }
                $cellTag = strtolower($cell->tagName);
                if ($cellTag !== 'td' && $cellTag !== 'th') {
                    continue;
                }
                $allHeaderCells = $allHeaderCells && $cellTag === 'th';
                $cells[] = $this->inlineOnly($this->renderChildren($cell, $minHeadingLevel, false));
            }
            if ($cells === []) {
                continue;
            }
            if ($headerRow === null && $rows === [] && ($inHead || $allHeaderCells)) {
                $headerRow = $cells;
                continue;
            }
            $rows[] = $cells;
        }
        if ($headerRow === null) {
            if ($rows === []) {
                return '';
            }
            $headerRow = array_shift($rows);
        }
        $columnCount = max(count($headerRow), ...array_map('count', $rows ?: [[]]));
        $headerRow = array_pad($headerRow, $columnCount, '');
        $markdown = $this->table($headerRow, $rows === [] ? [array_fill(0, $columnCount, '')] : $rows);
        return $markdown === '' ? '' : $this->block($markdown);
    }

    /**
     * @return list<array{0: \DOMElement, 1: bool}>
     */
    private function collectTableRows(\DOMElement $table): array
    {
        $rows = [];
        foreach ($table->childNodes as $child) {
            if (!$child instanceof \DOMElement) {
                continue;
            }
            $tag = strtolower($child->tagName);
            if ($tag === 'tr') {
                $rows[] = [$child, false];
            } elseif ($tag === 'thead' || $tag === 'tbody' || $tag === 'tfoot') {
                foreach ($child->childNodes as $row) {
                    if ($row instanceof \DOMElement && strtolower($row->tagName) === 'tr') {
                        $rows[] = [$row, $tag === 'thead'];
                    }
                }
            }
        }
        return $rows;
    }

    // --- Bausteine ------------------------------------------------------------------------------

    /**
     * Ein Block (Absatz, Überschrift, Liste): von Leerzeilen umgeben, Zeilenanfänge abgesichert.
     */
    private function block(string $content): string
    {
        $content = trim($content, " \t\n\r\0\x0B" . self::HARD_BREAK);
        if ($content === '') {
            return '';
        }
        return "\n\n" . $content . "\n\n";
    }

    private function paragraph(string $text): string
    {
        return $this->block($this->protectLineStart($text));
    }

    /**
     * Abschließende Bereinigung: <br>-Platzhalter auflösen, Leerzeilen zusammenfassen.
     */
    private function finalize(string $markdown): string
    {
        $hardBreak = self::HARD_BREAK;
        // Zwei oder mehr <br> hintereinander = neuer Absatz
        $markdown = preg_replace('/(?:[ \t]*' . $hardBreak . '[ \t]*){2,}/u', "\n\n", $markdown) ?? $markdown;
        // <br> am Anfang oder Ende eines Blocks ist bedeutungslos
        $markdown = preg_replace('/(^|\n\n)[ \t]*' . $hardBreak . '[ \t]*/u', '$1', $markdown) ?? $markdown;
        $markdown = preg_replace('/[ \t]*' . $hardBreak . '[ \t]*(\n\n|$)/u', '$1', $markdown) ?? $markdown;

        $lines = [];
        foreach (explode("\n", $markdown) as $line) {
            $line = rtrim($line);
            $lines[] = $line;
        }
        $markdown = implode("\n", $lines);
        $markdown = preg_replace("/\n{3,}/", "\n\n", $markdown) ?? $markdown;

        // Harter Zeilenumbruch innerhalb eines Absatzes; die Folgezeile wird abgesichert
        $segments = explode($hardBreak, $markdown);
        if (count($segments) > 1) {
            $markdown = '';
            foreach ($segments as $position => $segment) {
                if ($position > 0) {
                    $segment = $this->protectLineStart(ltrim($segment, " \t"));
                    $markdown .= "  \n" . $segment;
                } else {
                    $markdown .= rtrim($segment, " \t");
                }
            }
        }

        return trim($markdown);
    }

    /**
     * Inline-Text aus einem Fragment: Zeilenumbrüche und Blockgrenzen werden zu Leerzeichen.
     */
    private function inlineOnly(string $fragment): string
    {
        $fragment = str_replace(self::HARD_BREAK, ' ', $fragment);
        $fragment = preg_replace('/\s*\n+\s*/u', ' ', $fragment) ?? $fragment;
        return trim($this->collapseWhitespace($fragment));
    }

    private function codeSpan(string $code): string
    {
        $code = trim($this->collapseWhitespace($code));
        if ($code === '') {
            return '';
        }
        $fence = str_contains($code, '`') ? '``' : '`';
        return $fence . ' ' . $code . ' ' . $fence;
    }

    private function tableCell(mixed $cell): string
    {
        $cell = $this->stringify($cell);
        $cell = str_replace(self::HARD_BREAK, ' ', $cell);
        $cell = preg_replace('/\s*\n+\s*/u', ' ', $cell) ?? $cell;
        $cell = str_replace('|', '\\|', $cell);
        return trim($this->collapseWhitespace($cell));
    }

    private function indentListItem(string $marker, string $content): string
    {
        $lines = explode("\n", $content);
        $first = array_shift($lines) ?? '';
        $item = $marker . $this->protectLineStart($first);
        $indent = str_repeat(' ', strlen($marker));
        foreach ($lines as $line) {
            $item .= "\n" . ($line === '' ? '' : $indent . $line);
        }
        return $item;
    }

    private function indentLines(string $text, string $indent): string
    {
        return implode("\n", array_map(
            static fn(string $line): string => $line === '' ? '' : $indent . $line,
            explode("\n", $text)
        ));
    }

    private function formatUrl(string $url): string
    {
        if (preg_match('/[\s()<>]/', $url) === 1) {
            return '<' . str_replace(['<', '>'], ['%3C', '%3E'], $url) . '>';
        }
        return $url;
    }

    /**
     * Maskiert Zeichen, die innerhalb einer Zeile Markdown-Markup auslösen würden. URLs bleiben unverändert.
     */
    private function escapeInline(string $text): string
    {
        if ($text === '') {
            return '';
        }
        $tokens = preg_split('/(\s+)/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
        if ($tokens === false) {
            return $text;
        }
        foreach ($tokens as $index => $token) {
            if ($token === '' || preg_match('/^\s+$/u', $token) === 1 || preg_match('/^(https?:\/\/|www\.)/i', $token) === 1) {
                continue;
            }
            $tokens[$index] = preg_replace('/([\\\\`*_\[\]<~])/u', '\\\\$1', $token) ?? $token;
        }
        return implode('', $tokens);
    }

    /**
     * Sichert den Zeilenanfang ab, damit Klartext nicht als Überschrift, Liste, Zitat oder Tabelle gelesen wird.
     */
    private function protectLineStart(string $line): string
    {
        if ($line === '') {
            return '';
        }
        if (preg_match('/^(?:#{1,6}(?=\s|$)|>|[-+](?=\s|$)|\||(?:-+|=+)\s*$)/u', $line) === 1) {
            return '\\' . $line;
        }
        return preg_replace('/^(\d{1,9})([.)])(?=\s|$)/u', '$1\\\\$2', $line) ?? $line;
    }

    private function collapseWhitespace(string $text, bool $trim = true): string
    {
        $text = preg_replace('/[\s\x{00A0}\x{2028}\x{2029}]+/u', ' ', $text) ?? $text;
        return $trim ? trim($text) : $text;
    }

    private function stringify(mixed $value): string
    {
        if ($value === null || is_bool($value)) {
            return '';
        }
        if (is_string($value)) {
            return $value;
        }
        if (is_int($value) || is_float($value)) {
            return (string)$value;
        }
        if (is_array($value)) {
            return implode(' ', array_map(fn(mixed $item): string => $this->stringify($item), $value));
        }
        if ($value instanceof \Stringable) {
            return (string)$value;
        }
        return '';
    }
}
