<?php
require_once 'includes/bootstrap.php';

$db = getDB();
$action  = $_GET['action'] ?? 'products';
$success = '';
$error   = '';

// ── Produkt hinzufügen ──────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['form'] === 'add_product') {
    $name        = trim($_POST['name']        ?? '');
    $description = trim($_POST['description'] ?? '');
    $price       = (float)($_POST['price']    ?? 0);
    $stock       = (int)($_POST['stock']      ?? 0);
    $category_id = (int)($_POST['category_id']?? 0);
    $image_url   = trim($_POST['image_url']   ?? '');

    // Einfache Validierung
    if ($name === '') {
        $error = 'Name darf nicht leer sein.';
    } elseif ($price <= 0) {
        $error = 'Preis muss größer als 0 sein.';
    } elseif ($category_id <= 0) {
        $error = 'Bitte eine Kategorie wählen.';
    } else {
        $stmt = $db->prepare('
            INSERT INTO products (name, description, price, stock, category_id, image_url)
            VALUES (?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([$name, $description, $price, $stock, $category_id, $image_url]);
        $success = "Produkt „$name" wurde erfolgreich hinzugefügt!";
    }
}

// ── Produkt löschen ─────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['form'] === 'delete_product') {
    $deleteId = (int)$_POST['product_id'];
    if ($deleteId > 0) {
        $stmt = $db->prepare('DELETE FROM products WHERE id = ?');
        $stmt->execute([$deleteId]);
        $success = 'Produkt wurde gelöscht.';
    }
}

// Daten laden
$products   = $db->query('SELECT p.*, c.name AS category_name FROM products p JOIN categories c ON p.category_id = c.id ORDER BY p.id DESC')->fetchAll();
$categories = $db->query('SELECT * FROM categories ORDER BY name')->fetchAll();
$totalStock = array_sum(array_column($products, 'stock'));
$totalValue = array_sum(array_map(fn($p) => $p['price'] * $p['stock'], $products));
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin – PhpShop</title>
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
            <a href="index.php">← Zurück zum Shop</a>
        </nav>
        <span class="cart-btn" style="cursor:default; opacity:0.6;">⚙️ Admin</span>
    </div>
</header>

