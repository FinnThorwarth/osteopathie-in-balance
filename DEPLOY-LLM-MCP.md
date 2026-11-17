# Deploy: LLM-Features & MCP-Server

Betrifft: `Thorwarth.NeosMcp` (neu), LLM/GEO-Features in `Thorwarth.BaseSite` (llms.txt, `.md`-Fassungen,
ARD-Manifest, strukturierte Daten, WebMCP, Agent-Tracking), Logo-Cache-Busting.

`Web/` und `Configuration/` sind in `.gitignore` – die Schritte 2 und 3 daher **auf dem Server von Hand**.

## 1. Code & Pakete
```bash
git pull
composer install --no-dev          # symlinkt Thorwarth.NeosMcp, liest Thorwarth.BaseSite\Package neu ein
./flow flow:package:rescan
```

## 2. `Web/.htaccess` anpassen
Direkt nach `RewriteBase /`:
```apache
# Authorization-Header an PHP durchreichen (Bearer-Token für /api/mcp)
RewriteCond %{HTTP:Authorization} .
RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
```
Und die Zeile
```apache
RewriteRule ^(_Resources/Packages/|\.well-known/|robots\.txt|favicon\.ico) - [L]
```
ersetzen durch
```apache
# robots.txt und /.well-known/ nur statisch, wenn die Datei existiert – sonst rendert Flow sie
RewriteRule ^(_Resources/Packages/|favicon\.ico) - [L]
```
Liegt im Webroot eine statische `robots.txt`, gewinnt diese – dann entfällt der Hinweis auf `llms.txt`. Ggf. löschen.

## 3. MCP-Token
```bash
./flow mcp:token
```
`Configuration/Production/Settings.NeosMcp.yaml` anlegen:
```yaml
Thorwarth:
  NeosMcp:
    auth:
      tokens:
        claude-code: '<TOKEN>'
```
Optional für das Crawler-Tracking (`Configuration/Production/Settings.yaml`):
```yaml
Thorwarth:
  BaseSite:
    agentTracking:
      idSite: ''        # eigene Matomo-Site "Crawler & KI-Agenten" empfohlen
      tokenAuth: ''     # nur nötig, damit Matomo die IP des Agenten übernimmt
```

## 4. Caches
```bash
./flow flow:cache:flush
./flow flow:cache:warmup
```

## 5. Prüfen
```bash
curl -sI https://www.osteopathie-in-balance.de/llms.txt              # 200, text/plain
curl -sI https://www.osteopathie-in-balance.de/index.md              # 200, text/markdown
curl -s  https://www.osteopathie-in-balance.de/.well-known/ard.json  # JSON-Manifest
curl -s  https://www.osteopathie-in-balance.de/robots.txt            # enthält Hinweis auf llms.txt
curl -s -X POST https://www.osteopathie-in-balance.de/api/mcp \
  -H "Authorization: Bearer <TOKEN>" -H "Content-Type: application/json" \
  -d '{"jsonrpc":"2.0","id":1,"method":"tools/list"}'               # 401 = Token falsch, 503 = kein Token
```
Im Seitenquelltext: `<img src=".../Logo.svg?v=…">`, `<link rel="alternate" type="text/markdown">`,
`<script type="application/ld+json">` mit `MedicalBusiness`.

## 6. Claude Code anbinden
```bash
claude mcp add --transport http --scope user osteopathie-live https://www.osteopathie-in-balance.de/api/mcp \
  --header "Authorization: Bearer <TOKEN>"
```

## Offene Punkte
- `Thorwarth.BaseSite.branding.company.geo` / `mapUrl` (Settings.Geo.yaml): Koordinaten und Google-Maps-Link ergänzen.
- WebMCP-Origin-Trial-Token für die Domain beantragen (`agentic.webmcpOriginTrialToken`).
- Doctolib führt die Praxis unter „Gräfelfing“ mit zwei Praxis-IDs – prüfen, ob es einen zweiten Standort gibt
  (dann zweite Adresse in den strukturierten Daten ergänzen).
- Seitensuche: `/api/search` benötigt `flowpack/simplesearch` (nicht installiert) und liefert derzeit keine Treffer.
  Deshalb ist die Suche nicht im ARD-Manifest, in WebMCP und im WebSite-Schema enthalten.
