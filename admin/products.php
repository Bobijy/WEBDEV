<?php require_once __DIR__ . '/includes/header.php'; ?>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 20px;">
    <h3 style="font-family: var(--font-heading); color: var(--accent);">Product Catalog</h3>
    <button class="btn" id="openAddProductModal">Add Product</button>
</div>

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
        <tbody>
            <tr><td colspan="7" style="text-align:center;">Loading...</td></tr>
        </tbody>
    </table>
</div>

<!-- Product Modal -->
<div class="admin-modal-overlay" id="productModalOverlay"></div>
<div class="admin-modal" id="productModal">
    <h3 style="font-family: var(--font-heading); color: var(--accent); margin-bottom: 20px;" id="productModalTitle">Add Product</h3>
    <form id="productForm">
        <input type="hidden" id="prodId" name="id">
        <div class="form-group">
            <label>Name</label>
            <input type="text" id="prodName" name="name" class="form-control" required>
        </div>
        <div class="form-group">
            <label>Description</label>
            <textarea id="prodDesc" name="description" class="form-control" rows="3"></textarea>
        </div>
        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px;">
            <div class="form-group">
                <label>Price</label>
                <input type="number" step="0.01" id="prodPrice" name="price" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Stock</label>
                <input type="number" id="prodStock" name="stock" class="form-control" required min="0">
            </div>
        </div>
        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px;">
            <div class="form-group">
                <label>Category</label>
                <select id="prodCategory" name="category" class="form-control">
                    <option value="Pour Homme">Pour Homme</option>
                    <option value="Pour Femme">Pour Femme</option>
                    <option value="Unisex">Unisex</option>
                    <option value="Uncategorized">Uncategorized</option>
                </select>
            </div>
            <div class="form-group">
                <label>Status</label>
                <select id="prodStatus" name="status" class="form-control">
                    <option value="Active">Active</option>
                    <option value="Draft">Draft</option>
                </select>
            </div>
        </div>
        <div style="display:flex; gap:10px; margin-top:20px;">
            <button type="submit" class="btn" style="flex:1;">Save Product</button>
            <button type="button" class="btn btn-outline" id="closeProductModal" style="flex:1;">Cancel</button>
        </div>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        document.getElementById('pageTitle').textContent = 'Manage Products';
        fetchProducts();
    });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