<main class="container">
    <div class="page-title">
        <h1>Admin-Bereich</h1>
        <p>Produkte verwalten</p>
    </div>

    <?php if ($success): ?>
    <div class="alert alert-success">✓ <?= h($success) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="alert" style="background:rgba(255,77,77,0.1);border:1px solid rgba(255,77,77,0.3);color:var(--danger)">
        ✗ <?= h($error) ?>
    </div>
    <?php endif; ?>

    <div class="admin-layout">

        <!-- Sidebar Navigation -->
        <nav class="admin-nav">
            <a href="admin.php?action=products" class="<?= $action === 'products' ? 'active' : '' ?>">
                📦 Produkte (<?= count($products) ?>)
            </a>
            <a href="admin.php?action=add" class="<?= $action === 'add' ? 'active' : '' ?>">
                ➕ Produkt hinzufügen
            </a>
            <a href="admin.php?action=stats" class="<?= $action === 'stats' ? 'active' : '' ?>">
                📊 Übersicht
            </a>
            <a href="index.php">🏠 Shop</a>
        </nav>

        <div class="admin-content">

            <?php if ($action === 'stats'): ?>
            <!-- ── STATS ── -->
            <div class="admin-card">
                <h2>Shop-Übersicht</h2>
                <div class="product-grid" style="grid-template-columns: repeat(3, 1fr); gap: 16px;">
                    <div style="background:var(--surface-2);padding:20px;border-radius:var(--radius);text-align:center">
                        <div style="font-family:var(--font-mono);font-size:2rem;color:var(--accent)"><?= count($products) ?></div>
                        <div style="color:var(--text-muted);font-size:0.85rem;margin-top:4px">Produkte</div>
                    </div>
                    <div style="background:var(--surface-2);padding:20px;border-radius:var(--radius);text-align:center">
                        <div style="font-family:var(--font-mono);font-size:2rem;color:var(--accent)"><?= $totalStock ?></div>
                        <div style="color:var(--text-muted);font-size:0.85rem;margin-top:4px">Artikel auf Lager</div>
                    </div>
                    <div style="background:var(--surface-2);padding:20px;border-radius:var(--radius);text-align:center">
                        <div style="font-family:var(--font-mono);font-size:2rem;color:var(--accent)"><?= formatPrice($totalValue) ?></div>
                        <div style="color:var(--text-muted);font-size:0.85rem;margin-top:4px">Lagerwert</div>
                    </div>
                </div>
            </div>

            <?php elseif ($action === 'add'): ?>
            <!-- ── PRODUKT HINZUFÜGEN ── -->
            <div class="admin-card">
                <h2>Neues Produkt hinzufügen</h2>
                <form method="POST" action="admin.php?action=add">
                    <input type="hidden" name="form" value="add_product">
                    <div class="form-grid">
                        <div class="form-group full">
                            <label for="name">Produktname *</label>
                            <input type="text" name="name" id="name" 
                                   value="<?= h($_POST['name'] ?? '') ?>"
                                   placeholder="z.B. Wireless Maus" required>
                        </div>
                        <div class="form-group full">
                            <label for="description">Beschreibung</label>
                            <textarea name="description" id="description" 
                                      placeholder="Produktbeschreibung…"><?= h($_POST['description'] ?? '') ?></textarea>
                        </div>
                        <div class="form-group">
                            <label for="price">Preis (€) *</label>
                            <input type="number" name="price" id="price" 
                                   value="<?= h($_POST['price'] ?? '') ?>"
                                   step="0.01" min="0.01" placeholder="29.99" required>
                        </div>
                        <div class="form-group">
                            <label for="stock">Lagerbestand</label>
                            <input type="number" name="stock" id="stock" 
                                   value="<?= h($_POST['stock'] ?? '0') ?>"
                                   min="0" placeholder="0">
                        </div>
                        <div class="form-group">
                            <label for="category_id">Kategorie *</label>
                            <select name="category_id" id="category_id" required>
                                <option value="">– bitte wählen –</option>
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" 
                                    <?= (($_POST['category_id'] ?? '') == $cat['id']) ? 'selected' : '' ?>>
                                    <?= h($cat['name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="image_url">Bild-URL</label>
                            <input type="url" name="image_url" id="image_url" 
                                   value="<?= h($_POST['image_url'] ?? '') ?>"
                                   placeholder="https://…">
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Produkt speichern</button>
                        <a href="admin.php?action=products" class="btn btn-ghost">Abbrechen</a>
                    </div>
                </form>
            </div>

            <?php else: ?>
            <!-- ── PRODUKT-LISTE ── -->
            <div class="admin-card">
                <h2>Alle Produkte</h2>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Bild</th>
                            <th>Name</th>
                            <th>Kategorie</th>
                            <th>Preis</th>
                            <th>Lager</th>
                            <th>Aktion</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $p): ?>
                        <tr>
                            <td>
                                <img src="<?= h($p['image_url'] ?: 'https://placehold.co/48x48/222/888?text=?') ?>" 
                                     alt="">
                            </td>
                            <td>
                                <a href="product.php?id=<?= $p['id'] ?>" style="color:var(--text)">
                                    <?= h($p['name']) ?>
                                </a>
                            </td>
                            <td><span class="product-category"><?= h($p['category_name']) ?></span></td>
                            <td style="font-family:var(--font-mono)"><?= formatPrice($p['price']) ?></td>
                            <td>
                                <span class="badge <?= $p['stock'] > 0 ? 'badge-green' : 'badge-red' ?>">
                                    <?= $p['stock'] ?>
                                </span>
                            </td>
                            <td>
                                <form method="POST" action="admin.php?action=products"
                                      onsubmit="return confirm('Produkt wirklich löschen?')">
                                    <input type="hidden" name="form" value="delete_product">
                                    <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                    <button type="submit" class="btn btn-ghost" 
                                            style="padding:4px 10px;font-size:0.8rem;color:var(--danger);border-color:var(--danger)">
                                        Löschen
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

        </div>
    </div>
</main>

<footer class="site-footer">
    <div class="container">
        <p>PhpShop Admin – Gebaut mit PHP 8 & SQLite</p>
    </div>
</footer>

</body>
</html>
