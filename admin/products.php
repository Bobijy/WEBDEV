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
    <h3 style="font-family: var(--font-heading); color: var(--accent); margin:0;">Product Catalog</h3>
    <div style="display:flex; gap:10px;">
        <button type="button" class="btn" id="openAddProductBtn" onclick="openAddProductModal()" style="display:inline-flex; align-items:center; gap:8px;">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            Add Product
        </button>
    </div>
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
<!-- AJAX Product Modal (Add & Edit) -->
<div class="admin-modal-overlay modal-overlay" id="editProductModalOverlay" onclick="closeEditProductModal()"></div>
<div class="admin-modal" id="editProductModal" style="width: 100%; max-width: 600px;">
    <div class="modal-header">
        <div class="modal-title" id="productModalTitle">Add New Product</div>
        <button class="modal-close" id="closeEditProductModal" type="button" onclick="closeEditProductModal()">&times;</button>
    </div>
    <div class="modal-body">
        <form id="editProductForm" enctype="multipart/form-data">
            <!-- CSRF Token -->
            <input type="hidden" name="csrf_token" value="<?= CSRF::generate() ?>">
            <input type="hidden" name="action" id="productFormAction" value="add_product">
            <input type="hidden" name="id" id="editProductId">

            <div id="editModalFormError" style="display:none; background-color: #f8d7da; color: #721c24; padding: 10px; margin-bottom: 20px; border-radius: 4px; border: 1px solid #f5c6cb; font-size: 13px;"></div>

            <div class="form-group">
                <label>Product Name <span style="color:red;">*</span></label>
                <input type="text" name="name" id="editProductName" class="form-control" required placeholder="e.g. Amber Nuit">
            </div>

            <div class="form-group">
                <label>Description</label>
                <textarea name="description" id="editProductDesc" class="form-control" rows="3" placeholder="Enter product fragrance notes and details..."></textarea>
            </div>

            <div style="display:flex; gap:15px;">
                <div class="form-group" style="flex:1;">
                    <label>Category <span style="color:red;">*</span></label>
                    <select name="category" id="editProductCat" class="form-control" required>
                        <option value="Pour Homme">Pour Homme</option>
                        <option value="Pour Femme">Pour Femme</option>
                        <option value="Unisex">Unisex</option>
                        <option value="Uncategorized" selected>Uncategorized</option>
                    </select>
                </div>
                <div class="form-group" style="flex:1;">
                    <label>Brand</label>
                    <input type="text" name="brand" id="editProductBrand" class="form-control" value="Maison Ungod" placeholder="e.g. Maison Ungod">
                </div>
            </div>

            <div style="display:flex; gap:15px;">
                <div class="form-group" style="flex:1;">
                    <label>Price (₱) <span style="color:red;">*</span></label>
                    <input type="number" step="0.01" name="price" id="editProductPrice" class="form-control" required placeholder="0.00" min="0">
                </div>
                <div class="form-group" style="flex:1;">
                    <label>Stock Quantity <span style="color:red;">*</span></label>
                    <input type="number" name="stock" id="editProductStock" class="form-control" required min="0" value="10">
                </div>
            </div>

            <div class="form-group">
                <label>Status <span style="color:red;">*</span></label>
                <select name="status" id="editProductStatus" class="form-control" required>
                    <option value="Active" selected>Active</option>
                    <option value="Draft">Draft</option>
                    <option value="Hidden">Hidden</option>
                </select>
            </div>

            <div class="form-group">
                <div id="editProductImgPreviewWrap" style="display:none; margin-bottom: 10px;">
                    <label style="display:block; margin-bottom: 5px;">Current Image</label>
                    <img id="editProductImgPreview" src="" alt="Product Image" style="width: 80px; height: 80px; object-fit: cover; border-radius: 4px; border: 1px solid var(--border-color);">
                </div>
                <label id="editProductImageLabel">Product Image <small>(optional, Max 5MB)</small></label>
                <input type="file" name="image" id="editProductImageInput" class="form-control" accept="image/jpeg, image/png, image/webp, image/gif">
            </div>

            <div style="display:flex; gap:10px; margin-top:20px;">
                <button type="submit" class="btn" style="flex:1;" id="saveEditProductBtn">Add Product</button>
                <button type="button" class="btn btn-outline" style="flex:1;" id="cancelEditProductBtn" onclick="closeEditProductModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
    let productsList = <?= json_encode($products) ?>;

    function openAddProductModal() {
        const modal = document.getElementById('editProductModal');
        const overlay = document.getElementById('editProductModalOverlay');
        const form = document.getElementById('editProductForm');
        const err = document.getElementById('editModalFormError');
        if (form) form.reset();
        if (err) err.style.display = 'none';

        const modalTitle = document.getElementById('productModalTitle');
        if (modalTitle) modalTitle.textContent = 'Add New Product';
        const actionInput = document.getElementById('productFormAction');
        if (actionInput) actionInput.value = 'add_product';
        const idInput = document.getElementById('editProductId');
        if (idInput) idInput.value = '';
        const statusSelect = document.getElementById('editProductStatus');
        if (statusSelect) statusSelect.value = 'Active';
        const catSelect = document.getElementById('editProductCat');
        if (catSelect) catSelect.value = 'Uncategorized';
        const brandInput = document.getElementById('editProductBrand');
        if (brandInput) brandInput.value = 'Maison Ungod';
        const stockInput = document.getElementById('editProductStock');
        if (stockInput) stockInput.value = '10';
        const saveBtn = document.getElementById('saveEditProductBtn');
        if (saveBtn) saveBtn.textContent = 'Add Product';

        const imgPreviewWrap = document.getElementById('editProductImgPreviewWrap');
        if (imgPreviewWrap) imgPreviewWrap.style.display = 'none';
        const imgLabel = document.getElementById('editProductImageLabel');
        if (imgLabel) imgLabel.innerHTML = 'Product Image <small>(optional, Max 5MB)</small>';

        if (modal) modal.classList.add('open');
        if (overlay) overlay.classList.add('open');
        document.body.style.overflow = 'hidden';
    }

    function closeEditProductModal() {
        const modal = document.getElementById('editProductModal');
        const overlay = document.getElementById('editProductModalOverlay');
        const form = document.getElementById('editProductForm');
        const err = document.getElementById('editModalFormError');
        if (modal) modal.classList.remove('open');
        if (overlay) overlay.classList.remove('open');
        if (form) form.reset();
        if (err) err.style.display = 'none';
        document.body.style.overflow = '';
    }

    function editProduct(id) {
        if (!productsList) return;
        const p = productsList.find(x => x.id == id);
        if (!p) return;

        const modalTitle = document.getElementById('productModalTitle');
        if (modalTitle) modalTitle.textContent = 'Edit Product';
        const actionInput = document.getElementById('productFormAction');
        if (actionInput) actionInput.value = 'edit_product';
        const saveBtn = document.getElementById('saveEditProductBtn');
        if (saveBtn) saveBtn.textContent = 'Update Product';

        document.getElementById('editProductId').value = p.id;
        document.getElementById('editProductName').value = p.name;
        document.getElementById('editProductDesc').value = p.description || '';
        document.getElementById('editProductCat').value = p.category;
        document.getElementById('editProductBrand').value = p.brand || '';
        document.getElementById('editProductPrice').value = p.price;
        document.getElementById('editProductStock').value = p.stock;
        document.getElementById('editProductStatus').value = p.status;

        const imgPreviewWrap = document.getElementById('editProductImgPreviewWrap');
        const imgPreview = document.getElementById('editProductImgPreview');
        if (p.image) {
            imgPreview.src = '../' + p.image;
            if (imgPreviewWrap) imgPreviewWrap.style.display = 'block';
        } else {
            if (imgPreviewWrap) imgPreviewWrap.style.display = 'none';
        }

        const imgLabel = document.getElementById('editProductImageLabel');
        if (imgLabel) imgLabel.innerHTML = 'Replace Image <small>(optional, Max 5MB)</small>';

        const modal = document.getElementById('editProductModal');
        const overlay = document.getElementById('editProductModalOverlay');
        if (modal) modal.classList.add('open');
        if (overlay) overlay.classList.add('open');
        document.body.style.overflow = 'hidden';
    }

    window.openAddProductModal = openAddProductModal;
    window.closeEditProductModal = closeEditProductModal;
    window.editProduct = editProduct;

    document.addEventListener('DOMContentLoaded', () => {
        const pageTitle = document.getElementById('pageTitle');
        if (pageTitle) pageTitle.textContent = 'Manage Products';

        const form = document.getElementById('editProductForm');
        if (form && !form.dataset.bound) {
            form.dataset.bound = "true";
            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                const btn = document.getElementById('saveEditProductBtn');
                const originalText = btn ? btn.innerHTML : 'Save';
                const actionInput = document.getElementById('productFormAction');
                const isAdd = actionInput && actionInput.value === 'add_product';
                const errDiv = document.getElementById('editModalFormError');

                if (btn) {
                    btn.innerHTML = isAdd ? 'Creating...' : 'Updating...';
                    btn.disabled = true;
                }
                if (errDiv) errDiv.style.display = 'none';

                const formData = new FormData(form);

                try {
                    const response = await fetch('../api/admin.php', {
                        method: 'POST',
                        body: formData
                    });
                    const data = await response.json();

                    if (data.success) {
                        closeEditProductModal();
                        // Reload page to refresh PHP-rendered table and counters
                        window.location.reload();
                    } else {
                        let errHtml = '<strong>Error:</strong><br>';
                        if (data.errors) {
                            for (let field in data.errors) {
                                errHtml += `- ${data.errors[field]}<br>`;
                            }
                        } else {
                            errHtml += data.message || 'An unknown error occurred.';
                        }
                        if (errDiv) {
                            errDiv.innerHTML = errHtml;
                            errDiv.style.display = 'block';
                        }
                    }
                } catch (err) {
                    if (errDiv) {
                        errDiv.innerHTML = '<strong>Connection Error:</strong> Could not connect to server.';
                        errDiv.style.display = 'block';
                    }
                } finally {
                    if (btn) {
                        btn.innerHTML = originalText;
                        btn.disabled = false;
                    }
                }
            });
        }
    });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
