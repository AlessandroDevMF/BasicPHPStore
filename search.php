<?php
require_once 'includes/bootstrap.php';

// Suchbegriff aus URL holen und bereinigen
$query = trim($_GET['q'] ?? '');

$products = [];
$totalResults = 0;

if (strlen($query) >= 2) {
    $db = getDB();
    
    // LIKE-Suche in Name UND Beschreibung
    // % = Wildcard (beliebige Zeichen davor/danach)
    $searchTerm = '%' . $query . '%';
    
    $stmt = $db->prepare('
        SELECT p.*, c.name AS category_name
        FROM products p
        JOIN categories c ON p.category_id = c.id
        WHERE p.name LIKE ?
           OR p.description LIKE ?
        ORDER BY 
            CASE WHEN p.name LIKE ? THEN 0 ELSE 1 END, -- Treffer im Namen zuerst
            p.name ASC
    ');
    $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
    $products = $stmt->fetchAll();
    $totalResults = count($products);
}

// Warenkorb-Aktion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $productId = (int)$_POST['product_id'];
    if ($_POST['action'] === 'add' && $productId > 0) {
        cartAdd($productId);
        redirect('search.php?q=' . urlencode($query) . '&added=' . $productId);
    }
}

$addedId = isset($_GET['added']) ? (int)$_GET['added'] : null;
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suche: <?= h($query) ?> – PhpShop</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<header class="site-header">
    <div class="container header-inner">
        <a href="index.php" class="logo">
            <span class="logo-icon">⟨/⟩</span>
            PhpShop
        </a>
        <form action="search.php" method="GET" class="search-form">
            <input 
                type="search" 
                name="q" 
                value="<?= h($query) ?>"
                placeholder="Produkte suchen…"
                class="search-input"
                autofocus>
            <button type="submit" class="search-btn">🔍</button>
        </form>
        <a href="cart.php" class="cart-btn">
            🛒 Warenkorb
            <?php if (cartCount() > 0): ?>
                <span class="cart-badge"><?= cartCount() ?></span>
            <?php endif; ?>
        </a>
    </div>
</header>

<main class="container">

    <?php if ($addedId): ?>
    <div class="alert alert-success">✓ Produkt wurde zum Warenkorb hinzugefügt. <a href="cart.php">Zum Warenkorb →</a></div>
    <?php endif; ?>

    <?php if ($query === ''): ?>
        <!-- Noch keine Suche -->
        <div class="search-empty">
            <div class="search-empty-icon">🔍</div>
            <h1>Was suchst du?</h1>
            <p>Gib einen Suchbegriff ein um Produkte zu finden.</p>
        </div>

    <?php elseif (strlen($query) < 2): ?>
        <div class="search-empty">
            <h1>Suchbegriff zu kurz</h1>
            <p>Bitte mindestens 2 Zeichen eingeben.</p>
        </div>

    <?php else: ?>
        <div class="page-title">
            <h1>Suchergebnisse für „<?= h($query) ?>"</h1>
            <p><?= $totalResults ?> <?= $totalResults === 1 ? 'Ergebnis' : 'Ergebnisse' ?></p>
        </div>

        <?php if (empty($products)): ?>
            <div class="search-empty">
                <div class="search-empty-icon">😕</div>
                <h2>Nichts gefunden</h2>
                <p>Für „<?= h($query) ?>" gibt es keine Treffer.</p>
                <a href="index.php" class="btn btn-primary">Alle Produkte anzeigen</a>
            </div>
        <?php else: ?>
            <div class="product-grid">
                <?php foreach ($products as $product): ?>
                <div class="product-card">
                    <a href="product.php?id=<?= $product['id'] ?>" class="product-img-link">
                        <img src="<?= h($product['image_url']) ?>"
                             alt="<?= h($product['name']) ?>"
                             class="product-img">
                    </a>
                    <div class="product-info">
                        <span class="product-category"><?= h($product['category_name']) ?></span>
                        <h2 class="product-name">
                            <a href="product.php?id=<?= $product['id'] ?>"><?= h($product['name']) ?></a>
                        </h2>
                        <p class="product-desc"><?= h($product['description']) ?></p>
                        <div class="product-footer">
                            <span class="product-price"><?= formatPrice($product['price']) ?></span>
                            <?php if ($product['stock'] > 0): ?>
                                <form method="POST">
                                    <input type="hidden" name="action" value="add">
                                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                    <button type="submit" class="btn btn-primary">In den Warenkorb</button>
                                </form>
                            <?php else: ?>
                                <span class="out-of-stock">Nicht vorrätig</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>

</main>

<footer class="site-footer">
    <div class="container">
        <p>PhpShop – Gebaut mit PHP 8 & SQLite</p>
    </div>
</footer>

</body>
</html>
