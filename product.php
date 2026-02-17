<?php
require_once 'includes/bootstrap.php';

// Produkt-ID aus URL holen und zu Integer casten (Sicherheit!)
$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($productId <= 0) {
    redirect('index.php');
}

$db = getDB();
$stmt = $db->prepare('
    SELECT p.*, c.name AS category_name, c.slug AS category_slug
    FROM products p
    JOIN categories c ON p.category_id = c.id
    WHERE p.id = ?
');
$stmt->execute([$productId]);
$product = $stmt->fetch();

// Produkt nicht gefunden → zurück zur Startseite
if (!$product) {
    redirect('index.php');
}

// In Warenkorb legen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $qty = max(1, (int)($_POST['quantity'] ?? 1));
        cartAdd($productId, $qty);
        redirect('cart.php');
    }
}

// Ähnliche Produkte (gleiche Kategorie, nicht dasselbe Produkt)
$stmt2 = $db->prepare('
    SELECT * FROM products 
    WHERE category_id = ? AND id != ?
    LIMIT 3
');
$stmt2->execute([$product['category_id'], $productId]);
$related = $stmt2->fetchAll();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($product['name']) ?> – PhpShop</title>
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
            <a href="index.php">Alle Produkte</a>
        </nav>
        <form action="search.php" method="GET" class="search-form">
            <input type="search" name="q" placeholder="Suchen…" class="search-input">
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

    <!-- Breadcrumb -->
    <nav class="breadcrumb">
        <a href="index.php">Shop</a>
        <span>›</span>
        <a href="index.php?kategorie=<?= h($product['category_slug']) ?>"><?= h($product['category_name']) ?></a>
        <span>›</span>
        <span><?= h($product['name']) ?></span>
    </nav>

    <!-- Produktdetail -->
    <div class="product-detail">
        <div class="product-detail-img">
            <img src="<?= h($product['image_url']) ?>" alt="<?= h($product['name']) ?>">
        </div>
        <div class="product-detail-info">
            <span class="product-category"><?= h($product['category_name']) ?></span>
            <h1><?= h($product['name']) ?></h1>
            <p class="product-detail-desc"><?= h($product['description']) ?></p>
            
            <div class="product-detail-price"><?= formatPrice($product['price']) ?></div>
            
            <div class="stock-info">
                <?php if ($product['stock'] > 0): ?>
                    <span class="in-stock">✓ Auf Lager (<?= $product['stock'] ?> verfügbar)</span>
                <?php else: ?>
                    <span class="out-of-stock">✗ Nicht vorrätig</span>
                <?php endif; ?>
            </div>

            <?php if ($product['stock'] > 0): ?>
            <form method="POST" action="" class="add-to-cart-form">
                <input type="hidden" name="action" value="add">
                <div class="qty-row">
                    <label for="quantity">Menge:</label>
                    <input type="number" name="quantity" id="quantity" 
                           value="1" min="1" max="<?= $product['stock'] ?>"
                           class="qty-input">
                </div>
                <button type="submit" class="btn btn-primary btn-large">
                    🛒 In den Warenkorb
                </button>
            </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Ähnliche Produkte -->
    <?php if (!empty($related)): ?>
    <section class="related-products">
        <h2>Weitere Produkte aus <?= h($product['category_name']) ?></h2>
        <div class="product-grid product-grid--small">
            <?php foreach ($related as $rel): ?>
            <div class="product-card">
                <a href="product.php?id=<?= $rel['id'] ?>">
                    <img src="<?= h($rel['image_url']) ?>" alt="<?= h($rel['name']) ?>" class="product-img">
                </a>
                <div class="product-info">
                    <h3 class="product-name">
                        <a href="product.php?id=<?= $rel['id'] ?>"><?= h($rel['name']) ?></a>
                    </h3>
                    <div class="product-footer">
                        <span class="product-price"><?= formatPrice($rel['price']) ?></span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

</main>

<footer class="site-footer">
    <div class="container">
        <p>PhpShop – Gebaut mit PHP 8 & SQLite</p>
    </div>
</footer>

</body>
</html>
