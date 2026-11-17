# Thorwarth.NeosMcp – MCP-Server für Neos CMS 9

Stellt das Content Repository der Website als **MCP-Server** (Model Context Protocol) bereit, damit
KI-Werkzeuge wie Claude Code Inhalte lesen **und schreiben** können – auch auf der Live-Umgebung.

- Transport: **Streamable HTTP** unter `POST /api/mcp` (stateless, kein SSE) oder **stdio** (`./flow mcp:serve`)
- Auth: **Bearer-Token** (`Authorization: Bearer …`), Audit-Log in `Data/Logs/NeosMcp.log`
- Schreibzugriffe landen im Shared Workspace **`mcp`** (wird automatisch angelegt, im Neos-Backend sichtbar)
  und gehen erst mit dem Tool `publish` live. Direktes Schreiben nach `live` ist abschaltbar/einschaltbar.
- Keine externen Abhängigkeiten, läuft mit Neos 9.0.

## Tools

| Tool | Art | Zweck |
|---|---|---|
| `neos_info` | lesen | Sites, Workspaces (Status, offene Änderungen), Arbeits-Workspace |
| `list_node_types` | lesen | Seitentypen und Inhaltselemente mit Property-Namen |
| `get_node_type` | lesen | Properties (Typ, Editor, Werte, Defaults), Referenzen, Collections, erlaubte Kindtypen |
| `get_page_tree` | lesen | Seitenbaum mit IDs, Titeln, URL-Pfaden |
| `get_node` | lesen | Node per ID oder Dokument per Pfad (`/services`), inkl. Inhaltsbaum |
| `find_nodes` | lesen | Volltextsuche / NodeType-Filter |
| `list_assets` | lesen | Medien suchen (Asset-IDs für Bild-Properties) |
| `workspace_status` | lesen | Nicht veröffentlichte Änderungen |
| `set_properties` | schreiben | Properties/Referenzen setzen (validiert gegen den NodeType) |
| `create_node` | schreiben | Inhaltselement oder Seite anlegen (Position, Collection, uriPathSegment automatisch) |
| `move_node` | schreiben | Verschieben / umsortieren |
| `remove_node` | schreiben | Löschen (im Workspace als Soft-Löschung) |
| `set_hidden` | schreiben | Aus-/Einblenden |
| `publish` | schreiben | Workspace (oder ein Dokument) nach live veröffentlichen |
| `translate_node` | schreiben | Sprachvariante anlegen (Seite inkl. Inhalt und fehlender Elternseiten) |
| `discard` | schreiben | Änderungen verwerfen |

Alle Tools akzeptieren optional `workspace`. Leer = `mcp`, `live` = veröffentlichter Stand.

Inhaltstools akzeptieren optional `language` (`de` = Standard, `en`). Englische Seiten entstehen mit
`translate_node` (kopiert die deutsche Fassung) und werden danach per `set_properties` mit `language: "en"` übersetzt.
Konfiguration: `dimensionSpacePoint` (Standard-Dimension) und `languageDimension` (Name der Sprach-Dimension).

## Installation auf dem Live-Server

1. Code deployen (das Paket liegt unter `DistributionPackages/Thorwarth.NeosMcp` und ist im
   `composer.json`/`composer.lock` der Distribution eingetragen):
   ```bash
   composer install --no-dev
   ```
2. Token erzeugen und **außerhalb von Git** hinterlegen:
   ```bash
   ./flow mcp:token
   cp Configuration/Production/Settings.NeosMcp.yaml.example Configuration/Production/Settings.NeosMcp.yaml
   # Token in Settings.NeosMcp.yaml eintragen
   ```
3. Caches leeren:
   ```bash
   ./flow flow:cache:flush
   ./flow flow:cache:warmup
   ```
4. Prüfen (401 = Endpoint aktiv, Token falsch/fehlt; 503 = kein Token konfiguriert):
   ```bash
   curl -s -X POST https://www.osteopathie-in-balance.com/api/mcp \
     -H "Authorization: Bearer <TOKEN>" -H "Content-Type: application/json" \
     -d '{"jsonrpc":"2.0","id":1,"method":"tools/list"}'
   ```

`Web/.htaccess` enthält eine Rewrite-Regel, die den `Authorization`-Header an PHP durchreicht
(Apache/FastCGI verwirft ihn sonst). Sollte der Hoster den Header dennoch entfernen, kann das Token
alternativ im Header `X-Neos-Mcp-Token` gesendet werden (`auth.fallbackHeader`).

## Claude Code anbinden

Live-Server benutzerweit registrieren (Token wird in `~/.claude.json` gespeichert, nicht im Repo):

```bash
claude mcp add --transport http --scope user osteopathie-live https://www.osteopathie-in-balance.com/api/mcp \
  --header "Authorization: Bearer <TOKEN>"
```

Lokal (Docker, Token in `Configuration/Development/Docker/Settings.NeosMcp.yaml`):

```bash
claude mcp add --transport http --scope local osteopathie-local http://localhost:8081/api/mcp \
  --header "Authorization: Bearer <TOKEN>"
```

Lokal ohne HTTP (stdio, benötigt laufende Datenbank):

```json
{ "mcpServers": { "neos-local": { "command": "php", "args": ["neos-app/flow", "mcp:serve"] } } }
```

## CLI

```bash
./flow mcp:token                      # Zufallstoken erzeugen
./flow mcp:tools                      # Tools auflisten
./flow mcp:call get_node --arguments '{"path":"/"}'   # Tool direkt ausführen (ohne HTTP)
./flow mcp:serve                      # MCP über stdio
```

## Konfiguration (Settings.yaml, Pfad `Thorwarth.NeosMcp`)

| Schlüssel | Standard | Bedeutung |
|---|---|---|
| `endpointPath` | `/api/mcp` | Pfad des HTTP-Endpoints |
| `auth.tokens` | `[]` | Liste oder Map `label: token` (mind. 20 Zeichen) |
| `auth.fallbackHeader` | `X-Neos-Mcp-Token` | Ersatz-Header für das Token |
| `allowedOrigins` | `[]` | Erlaubte `Origin`-Header (Browser-Clients) |
| `workspace.name` | `mcp` | Arbeits-Workspace (wird bei Bedarf angelegt) |
| `workspace.allowLiveWrites` | `false` | Direktes Schreiben mit `workspace: live` erlauben |
| `dimensionSpacePoint` | `{language: de}` | Standard-Dimension |
| `languageDimension` | `language` | Dimension, die per Argument `language` gewählt wird |
| `instructions` | Text | Hinweise, die das KI-Modell beim Verbinden erhält |

## Sicherheit

- Nur über HTTPS betreiben; das Token gewährt vollen Lese-/Schreibzugriff auf Inhalte (nicht auf Benutzer,
  Pakete oder Konfiguration).
- Jeder Aufruf wird mit Client-Label, IP, Tool und Argumenten in `Data/Logs/NeosMcp.log` protokolliert.
- Der Endpoint läuft als Flow-HTTP-Middleware vor dem Routing; Flow-Policies greifen hier nicht, die
  Zugriffskontrolle erfolgt ausschließlich über das Token. Die Autorisierungsprüfungen des Content Repository
  sind für den Aufruf deaktiviert (vergleichbar mit CLI-Kommandos).
- Requests mit fremdem `Origin`-Header werden abgelehnt (DNS-Rebinding-Schutz).
- Token rotieren: neues Token in `Settings.NeosMcp.yaml`, `./flow flow:cache:flush`, Client aktualisieren.
