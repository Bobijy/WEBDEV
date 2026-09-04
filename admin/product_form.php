<?php
require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../database/helpers.php';
require_once __DIR__ . '/includes/header.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$is_edit = $id > 0;

$product = [
    'name' => '',
    'description' => '',
    'price' => '',
    'stock' => '',
    'category' => 'Uncategorized',
    'status' => 'Active'
];

if ($is_edit) {
    $existing = db_fetch($pdo, 'SELECT * FROM products WHERE id = :id', [':id' => $id]);
    if ($existing) {
        $product = $existing;
    } else {
        $_SESSION['error'] = 'Product not found.';
        header('Location: products.php');
        exit;
    }
}

// Repopulate form with submitted data if validation failed
if (isset($_SESSION['form_data'])) {
    $product = array_merge($product, $_SESSION['form_data']);
    unset($_SESSION['form_data']);
}

$errors = $_SESSION['form_errors'] ?? [];
unset($_SESSION['form_errors']);
?>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 20px;">
    <h3 style="font-family: var(--font-heading); color: var(--accent);">
        <?= $is_edit ? 'Edit Product' : 'Add Product' ?>
    </h3>
    <a href="products.php" class="btn btn-outline">Back to Products</a>
</div>

<div class="admin-modal" style="display: block; position: static; transform: none; width: 100%; max-width: 800px; margin: 0 auto; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
    <form action="product_action.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="<?= $is_edit ? 'update' : 'add' ?>">
        <input type="hidden" name="csrf_token" value="<?= CSRF::generate() ?>">
        <?php if ($is_edit): ?>
            <input type="hidden" name="id" value="<?= htmlspecialchars((string)$id) ?>">
        <?php endif; ?>

        <div class="form-group">
            <label>Name <span style="color:red;">*</span></label>
            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars((string)$product['name']) ?>" required>
            <?php if (isset($errors['name'])): ?>
                <div style="color:red; font-size: 0.85em; margin-top:5px;"><?= htmlspecialchars($errors['name']) ?></div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label>Description</label>
            <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars((string)$product['description']) ?></textarea>
        </div>

        <div class="form-group">
            <label>Product Image <?= $is_edit ? '' : '<span style="color:red;">*</span>' ?></label>
            <input type="file" name="image" class="form-control" accept="image/jpeg, image/png, image/webp, image/gif" <?= $is_edit ? '' : 'required' ?>>
            <?php if (isset($errors['image'])): ?>
                <div style="color:red; font-size: 0.85em; margin-top:5px;"><?= htmlspecialchars($errors['image']) ?></div>
            <?php endif; ?>
        </div>

        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px;">
            <div class="form-group">
                <label>Price (₱) <span style="color:red;">*</span></label>
                <input type="number" step="0.01" name="price" class="form-control" value="<?= htmlspecialchars((string)$product['price']) ?>" required>
                <?php if (isset($errors['price'])): ?>
                    <div style="color:red; font-size: 0.85em; margin-top:5px;"><?= htmlspecialchars($errors['price']) ?></div>
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label>Stock <span style="color:red;">*</span></label>
                <input type="number" name="stock" class="form-control" value="<?= htmlspecialchars((string)$product['stock']) ?>" required min="0">
                <?php if (isset($errors['stock'])): ?>
                    <div style="color:red; font-size: 0.85em; margin-top:5px;"><?= htmlspecialchars($errors['stock']) ?></div>
                <?php endif; ?>
            </div>
        </div>

        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px;">
            <div class="form-group">
                <label>Category <span style="color:red;">*</span></label>
                <select name="category" class="form-control" required>
                    <?php 
                    $categories = ['Pour Homme', 'Pour Femme', 'Unisex', 'Uncategorized'];
                    foreach ($categories as $cat): 
                        $selected = ($product['category'] === $cat) ? 'selected' : '';
                    ?>
                        <option value="<?= htmlspecialchars($cat) ?>" <?= $selected ?>><?= htmlspecialchars($cat) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['category'])): ?>
                    <div style="color:red; font-size: 0.85em; margin-top:5px;"><?= htmlspecialchars($errors['category']) ?></div>
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label>Status <span style="color:red;">*</span></label>
                <select name="status" class="form-control" required>
                    <option value="Active" <?= $product['status'] === 'Active' ? 'selected' : '' ?>>Active</option>
                    <option value="Draft" <?= $product['status'] === 'Draft' ? 'selected' : '' ?>>Draft</option>
                </select>
                <?php if (isset($errors['status'])): ?>
                    <div style="color:red; font-size: 0.85em; margin-top:5px;"><?= htmlspecialchars($errors['status']) ?></div>
                <?php endif; ?>
            </div>
        </div>

        <div style="display:flex; gap:10px; margin-top:20px;">
            <button type="submit" class="btn" style="flex:1;">Save Product</button>
            <a href="products.php" class="btn btn-outline" style="flex:1; text-align:center;">Cancel</a>
        </div>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        document.getElementById('pageTitle').textContent = '<?= $is_edit ? 'Edit Product' : 'Add Product' ?>';
    });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
