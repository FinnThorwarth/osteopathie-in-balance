<?php
declare(strict_types=1);

namespace Thorwarth\NeosMcp\Tools;

use Neos\Flow\Annotations as Flow;

/**
 * MCP-Tool-Definitionen (Name, Beschreibung, JSON-Schema der Argumente, Annotations)
 */
#[Flow\Proxy(false)]
final class ToolDefinitions
{
    private const WORKSPACE_PROPERTY = [
        'type' => 'string',
        'description' => 'Workspace-Name. Leer = Arbeits-Workspace "mcp". "live" = veröffentlichter Stand (nur lesend, sofern allowLiveWrites nicht aktiv ist).',
    ];

    private const LANGUAGE_PROPERTY = [
        'type' => 'string',
        'description' => 'Sprache (Content-Dimension), z. B. "de" oder "en". Leer = Standardsprache. Verfügbare Sprachen liefert neos_info.',
    ];

    private const ID_PROPERTY = [
        'type' => 'string',
        'description' => 'Node-ID (aus vorherigen Antworten, z. B. get_page_tree oder get_node).',
    ];

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function all(): array
    {
        return [
            self::tool(
                'neos_info',
                'Neos-Überblick',
                'Überblick über Sites, Workspaces (Status, offene Änderungen), den konfigurierten Arbeits-Workspace und die Anzahl der NodeTypes. Guter erster Aufruf.',
                [],
                [],
                ['readOnlyHint' => true]
            ),
            self::tool(
                'list_node_types',
                'NodeTypes auflisten',
                'Listet die verfügbaren NodeTypes (Seitentypen und Inhaltselemente) mit Label, Gruppe und Property-Namen. Details zu einem Typ liefert get_node_type.',
                [
                    'kind' => ['type' => 'string', 'enum' => ['all', 'document', 'content', 'collection'], 'description' => 'Filter: document = Seitentypen, content = Inhaltselemente, collection = Content-Collections (Standard: all).'],
                    'search' => ['type' => 'string', 'description' => 'Optionaler Suchbegriff (Name oder Label, case-insensitive).'],
                ],
                [],
                ['readOnlyHint' => true]
            ),
            self::tool(
                'get_node_type',
                'NodeType-Details',
                'Details eines NodeTypes: Properties (Typ, Label, Editor, erlaubte Werte, Defaults), Referenzen, feste Kind-Collections (z. B. "main") und erlaubte Kind-NodeTypes. Vor create_node/set_properties aufrufen.',
                [
                    'nodeType' => ['type' => 'string', 'description' => 'Vollständiger NodeType-Name, z. B. "Thorwarth.BaseSite:Content.Text".'],
                ],
                ['nodeType'],
                ['readOnlyHint' => true]
            ),
            self::tool(
                'get_page_tree',
                'Seitenbaum',
                'Seitenbaum (nur Dokumente) ab der Startseite oder einem Dokument: IDs, Titel, URL-Pfade, Typen, hidden/removed-Flags.',
                [
                    'rootId' => ['type' => 'string', 'description' => 'Dokument-ID als Startpunkt (Standard: Startseite der Site).'],
                    'depth' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 10, 'description' => 'Tiefe (Standard 3).'],
                    'workspace' => self::WORKSPACE_PROPERTY,
                    'language' => self::LANGUAGE_PROPERTY,
                    'includeRemoved' => ['type' => 'boolean', 'description' => 'Auch soft-gelöschte (noch nicht veröffentlichte Löschungen) anzeigen (Standard false).'],
                ],
                [],
                ['readOnlyHint' => true]
            ),
            self::tool(
                'get_node',
                'Node lesen',
                'Liest einen Node per ID oder ein Dokument per URL-Pfad (z. B. "/ueber-uns"). Bei Dokumenten inklusive des kompletten Inhaltsbaums (Collections und Inhaltselemente mit Properties) und der Unterseiten.',
                [
                    'id' => self::ID_PROPERTY,
                    'path' => ['type' => 'string', 'description' => 'URL-Pfad eines Dokuments, z. B. "/" oder "/services". Alternative zu id.'],
                    'workspace' => self::WORKSPACE_PROPERTY,
                    'language' => self::LANGUAGE_PROPERTY,
                    'includeContent' => ['type' => 'boolean', 'description' => 'Inhaltsbaum bei Dokumenten mitliefern (Standard true).'],
                    'depth' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 20, 'description' => 'Tiefe des Inhaltsbaums (Standard 8).'],
                    'includeRemoved' => ['type' => 'boolean', 'description' => 'Soft-gelöschte Nodes einbeziehen (Standard false).'],
                ],
                [],
                ['readOnlyHint' => true]
            ),
            self::tool(
                'find_nodes',
                'Nodes suchen',
                'Volltextsuche über Property-Werte und/oder Filter nach NodeType unterhalb der Site (oder eines Teilbaums). Liefert Treffer mit zugehörigem Dokument und Textausschnitten.',
                [
                    'searchTerm' => ['type' => 'string', 'description' => 'Suchbegriff (case-insensitive, Teilstring).'],
                    'nodeType' => ['type' => 'string', 'description' => 'NodeType-Filter inkl. Untertypen, z. B. "Neos.Neos:Document" oder "Thorwarth.BaseSite:Content.Text". Mehrere mit Komma, Negation mit "!".'],
                    'rootId' => ['type' => 'string', 'description' => 'Nur unterhalb dieses Nodes suchen (Standard: Site).'],
                    'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 200, 'description' => 'Maximale Trefferzahl (Standard 25).'],
                    'workspace' => self::WORKSPACE_PROPERTY,
                    'language' => self::LANGUAGE_PROPERTY,
                ],
                [],
                ['readOnlyHint' => true]
            ),
            self::tool(
                'list_assets',
                'Medien suchen',
                'Sucht Assets (Bilder, Dokumente) in der Medienverwaltung nach Titel. Liefert Asset-IDs, die in Bild-/Asset-Properties gesetzt werden können.',
                [
                    'search' => ['type' => 'string', 'description' => 'Suchbegriff im Titel (leer = alle).'],
                    'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 100, 'description' => 'Maximale Anzahl (Standard 25).'],
                ],
                [],
                ['readOnlyHint' => true]
            ),
            self::tool(
                'workspace_status',
                'Workspace-Status',
                'Zeigt die noch nicht veröffentlichten Änderungen eines Workspaces (angelegt/geändert/verschoben/gelöscht) mit zugehörigen Dokumenten sowie ob der Workspace veraltet ist.',
                [
                    'workspace' => self::WORKSPACE_PROPERTY,
                    'language' => self::LANGUAGE_PROPERTY,
                ],
                [],
                ['readOnlyHint' => true]
            ),
            self::tool(
                'set_properties',
                'Properties setzen',
                'Setzt Properties und/oder Referenzen eines Nodes. Nur im NodeType definierte Properties sind erlaubt (siehe get_node_type). null entfernt einen Wert. Rich-Text-Properties enthalten HTML. Bilder/Assets per Asset-ID.',
                [
                    'id' => self::ID_PROPERTY,
                    'properties' => ['type' => 'object', 'description' => 'Property-Name → Wert, z. B. {"title": "Neuer Titel", "text": "<p>…</p>"}.', 'additionalProperties' => true],
                    'references' => ['type' => 'object', 'description' => 'Referenz-Name → Liste von Node-IDs, z. B. {"link": ["<node-id>"]}. Leere Liste entfernt die Referenz.', 'additionalProperties' => true],
                    'workspace' => self::WORKSPACE_PROPERTY,
                    'language' => self::LANGUAGE_PROPERTY,
                ],
                ['id'],
                ['readOnlyHint' => false, 'destructiveHint' => false, 'idempotentHint' => true]
            ),
            self::tool(
                'create_node',
                'Node anlegen',
                'Legt ein neues Inhaltselement oder Dokument an. Ist parentId ein Dokument und der NodeType ein Inhaltselement, landet es automatisch in der Collection "main" (oder in "collection"). Dokumente brauchen "title"; uriPathSegment wird sonst erzeugt. Feste Kind-Nodes (childNodes) werden automatisch mit angelegt.',
                [
                    'parentId' => ['type' => 'string', 'description' => 'ID des Eltern-Nodes (Dokument, Collection oder Container-Element).'],
                    'nodeType' => ['type' => 'string', 'description' => 'NodeType des neuen Nodes.'],
                    'properties' => ['type' => 'object', 'description' => 'Initiale Properties.', 'additionalProperties' => true],
                    'references' => ['type' => 'object', 'description' => 'Initiale Referenzen (Name → Node-IDs).', 'additionalProperties' => true],
                    'collection' => ['type' => 'string', 'description' => 'Name der Ziel-Collection unterhalb von parentId, z. B. "main" oder "main/left" (optional).'],
                    'position' => ['type' => 'string', 'enum' => ['start', 'end', 'before', 'after'], 'description' => 'Einfügeposition (Standard end). before/after beziehen sich auf siblingId.'],
                    'siblingId' => ['type' => 'string', 'description' => 'Geschwister-Node für position before/after.'],
                    'workspace' => self::WORKSPACE_PROPERTY,
                    'language' => self::LANGUAGE_PROPERTY,
                ],
                ['parentId', 'nodeType'],
                ['readOnlyHint' => false, 'destructiveHint' => false, 'idempotentHint' => false]
            ),
            self::tool(
                'move_node',
                'Node verschieben',
                'Verschiebt einen Node an eine andere Position (innerhalb der Eltern oder in einen anderen Eltern-Node/Collection).',
                [
                    'id' => self::ID_PROPERTY,
                    'parentId' => ['type' => 'string', 'description' => 'Neuer Eltern-Node (optional; Standard: bisheriger).'],
                    'collection' => ['type' => 'string', 'description' => 'Ziel-Collection unterhalb von parentId (optional).'],
                    'position' => ['type' => 'string', 'enum' => ['start', 'end', 'before', 'after'], 'description' => 'Zielposition (Standard end).'],
                    'siblingId' => ['type' => 'string', 'description' => 'Geschwister-Node für position before/after.'],
                    'workspace' => self::WORKSPACE_PROPERTY,
                    'language' => self::LANGUAGE_PROPERTY,
                ],
                ['id'],
                ['readOnlyHint' => false, 'destructiveHint' => false, 'idempotentHint' => true]
            ),
            self::tool(
                'remove_node',
                'Node löschen',
                'Löscht einen Node samt Unterbaum. Im Arbeits-Workspace ist das eine Soft-Löschung, die erst beim Veröffentlichen endgültig wird und sich mit discard zurücknehmen lässt.',
                [
                    'id' => self::ID_PROPERTY,
                    'workspace' => self::WORKSPACE_PROPERTY,
                    'language' => self::LANGUAGE_PROPERTY,
                ],
                ['id'],
                ['readOnlyHint' => false, 'destructiveHint' => true, 'idempotentHint' => true]
            ),
            self::tool(
                'set_hidden',
                'Node ausblenden/einblenden',
                'Blendet einen Node (Seite oder Inhaltselement) samt Unterbaum aus oder wieder ein.',
                [
                    'id' => self::ID_PROPERTY,
                    'hidden' => ['type' => 'boolean', 'description' => 'true = ausblenden, false = einblenden.'],
                    'workspace' => self::WORKSPACE_PROPERTY,
                    'language' => self::LANGUAGE_PROPERTY,
                ],
                ['id', 'hidden'],
                ['readOnlyHint' => false, 'destructiveHint' => false, 'idempotentHint' => true]
            ),
            self::tool(
                'translate_node',
                'Übersetzung anlegen',
                'Legt eine Sprachvariante eines bestehenden Nodes an (z. B. eine deutsche Seite auf Englisch). Kopiert die Inhalte der Quellsprache als Ausgangspunkt; anschließend die Texte mit set_properties und language = Zielsprache übersetzen. Bei Dokumenten werden fehlende Elternseiten und (mit includeContent) alle Inhaltselemente mit übersetzt.',
                [
                    'id' => self::ID_PROPERTY,
                    'targetLanguage' => ['type' => 'string', 'description' => 'Zielsprache, z. B. "en".'],
                    'sourceLanguage' => ['type' => 'string', 'description' => 'Quellsprache (Standard: Standardsprache, z. B. "de").'],
                    'includeContent' => ['type' => 'boolean', 'description' => 'Bei Dokumenten auch alle Inhaltselemente übernehmen (Standard true).'],
                    'includeParents' => ['type' => 'boolean', 'description' => 'Fehlende Eltern-Dokumente in der Zielsprache mit anlegen (Standard true).'],
                    'workspace' => self::WORKSPACE_PROPERTY,
                ],
                ['id', 'targetLanguage'],
                ['readOnlyHint' => false, 'destructiveHint' => false, 'idempotentHint' => true]
            ),
            self::tool(
                'publish',
                'Veröffentlichen',
                'Veröffentlicht die Änderungen des Arbeits-Workspaces nach live (alle oder nur die eines Dokuments). Danach sind die Inhalte öffentlich sichtbar. Vorher mit workspace_status prüfen.',
                [
                    'documentId' => ['type' => 'string', 'description' => 'Nur die Änderungen dieses Dokuments (inkl. seiner Inhalte) veröffentlichen (optional).'],
                    'workspace' => self::WORKSPACE_PROPERTY,
                ],
                [],
                ['readOnlyHint' => false, 'destructiveHint' => false, 'idempotentHint' => false]
            ),
            self::tool(
                'discard',
                'Änderungen verwerfen',
                'Verwirft nicht veröffentlichte Änderungen des Arbeits-Workspaces (alle oder nur die eines Dokuments). Nicht rückgängig zu machen.',
                [
                    'documentId' => ['type' => 'string', 'description' => 'Nur die Änderungen dieses Dokuments verwerfen (optional).'],
                    'workspace' => self::WORKSPACE_PROPERTY,
                ],
                [],
                ['readOnlyHint' => false, 'destructiveHint' => true, 'idempotentHint' => true]
            ),
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function names(): array
    {
        return array_map(fn (array $tool) => $tool['name'], self::all());
    }

    private static function tool(string $name, string $title, string $description, array $properties, array $required, array $annotations): array
    {
        $schema = [
            'type' => 'object',
            'properties' => $properties === [] ? new \stdClass() : $properties,
            'additionalProperties' => false,
        ];
        if ($required !== []) {
            $schema['required'] = $required;
        }
        return [
            'name' => $name,
            'title' => $title,
            'description' => $description,
            'inputSchema' => $schema,
            'annotations' => ['title' => $title] + $annotations,
        ];
    }
}
