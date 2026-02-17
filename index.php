<?php
require_once 'includes/bootstrap.php';

// Warenkorb-Aktion verarbeiten (POST-Request)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $productId = (int)$_POST['product_id']; // (int) = cast zu Integer, schützt vor SQL-Injection

    if ($_POST['action'] === 'add' && $productId > 0) {
        cartAdd($productId);
        redirect('index.php?added=' . $productId);
    }
}

// Kategorie-Filter (aus URL: ?kategorie=elektronik)
$selectedSlug = $_GET['kategorie'] ?? null;

// Sortierung – whitelist erlaubter Werte (verhindert SQL-Injection bei ORDER BY)
$sortOptions = [
    'name_asc'   => ['p.name ASC',   'Name A–Z'],
    'name_desc'  => ['p.name DESC',  'Name Z–A'],
    'price_asc'  => ['p.price ASC',  'Preis aufsteigend'],
    'price_desc' => ['p.price DESC', 'Preis absteigend'],
];
$selectedSort = $_GET['sort'] ?? 'name_asc';
if (!array_key_exists($selectedSort, $sortOptions)) {
    $selectedSort = 'name_asc';
}
$orderBy = $sortOptions[$selectedSort][0];

$db = getDB();

// Alle Kategorien für Navigation holen
$categories = $db->query('SELECT * FROM categories ORDER BY name')->fetchAll();

// Produkte holen – mit oder ohne Kategorie-Filter
if ($selectedSlug) {
    $stmt = $db->prepare("
        SELECT p.*, c.name AS category_name 
        FROM products p 
        JOIN categories c ON p.category_id = c.id
        WHERE c.slug = ?
        ORDER BY $orderBy
    ");
    $stmt->execute([$selectedSlug]);
} else {
    $stmt = $db->query("
        SELECT p.*, c.name AS category_name 
        FROM products p 
        JOIN categories c ON p.category_id = c.id
        ORDER BY $orderBy
    ");
}
$products = $stmt->fetchAll();

// Erfolgsmeldung wenn Produkt hinzugefügt wurde
$addedId = isset($_GET['added']) ? (int)$_GET['added'] : null;
$addedProduct = null;
if ($addedId) {
    $stmt2 = $db->prepare('SELECT name FROM products WHERE id = ?');
    $stmt2->execute([$addedId]);
    $addedProduct = $stmt2->fetch();
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PhpShop – Startseite</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<!-- HEADER -->
<header class="site-header">
    <div class="container header-inner">
        <a href="index.php" class="logo">
            <span class="logo-icon">⟨/⟩</span>
            PhpShop
        </a>
        <nav class="main-nav">
            <a href="index.php" class="<?= !$selectedSlug ? 'active' : '' ?>">Alle</a>
            <?php foreach ($categories as $cat): ?>
                <a href="index.php?kategorie=<?= h($cat['slug']) ?>" 
                   class="<?= $selectedSlug === $cat['slug'] ? 'active' : '' ?>">
                    <?= h($cat['name']) ?>
                </a>
            <?php endforeach; ?>
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

<!-- MAIN -->
<main class="container">

    <?php if ($addedProduct): ?>
    <div class="alert alert-success">
        ✓ <strong><?= h($addedProduct['name']) ?></strong> wurde zum Warenkorb hinzugefügt.
        <a href="cart.php">Zum Warenkorb →</a>
    </div>
    <?php endif; ?>

    <div class="page-title">
        <h1><?= $selectedSlug ? h(ucfirst($selectedSlug)) : 'Alle Produkte' ?></h1>
        <p><?= count($products) ?> Produkte</p>
    </div>

    <!-- SORT BAR -->
    <div class="sort-bar">
        <label for="sort">Sortieren:</label>
        <form method="GET" action="index.php" id="sort-form">
            <?php if ($selectedSlug): ?>
            <input type="hidden" name="kategorie" value="<?= h($selectedSlug) ?>">
            <?php endif; ?>
            <select name="sort" id="sort" class="sort-select" onchange="document.getElementById('sort-form').submit()">
                <?php foreach ($sortOptions as $key => [$sql, $label]): ?>
                <option value="<?= $key ?>" <?= $selectedSort === $key ? 'selected' : '' ?>>
                    <?= $label ?>
                </option>
                <?php endforeach; ?>
            </select>
        </form>
        <span class="sort-count"><?= count($products) ?> Produkte</span>
    </div>

    <!-- PRODUKT-GRID -->
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
                        <form method="POST" action="index.php">
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

</main>

<footer class="site-footer">
    <div class="container">
        <p>PhpShop – Gebaut mit PHP 8 & SQLite | 
           <a href="https://github.com" target="_blank">GitHub</a>
        </p>
    </div>
</footer>

</body>
</html>
