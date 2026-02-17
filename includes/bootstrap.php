<?php
/**
 * Bootstrap – wird auf jeder Seite als erstes geladen.
 *
 * "Bootstrap" bedeutet hier nicht das CSS-Framework, sondern
 * das Hochfahren der Anwendung: Session starten, Funktionen laden.
 */

session_start(); // Session initialisieren (muss vor jeder Ausgabe passieren!)

// Warenkorb initialisieren falls noch nicht vorhanden
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Alle includes laden
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/cart.php';

// Hilfsfunktionen
function formatPrice(float $price): string {
    return number_format($price, 2, ',', '.') . ' €';
}

function h(string $string): string {
    // HTML-Sonderzeichen escapen – IMMER nutzen wenn User-Input ausgegeben wird!
    // Schützt vor XSS (Cross-Site-Scripting) Angriffen
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// Redirect-Hilfsfunktion
function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}
