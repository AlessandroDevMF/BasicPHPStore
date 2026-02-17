<?php
/**
 * Datenbankverbindung via PDO (PHP Data Objects)
 *
 * PDO ist der moderne Weg in PHP, mit Datenbanken zu sprechen.
 * Der Vorteil: Du kannst später SQLite durch MySQL ersetzen,
 * ohne den restlichen Code zu ändern – nur diese Datei anpassen.
 */

define('DB_PATH', __DIR__ . '/../database.sqlite');

function getDB(): PDO {
    static $pdo = null; // static = wird nur einmal erstellt, dann wiederverwendet

    if ($pdo === null) {
        try {
            $pdo = new PDO('sqlite:' . DB_PATH);
            
            // Bei Fehler: Exception werfen statt still scheitern
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Ergebnisse als assoziatives Array (z.B. $row['name'] statt $row[0])
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            die('Datenbankfehler: ' . $e->getMessage());
        }
    }

    return $pdo;
}
