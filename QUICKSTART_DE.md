# Lokal starten – Windows

Eine externe Datenbank ist nicht nötig.

## Am einfachsten – ohne PowerShell

1. Entpacke die ZIP-Datei vollständig.
2. Öffne den Ordner `access-governance-dashboard`.
3. Doppelklicke auf **`START_HERE.cmd`**.
4. Der Browser öffnet automatisch `http://localhost:8080`.

Das Startprogramm verwendet automatisch **PHP 8.2+**. Falls PHP nicht vorhanden ist, versucht es **Docker Desktop**.

## Manuell mit PHP

Öffne die Eingabeaufforderung im Projektordner:

```cmd
php -S localhost:8080 -t public
```

## Manuell mit Docker Desktop

```cmd
docker compose up --build
```

Beim ersten Start lädt Docker das PHP-Basisimage herunter und baut die SQLite-Erweiterung. Das kann einige Minuten dauern. Sobald `Apache` läuft, ist die Seite unter `http://localhost:8080` erreichbar.

## Demo-Login

- E-Mail: `altan@example.test`
- Passwort: `demo123`

Weitere Demo-Benutzer stehen auf der Login-Seite.

## Daten zurücksetzen

Mit PHP:

```cmd
php scripts\reset_demo.php
```

Mit Docker:

```cmd
docker compose down -v
docker compose up --build
```
