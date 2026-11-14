# SuluAITranslatorBundle (Deutsch)

Dies ist die deutsche Ergänzung zur [englischen README](README.md), die Installation und alle anderen Funktionen beschreibt. Hier stehen die Verbrauchsstatistik und die Dashboard-Kachel.

## Verbrauchsstatistik

Der DeepL-Verbrauch des Monats wird an zwei Stellen gezeigt. Beide sind standardmäßig eingeschaltet und lassen sich unabhängig voneinander abschalten:

```yaml
sulu_ai_translator:
    usage_statistics:
        settings_page: true   # false: kein Eintrag "DeepL Verbrauchsstatistik" und keine Seite unter Einstellungen
        dashboard: true       # false: keine Kachel auf dem Dashboard
```

Beide nutzen das Recht "DeepL Usage Statistics" (`sulu.settings.access_usage_statistics`) unter *Einstellungen > Benutzerrollen*. Es bleibt auch mit `settings_page: false` bestehen, weil die Kachel es braucht.

### Kachel auf dem Dashboard

Ist [manuxi/sulu-dashboard-bundle](https://github.com/manuxi/SuluDashboardBundle) installiert, ergänzt dieses Bundle dessen Dashboard um die Kachel "DeepL Verbrauchsstatistik": ein Balken mit den in diesem Monat genutzten Zeichen, in der Standardfarbe bis 80 %, ab 80 % orange und bei 100 % rot. Das Dashboard ist nur eine Empfehlung (`suggest`). Ohne es ändert sich nichts, die Kachel-Dienste werden dann nicht einmal geladen:

```console
composer require manuxi/sulu-dashboard-bundle
```

Die Kachel scheitert nie still. Fehlt der API-Schlüssel, lehnt DeepL ihn ab, ist DeepL nicht erreichbar oder liefert das Konto keine Verbrauchsangaben, erklärt die Kachel das in einfachen Worten statt des Balkens. Technische Einzelheiten einer fehlgeschlagenen Anfrage stehen im Log.

Mit `usage_statistics.settings_page: false` entfällt die zusätzliche Seite unter Einstellungen, wenn die Kachel reicht.

## Entwicklung

Tests und PHPStan für die Kachel brauchen einen Checkout des optionalen Dashboard-Bundles, das nicht in der `composer.json` steht:

```powershell
$env:SULU_DASHBOARD_BUNDLE_PATH = "D:\Pfad\zu\SuluDashboardBundle"
composer test
composer phpstan
```

Ohne die Variable werden die Tests der Kachel übersprungen.
