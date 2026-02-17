<?php
/**
 * Warenkorb-Funktionen
 *
 * Der Warenkorb wird in der PHP-Session gespeichert.
 * Sessions speichern Daten serverseitig – der Browser bekommt
 * nur eine Session-ID (als Cookie). So bleibt der Warenkorb
 * auch beim Seitenwechsel erhalten.
 *
 * Struktur: $_SESSION['cart'] = [
 *   product_id => quantity,
 *   1 => 2,   // Produkt 1, 2x im Warenkorb
 *   3 => 1,   // Produkt 3, 1x im Warenkorb
 * ]
 */

function cartAdd(int $productId, int $qty = 1): void {
    if (!isset($_SESSION['cart'][$productId])) {
        $_SESSION['cart'][$productId] = 0;
    }
    $_SESSION['cart'][$productId] += $qty;
}

function cartRemove(int $productId): void {
    unset($_SESSION['cart'][$productId]);
}

function cartUpdate(int $productId, int $qty): void {
    if ($qty <= 0) {
        cartRemove($productId);
    } else {
        $_SESSION['cart'][$productId] = $qty;
    }
}

function cartClear(): void {
    $_SESSION['cart'] = [];
}

function cartCount(): int {
    if (empty($_SESSION['cart'])) return 0;
    return array_sum($_SESSION['cart']); // Summe aller Mengen
}

/**
 * Gibt alle Warenkorbprodukte mit Details aus der DB zurück.
 * Wichtig: Wir holen die Preise immer aus der DB – nie aus dem Browser!
 * (Sonst könnte ein User den Preis manipulieren.)
 */
function cartItems(): array {
    if (empty($_SESSION['cart'])) return [];

    $db = getDB();
    $ids = array_keys($_SESSION['cart']);
    
    // IN (?,?,?) dynamisch aufbauen
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $db->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
    $stmt->execute($ids);
    $products = $stmt->fetchAll();

    $items = [];
    foreach ($products as $product) {
        $qty = $_SESSION['cart'][$product['id']];
        $items[] = [
            'product'  => $product,
            'quantity' => $qty,
            'subtotal' => $product['price'] * $qty,
        ];
    }

    return $items;
}

function cartTotal(): float {
    $total = 0;
    foreach (cartItems() as $item) {
        $total += $item['subtotal'];
    }
    return $total;
}
