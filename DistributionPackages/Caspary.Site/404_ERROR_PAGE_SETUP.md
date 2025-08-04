# 404-Fehlerseite Setup für Caspary.Site

## Übersicht

Diese Dokumentation beschreibt die implementierte 404-Fehlerseite für das Caspary.Site Neos 9 Projekt.

## Implementierte Komponenten

### 1. NodeType: ErrorPage
- **Datei**: `NodeTypes/Document/ErrorPage.yaml`
- **Beschreibung**: Eine spezielle NodeType für Fehlerseiten mit konfigurierbaren Eigenschaften
- **Eigenschaften**:
  - `errorCode`: Der HTTP-Statuscode (Standard: 404)
  - `errorTitle`: Der Titel der Fehlermeldung
  - `errorMessage`: Die Fehlermeldung
  - `showSearch`: Suchfunktion anzeigen/verstecken
  - `showNavigation`: Navigation anzeigen/verstecken

### 2. Fusion Template
- **Datei**: `Resources/Private/Fusion/Document/ErrorPage.fusion`
- **Beschreibung**: Das Fusion-Template für die ErrorPage NodeType
- **Features**:
  - Responsive Design
  - Suchfunktion
  - Navigation zu wichtigen Seiten
  - Anpassbare Inhalte

### 3. HTML Templates
- **404 Template**: `Resources/Private/Templates/Error/404.html`
- **403 Template**: `Resources/Private/Templates/Error/403.html`
- **Beschreibung**: Statische HTML-Templates für verschiedene HTTP-Statuscodes

### 4. Konfiguration
- **Datei**: `Configuration/Settings.yaml`
- **Beschreibung**: Automatische Weiterleitung bei verschiedenen Fehlern

## Automatische Aktivierung

Die 404-Seite wird automatisch aktiviert, wenn:
1. Eine URL nicht gefunden wird (`NoMatchingRouteException`)
2. Eine Action gestoppt wird (`StopActionException`)
3. Zugriff verweigert wird (`AccessDeniedException`)

## Manuelle Erstellung einer 404-Seite

### Im Neos Backend:
1. Gehen Sie zum Content-Bereich
2. Erstellen Sie eine neue Seite vom Typ "404 Fehlerseite"
3. Konfigurieren Sie die Eigenschaften:
   - Fehlercode: 404
   - Fehlertitel: "Seite nicht gefunden"
   - Fehlermeldung: "Die angeforderte Seite konnte leider nicht gefunden werden."
   - Suchfunktion anzeigen: Ja
   - Navigation anzeigen: Ja
4. Fügen Sie optional zusätzliche Inhalte hinzu (Text, Headlines, Buttons)

## Anpassungen

### Eigene Fehlermeldungen
Sie können die Standard-Fehlermeldungen in der `Settings.yaml` anpassen:

```yaml
Neos:
  Neos:
    sites:
      '*':
        errorHandling:
          'Neos\Flow\Mvc\Exception\NoMatchingRouteException':
            renderingGroups:
              'Neos.Neos:Fallback':
                options:
                  templatePathAndFilename: 'resource://Caspary.Site/Private/Templates/Error/404.html'
                  variables:
                    errorCode: 404
                    errorTitle: 'Ihr eigener Titel'
                    errorMessage: 'Ihre eigene Fehlermeldung'
```

### Zusätzliche HTTP-Statuscodes
Fügen Sie weitere Statuscodes in der `Settings.yaml` hinzu:

```yaml
'Neos\Flow\Http\Exception\ServiceUnavailableException':
  renderingGroups:
    'Neos.Neos:Fallback':
      options:
        templatePathAndFilename: 'resource://Caspary.Site/Private/Templates/Error/503.html'
        variables:
          errorCode: 503
          errorTitle: 'Service nicht verfügbar'
          errorMessage: 'Der Service ist vorübergehend nicht verfügbar.'
```

## Styling

Die Fehlerseiten verwenden:
- Tailwind CSS Klassen
- Caspary Design System
- Responsive Design
- Konsistente Farben (`caspary-black`, etc.)

## Testing

### Testen der 404-Seite:
1. Rufen Sie eine nicht existierende URL auf (z.B. `/nicht-existierend`)
2. Die 404-Seite sollte automatisch angezeigt werden

### Testen der 403-Seite:
1. Versuchen Sie, auf eine geschützte Seite zuzugreifen
2. Die 403-Seite sollte angezeigt werden

## Wartung

### Cache leeren:
Nach Änderungen an der Konfiguration:
```bash
./flow cache:flush
```

### NodeTypes aktualisieren:
```bash
./flow node:repair
```

## Support

Bei Fragen oder Problemen:
- Überprüfen Sie die Neos-Logs
- Stellen Sie sicher, dass alle Dateien korrekt erstellt wurden
- Überprüfen Sie die Berechtigungen der Template-Dateien

## Changelog

- **v1.0**: Initiale Implementierung der 404- und 403-Fehlerseiten
- Unterstützung für automatische Weiterleitung
- Responsive Design mit Tailwind CSS
- Konfigurierbare Eigenschaften 
