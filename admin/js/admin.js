(function () {
    'use strict';

    // ── LOGOUT ──
    const logoutBtn = document.getElementById('adminLogoutBtn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', async (e) => {
            e.preventDefault();
            await fetch('../api/auth.php?action=logout');
            window.location.href = 'login.php';
        });
    }

    // ── HELPERS ──
    async function apiCall(action, method = 'GET', body = null) {
        const options = { method };
        if (body) {
            if (body instanceof FormData) {
                options.body = body;
                // Add action if not present
                if (!body.has('action')) body.append('action', action);
            } else {
                options.headers = { 'Content-Type': 'application/json' };
                body.action = action;
                options.body = JSON.stringify(body);
            }
        }
        
        const url = method === 'GET' ? `../api/admin.php?action=${action}` : '../api/admin.php';
        const res = await fetch(url, options);
        
        if (res.status === 403) {
            window.location.href = 'login.php';
            return null;
        }
        return await res.json();
    }

    function formatDate(dateString) {
        return new Date(dateString).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
    }

    function badgeColor(status) {
        status = status.toLowerCase();
        if (['active', 'approved', 'completed', 'shipped'].includes(status)) return 'badge-active';
        if (['pending', 'processing'].includes(status)) return 'badge-pending';
        return 'badge-cancelled';
    }

    // ── DASHBOARD ──
    window.fetchDashboardStats = async function() {
        const data = await apiCall('dashboard_stats');
        if (!data || !data.success) return;

        document.getElementById('statProducts').textContent = data.stats.total_products;
        document.getElementById('statStock').textContent = data.stats.total_stock;
        document.getElementById('statPending').textContent = data.stats.pending_orders;
        document.getElementById('statApproved').textContent = data.stats.approved_orders;
        document.getElementById('statCompleted').textContent = data.stats.completed_orders;
        document.getElementById('statCustomers').textContent = data.stats.total_customers;

        const tbody = document.querySelector('#recentOrdersTable tbody');
        if (data.recent_orders.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;">No recent orders.</td></tr>';
        } else {
            let html = '';
            data.recent_orders.forEach(o => {
                html += `
                    <tr>
                        <td>#${String(o.id).padStart(5, '0')}</td>
                        <td>${o.full_name}</td>
                        <td>${formatDate(o.created_at)}</td>
                        <td><span class="badge ${badgeColor(o.status)}">${o.status}</span></td>
                        <td>$${parseFloat(o.total_amount).toFixed(2)}</td>
                    </tr>
                `;
            });
            tbody.innerHTML = html;
        }
    }

    // ── PRODUCTS ──
    let productsList = [];
    window.fetchProducts = async function() {
        const data = await apiCall('get_products');
        if (!data || !data.success) return;
        productsList = data.products;

        const tbody = document.querySelector('#productsTable tbody');
        if (productsList.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;">No products found.</td></tr>';
        } else {
            let html = '';
            productsList.forEach(p => {
                html += `
                    <tr>
                        <td><img src="../${p.image}" alt=""></td>
                        <td>${p.name}</td>
                        <td>${p.category}</td>
                        <td>$${parseFloat(p.price).toFixed(2)}</td>
                        <td>${p.stock}</td>
                        <td><span class="badge ${badgeColor(p.status)}">${p.status}</span></td>
                        <td>
                            <button class="btn btn-sm btn-outline" onclick="editProduct(${p.id})">Edit</button>
                            <button class="btn btn-sm btn-outline" style="color:#e74c3c; border-color:#e74c3c;" onclick="deleteProduct(${p.id})">Delete</button>
                        </td>
                    </tr>
                `;
            });
            tbody.innerHTML = html;
        }
    }

    // Product Modal Logic
    const prodModal = document.getElementById('productModal');
    const prodOverlay = document.getElementById('productModalOverlay');
    const prodForm = document.getElementById('productForm');
    
    if (document.getElementById('openAddProductModal')) {
        document.getElementById('openAddProductModal').addEventListener('click', () => {
            prodForm.reset();
            document.getElementById('prodId').value = '';
            document.getElementById('productModalTitle').textContent = 'Add Product';
            prodModal.classList.add('open');
            prodOverlay.classList.add('open');
            document.body.style.overflow = 'hidden';
        });
    }
    
    function closeProductModal() {
        prodModal.classList.remove('open');
        prodOverlay.classList.remove('open');
        document.body.style.overflow = '';
    }

    if (document.getElementById('closeProductModal')) {
        document.getElementById('closeProductModal').addEventListener('click', closeProductModal);
    }


    window.editProduct = function(id) {
        const p = productsList.find(x => x.id == id);
        if(!p) return;
        
        document.getElementById('prodId').value = p.id;
        document.getElementById('prodName').value = p.name;
        document.getElementById('prodDesc').value = p.description;
        document.getElementById('prodPrice').value = p.price;
        document.getElementById('prodStock').value = p.stock;
        document.getElementById('prodCategory').value = p.category;
        document.getElementById('prodStatus').value = p.status;
        
        document.getElementById('productModalTitle').textContent = 'Edit Product';
        prodModal.classList.add('open');
        prodOverlay.classList.add('open');
        document.body.style.overflow = 'hidden';
    }

    if (prodForm) {
        prodForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(prodForm);
            const action = formData.get('id') ? 'update_product' : 'add_product';
            const data = await apiCall(action, 'POST', formData);
            if (data && data.success) {
                prodModal.classList.remove('open');
                prodOverlay.classList.remove('open');
                fetchProducts();
            } else {
                alert(data ? data.message : 'Error saving product');
            }
        });
    }

    window.deleteProduct = async function(id) {
        if(confirm('Are you sure you want to delete this product?')) {
            const data = await apiCall('delete_product', 'DELETE', { id });
            if (data && data.success) {
                fetchProducts();
            } else {
                alert(data ? data.message : 'Error deleting product');
            }
        }
    }

    // ── ORDERS ──
    let ordersList = [];
    window.fetchOrders = async function() {
        const data = await apiCall('get_orders');
        if (!data || !data.success) return;
        ordersList = data.orders;

        const tbody = document.querySelector('#ordersTable tbody');
        if (ordersList.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;">No orders found.</td></tr>';
        } else {
            let html = '';
            ordersList.forEach(o => {
                html += `
                    <tr>
                        <td>#${String(o.id).padStart(5, '0')}</td>
                        <td>${o.customer_name}<br><small style="color:#8A8A8A">${o.email}</small></td>
                        <td>${formatDate(o.created_at)}</td>
                        <td>$${parseFloat(o.total_amount).toFixed(2)}</td>
                        <td><span class="badge ${badgeColor(o.status)}">${o.status}</span></td>
                        <td>
                            <button class="btn btn-sm btn-outline" onclick="editOrder(${o.id})">Update Status</button>
                        </td>
                    </tr>
                `;
            });
            tbody.innerHTML = html;
        }
    }

    const orderModal = document.getElementById('orderModal');
    const orderOverlay = document.getElementById('orderModalOverlay');
    const orderForm = document.getElementById('orderForm');

    function closeOrderModal() {
        if(orderModal) orderModal.classList.remove('open');
        if(orderOverlay) orderOverlay.classList.remove('open');
        document.body.style.overflow = '';
    }

    if (document.getElementById('closeOrderModal')) {
        document.getElementById('closeOrderModal').addEventListener('click', closeOrderModal);
    }


    window.editOrder = function(id) {
        const o = ordersList.find(x => x.id == id);
        if(!o) return;
        
        document.getElementById('orderId').value = o.id;
        document.getElementById('orderCustomer').value = o.customer_name;
        document.getElementById('orderStatus').value = o.status;
        
        orderModal.classList.add('open');
        orderOverlay.classList.add('open');
        document.body.style.overflow = 'hidden';
    }

    if (orderForm) {
        orderForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(orderForm);
            const data = await apiCall('update_order_status', 'POST', formData);
            if (data && data.success) {
                orderModal.classList.remove('open');
                orderOverlay.classList.remove('open');
                fetchOrders();
            } else {
                alert(data ? data.message : 'Error updating order');
            }
        });
    }

    // ── USERS ──
    let usersList = [];
    window.fetchUsers = async function() {
        const data = await apiCall('get_users');
        if (!data || !data.success) return;
        usersList = data.users;

        const tbody = document.querySelector('#usersTable tbody');
        if (usersList.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;">No users found.</td></tr>';
        } else {
            let html = '';
            usersList.forEach(u => {
                html += `
                    <tr>
                        <td>${u.id}</td>
                        <td>${u.full_name}</td>
                        <td>${u.email}</td>
                        <td>${formatDate(u.created_at)}</td>
                        <td><span class="badge ${u.role === 'admin' ? 'badge-active' : ''}">${u.role}</span></td>
                        <td>
                            <select onchange="updateUserRole(${u.id}, this.value)" class="form-control" style="width: auto; padding: 5px;">
                                <option value="customer" ${u.role === 'customer' ? 'selected' : ''}>Customer</option>
                                <option value="admin" ${u.role === 'admin' ? 'selected' : ''}>Admin</option>
                            </select>
                        </td>
                    </tr>
                `;
            });
            tbody.innerHTML = html;
        }
    }

    window.updateUserRole = async function(id, role) {
        if(confirm(`Are you sure you want to change user ${id} role to ${role}?`)) {
            const data = await apiCall('update_user_role', 'POST', { id, role });
            if (data && data.success) {
                fetchUsers();
            } else {
                alert(data ? data.message : 'Error updating role');
                fetchUsers(); // reset select
            }
        } else {
            fetchUsers(); // reset select
        }
    }

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            if (prodModal && prodModal.classList.contains('open')) {
                if (typeof closeProductModal === 'function') closeProductModal();
            }
            if (orderModal && orderModal.classList.contains('open')) {
                if (typeof closeOrderModal === 'function') closeOrderModal();
            }
        }
    });

})();