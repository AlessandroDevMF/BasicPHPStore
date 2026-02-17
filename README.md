# ⟨/⟩ PhpShop

Ein Mini-Onlineshop in purem PHP 8 – gebaut um die Grundlagen von Web-Entwicklung, Datenbankanbindung und Session-Management zu verstehen.

## Features

- 🛍️ Produktliste mit Kategorie-Filter
- 📄 Produktdetailseite mit ähnlichen Produkten
- 🛒 Warenkorb mit Session-Speicherung
- ✏️ Menge ändern & Produkte entfernen
- 💾 SQLite-Datenbank via PDO
- 🎨 Dark-Theme Design (kein CSS-Framework)
- 🔒 XSS-Schutz durch htmlspecialchars()
- 🔐 SQL-Injection-Schutz durch Prepared Statements

## Projektstruktur

```
phpshop/
├── index.php           # Produktliste (Startseite)
├── product.php         # Produktdetailseite
├── cart.php            # Warenkorb
├── database.sqlite     # SQLite-Datenbank
├── includes/
│   ├── bootstrap.php   # Session starten, alle includes laden
│   ├── db.php          # PDO-Datenbankverbindung
│   └── cart.php        # Warenkorb-Logik (Session-basiert)
└── assets/
    └── css/
        └── style.css   # Komplettes Stylesheet
```

## Lokale Entwicklung

### Option 1: PHP Built-in Server (einfachste Variante)
```bash
git clone https://github.com/dein-name/phpshop.git
cd phpshop
php -S localhost:8000
# → http://localhost:8000 öffnen
```

### Option 2: Docker / ddev
```bash
# ddev installieren: https://ddev.readthedocs.io
ddev config --project-type=php --php-version=8.2
ddev start
ddev launch
```

### Option 3: XAMPP / MAMP
Den Ordner in `htdocs/` legen und über `http://localhost/phpshop` aufrufen.

## Technische Highlights

### PDO & Prepared Statements
```php
// Sicher – kein SQL-Injection möglich
$stmt = $db->prepare('SELECT * FROM products WHERE id = ?');
$stmt->execute([$productId]);
$product = $stmt->fetch();
```

### Session-basierter Warenkorb
```php
// Warenkorb-Struktur in $_SESSION:
// ['cart'] = [product_id => quantity]
$_SESSION['cart'][3] = 2; // Produkt 3, 2x
```

### XSS-Schutz
```php
// htmlspecialchars() immer wenn User-Daten ausgegeben werden!
echo h($product['name']); // h() ist ein Wrapper um htmlspecialchars()
```

### PRG-Pattern (Post/Redirect/Get)
```php
// Nach jedem POST-Request wird redirectet, damit:
// 1. Kein doppeltes Absenden beim Reload
// 2. Browser-History bleibt sauber
header('Location: cart.php');
exit;
```

## Was als nächstes käme (mit Laravel)

In Laravel würde man dasselbe eleganter lösen:

| Hier (plain PHP)              | Mit Laravel                          |
|-------------------------------|--------------------------------------|
| Manuelle PDO-Verbindung       | Eloquent ORM                         |
| `require_once` überall        | Autoloading via Composer             |
| PHP im HTML                   | Blade-Templates                      |
| Manuelle Sessions             | Laravel Session-Facade               |
| Selbstgebautes Routing        | Routes in `routes/web.php`           |
| Kein Auth                     | Laravel Breeze / Jetstream           |

## Datenbankschema

```sql
CREATE TABLE categories (
    id   INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    slug TEXT NOT NULL UNIQUE
);

CREATE TABLE products (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    category_id INTEGER REFERENCES categories(id),
    name        TEXT NOT NULL,
    description TEXT,
    price       REAL NOT NULL,
    stock       INTEGER DEFAULT 0,
    image_url   TEXT,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

## Auf MySQL umstellen

In `includes/db.php` nur die DSN-Zeile ändern:

```php
// SQLite:
$pdo = new PDO('sqlite:' . DB_PATH);

// MySQL:
$pdo = new PDO('mysql:host=localhost;dbname=phpshop;charset=utf8mb4', 'user', 'password');
```

Der restliche Code bleibt **identisch** – das ist die Stärke von PDO.

---

Gebaut als Lernprojekt für ein Bewerbungsgespräch 🚀
