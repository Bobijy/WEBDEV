<?php 
require_once __DIR__ . '/includes/header.php'; 
require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../database/helpers.php';

// Fetch products from database natively instead of via AJAX
$products = db_fetch_all($pdo, 'SELECT * FROM products ORDER BY id DESC');

// Display flash messages
$msg = $_SESSION['msg'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['msg'], $_SESSION['error']);
?>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 20px;">
    <h3 style="font-family: var(--font-heading); color: var(--accent);">Product Catalog</h3>
</div>

<?php if ($msg): ?>
    <div style="background-color: #d4edda; color: #155724; padding: 10px; margin-bottom: 20px; border-radius: 4px; border: 1px solid #c3e6cb;">
        <?= htmlspecialchars($msg) ?>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div style="background-color: #f8d7da; color: #721c24; padding: 10px; margin-bottom: 20px; border-radius: 4px; border: 1px solid #f5c6cb;">
        <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<div class="admin-table-container">
    <table class="admin-table" id="productsTable">
        <thead>
            <tr>
                <th>Image</th>
                <th>Name</th>
                <th>Category</th>
                <th>Price</th>
                <th>Stock</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody id="productsTableBody">
            <?php if (count($products) > 0): ?>
                <?php foreach ($products as $p): ?>
                    <tr>
                        <td><img src="../<?= htmlspecialchars($p['image']) ?>" alt="Product" style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px;"></td>
                        <td><?= htmlspecialchars($p['name']) ?></td>
                        <td><?= htmlspecialchars($p['category']) ?></td>
                        <td>₱<?= number_format($p['price'], 2) ?></td>
                        <td><?= htmlspecialchars((string)$p['stock']) ?></td>
                        <td>
                            <span style="display:inline-block; padding: 2px 8px; border-radius: 12px; font-size: 12px; background: <?= $p['status'] === 'Active' ? '#e2f5ec' : '#f0f0f0' ?>; color: <?= $p['status'] === 'Active' ? '#1b8b54' : '#666' ?>;">
                                <?= htmlspecialchars($p['status']) ?>
                            </span>
                        </td>
                        <td>
                            <div style="display:flex; gap: 5px;">
                                <button type="button" class="btn" style="padding: 4px 8px; font-size: 12px;" onclick="editProduct(<?= $p['id'] ?>)">Edit</button>
                                <form action="product_action.php" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this product?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                    <input type="hidden" name="csrf_token" value="<?= CSRF::generate() ?>">
                                    <button type="submit" class="btn" style="padding: 4px 8px; font-size: 12px; background-color: #dc3545;">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" style="text-align:center;">No products found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>




<!-- AJAX Edit Product Modal -->
<div class="modal-overlay" id="editProductModalOverlay"></div>
<div class="admin-modal" id="editProductModal" style="width: 100%; max-width: 600px;">
    <div class="modal-header">
        <div class="modal-title">Edit Product</div>
        <button class="modal-close" id="closeEditProductModal">&times;</button>
    </div>
    <div class="modal-body">
        <form id="editProductForm" enctype="multipart/form-data">
            <!-- CSRF Token -->
            <input type="hidden" name="csrf_token" value="<?= CSRF::generate() ?>">
            <input type="hidden" name="action" value="edit_product">
            <input type="hidden" name="id" id="editProductId">

            <div id="editModalFormError" style="display:none; background-color: #f8d7da; color: #721c24; padding: 10px; margin-bottom: 20px; border-radius: 4px; border: 1px solid #f5c6cb; font-size: 13px;"></div>

            <div class="form-group">
                <label>Product Name <span style="color:red;">*</span></label>
                <input type="text" name="name" id="editProductName" class="form-control" required>
            </div>

            <div class="form-group">
                <label>Description</label>
                <textarea name="description" id="editProductDesc" class="form-control" rows="3"></textarea>
            </div>

            <div style="display:flex; gap:15px;">
                <div class="form-group" style="flex:1;">
                    <label>Category <span style="color:red;">*</span></label>
                    <select name="category" id="editProductCat" class="form-control" required>
                        <option value="Pour Homme">Pour Homme</option>
                        <option value="Pour Femme">Pour Femme</option>
                        <option value="Unisex">Unisex</option>
                        <option value="Uncategorized">Uncategorized</option>
                    </select>
                </div>
                <div class="form-group" style="flex:1;">
                    <label>Brand</label>
                    <input type="text" name="brand" id="editProductBrand" class="form-control" placeholder="e.g. Maison Ungod">
                </div>
            </div>

            <div style="display:flex; gap:15px;">
                <div class="form-group" style="flex:1;">
                    <label>Price (₱) <span style="color:red;">*</span></label>
                    <input type="number" step="0.01" name="price" id="editProductPrice" class="form-control" required>
                </div>
                <div class="form-group" style="flex:1;">
                    <label>Stock Quantity <span style="color:red;">*</span></label>
                    <input type="number" name="stock" id="editProductStock" class="form-control" required min="0">
                </div>
            </div>

            <div class="form-group">
                <label>Status <span style="color:red;">*</span></label>
                <select name="status" id="editProductStatus" class="form-control" required>
                    <option value="Active">Active</option>
                    <option value="Draft">Draft</option>
                    <option value="Hidden">Hidden</option>
                </select>
            </div>

            <div class="form-group">
                <label>Current Image</label>
                <div style="margin-bottom: 10px;">
                    <img id="editProductImgPreview" src="" alt="Product Image" style="width: 80px; height: 80px; object-fit: cover; border-radius: 4px; border: 1px solid var(--border-color); display: none;">
                </div>
                <label>Replace Image <small>(optional, Max 5MB)</small></label>
                <input type="file" name="image" id="editProductImageInput" class="form-control" accept="image/jpeg, image/png, image/webp, image/gif">
            </div>

            <div style="display:flex; gap:10px; margin-top:20px;">
                <button type="submit" class="btn" style="flex:1;" id="saveEditProductBtn">Update Product</button>
                <button type="button" class="btn btn-outline" style="flex:1;" id="cancelEditProductBtn">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
    let productsList = <?= json_encode($products) ?>;
    document.addEventListener('DOMContentLoaded', () => {
        document.getElementById('pageTitle').textContent = 'Manage Products';
    });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
