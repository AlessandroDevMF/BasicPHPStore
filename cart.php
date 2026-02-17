<?php
require_once 'includes/bootstrap.php';

// Warenkorb-Aktionen verarbeiten
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action    = $_POST['action']     ?? '';
    $productId = (int)($_POST['product_id'] ?? 0);

    if ($action === 'remove' && $productId > 0) {
        cartRemove($productId);
    } elseif ($action === 'update' && $productId > 0) {
        $qty = (int)($_POST['quantity'] ?? 0);
        cartUpdate($productId, $qty);
    } elseif ($action === 'clear') {
        cartClear();
    }

    // PRG-Pattern: nach POST immer redirecten → kein doppeltes Absenden beim Refresh
    redirect('cart.php');
}

$items = cartItems();
$total = cartTotal();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Warenkorb – PhpShop</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<header class="site-header">
    <div class="container header-inner">
        <a href="index.php" class="logo">
            <span class="logo-icon">⟨/⟩</span>
            PhpShop
        </a>
        <nav class="main-nav">
            <a href="index.php">← Weiter einkaufen</a>
        </nav>
        <form action="search.php" method="GET" class="search-form">
            <input type="search" name="q" placeholder="Suchen…" class="search-input">
            <button type="submit" class="search-btn">🔍</button>
        </form>
        <a href="cart.php" class="cart-btn active">
            🛒 Warenkorb
            <?php if (cartCount() > 0): ?>
                <span class="cart-badge"><?= cartCount() ?></span>
            <?php endif; ?>
        </a>
    </div>
</header>

<main class="container">
    <div class="page-title">
        <h1>Warenkorb</h1>
        <?php if (!empty($items)): ?>
            <p><?= cartCount() ?> Artikel</p>
        <?php endif; ?>
    </div>

    <?php if (empty($items)): ?>
        <!-- Leerer Warenkorb -->
        <div class="empty-cart">
            <div class="empty-cart-icon">🛒</div>
            <h2>Dein Warenkorb ist leer</h2>
            <p>Schau dich in unserem Shop um!</p>
            <a href="index.php" class="btn btn-primary">Zum Shop</a>
        </div>

    <?php else: ?>
        <!-- Warenkorb-Tabelle -->
        <div class="cart-layout">
            <div class="cart-items">
                <?php foreach ($items as $item): 
                    $p = $item['product'];
                ?>
                <div class="cart-item">
                    <img src="<?= h($p['image_url']) ?>" 
                         alt="<?= h($p['name']) ?>" 
                         class="cart-item-img">
                    
                    <div class="cart-item-info">
                        <h3>
                            <a href="product.php?id=<?= $p['id'] ?>"><?= h($p['name']) ?></a>
                        </h3>
                        <span class="product-category"><?= h($p['category_name'] ?? '') ?></span>
                        <span class="cart-item-unit-price"><?= formatPrice($p['price']) ?> / Stück</span>
                    </div>

                    <!-- Menge ändern -->
                    <form method="POST" action="cart.php" class="cart-item-qty-form">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                        <input type="number" name="quantity" 
                               value="<?= $item['quantity'] ?>"
                               min="0" max="<?= $p['stock'] ?>"
                               class="qty-input"
                               onchange="this.form.submit()">
                    </form>

                    <div class="cart-item-subtotal">
                        <?= formatPrice($item['subtotal']) ?>
                    </div>

                    <!-- Entfernen -->
                    <form method="POST" action="cart.php">
                        <input type="hidden" name="action" value="remove">
                        <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                        <button type="submit" class="btn-remove" title="Entfernen">✕</button>
                    </form>
                </div>
                <?php endforeach; ?>

                <!-- Warenkorb leeren -->
                <div class="cart-actions">
                    <form method="POST" action="cart.php">
                        <input type="hidden" name="action" value="clear">
                        <button type="submit" class="btn btn-ghost">Warenkorb leeren</button>
                    </form>
                    <a href="index.php" class="btn btn-ghost">← Weiter einkaufen</a>
                </div>
            </div>

            <!-- Zusammenfassung -->
            <aside class="cart-summary">
                <h2>Zusammenfassung</h2>
                <div class="summary-row">
                    <span>Zwischensumme</span>
                    <span><?= formatPrice($total) ?></span>
                </div>
                <div class="summary-row">
                    <span>Versand</span>
                    <span><?= $total >= 50 ? 'Kostenlos' : formatPrice(4.99) ?></span>
                </div>
                <?php if ($total < 50): ?>
                <div class="free-shipping-hint">
                    Noch <?= formatPrice(50 - $total) ?> bis zum kostenlosen Versand!
                </div>
                <?php endif; ?>
                <div class="summary-row summary-total">
                    <span>Gesamt</span>
                    <span><?= formatPrice($total + ($total >= 50 ? 0 : 4.99)) ?></span>
                </div>
                <button class="btn btn-primary btn-large btn-block" onclick="alert('Checkout noch nicht implementiert – aber das wäre der nächste Schritt! 🚀')">
                    Zur Kasse →
                </button>
                <p class="summary-note">
                    inkl. MwSt. | Sicher bezahlen mit PayPal, Kreditkarte
                </p>
            </aside>
        </div>
    <?php endif; ?>
</main>

<footer class="site-footer">
    <div class="container">
        <p>PhpShop – Gebaut mit PHP 8 & SQLite</p>
    </div>
</footer>

</body>
</html>
