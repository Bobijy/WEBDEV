// Admin dashboard script: handles products, orders, user roles, and statistics

(function () {
    'use strict';

    // GLOBAL LUXURY MODAL SYSTEM (Message, Alert, Confirm)
    window.MaisonUngod = window.MaisonUngod || {};

    window.MaisonUngod.showModal = function (options) {
        options = options || {};
        return new Promise((resolve) => {
            const title = options.title || 'Notice';
            const message = options.message || '';
            const iconType = options.icon || 'info';
            const confirmText = options.confirmText || 'OK';
            const cancelText = options.cancelText || null;
            const isDanger = !!options.isDanger;

            let overlay = document.getElementById('globalMessageOverlay');
            let modal = document.getElementById('globalMessageModal');

            if (!overlay) {
                overlay = document.createElement('div');
                overlay.id = 'globalMessageOverlay';
                overlay.className = 'message-overlay';
                document.body.appendChild(overlay);
            }

            if (!modal) {
                modal = document.createElement('div');
                modal.id = 'globalMessageModal';
                modal.className = 'message-modal';
                modal.setAttribute('role', 'dialog');
                modal.setAttribute('aria-modal', 'true');
                document.body.appendChild(modal);
            }

            let iconSvg = '';
            let iconClass = 'message-modal__icon';
            if (iconType === 'danger') {
                iconClass += ' message-modal__icon--danger';
                iconSvg = `<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>`;
            } else if (iconType === 'warning') {
                iconClass += ' message-modal__icon--warning';
                iconSvg = `<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>`;
            } else if (iconType === 'success') {
                iconClass += ' message-modal__icon--success';
                iconSvg = `<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>`;
            } else {
                iconClass += ' message-modal__icon--info';
                iconSvg = `<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>`;
            }

            const confirmBtnClass = isDanger
                ? 'message-modal__btn message-modal__btn--danger'
                : 'message-modal__btn message-modal__btn--primary';

            modal.innerHTML = `
                <button class="message-modal__close" id="globalMessageClose" aria-label="Close">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
                <div class="message-modal__inner">
                    <div class="${iconClass}">
                        ${iconSvg}
                    </div>
                    <h3 class="message-modal__title">${title}</h3>
                    <p class="message-modal__text">${message}</p>
                    <div class="message-modal__actions">
                        ${cancelText ? `<button type="button" class="message-modal__btn message-modal__btn--secondary" id="globalMessageCancel">${cancelText}</button>` : ''}
                        <button type="button" class="${confirmBtnClass}" id="globalMessageConfirm">${confirmText}</button>
                    </div>
                </div>
            `;

            const btnConfirm = modal.querySelector('#globalMessageConfirm');
            const btnCancel = modal.querySelector('#globalMessageCancel');
            const btnClose = modal.querySelector('#globalMessageClose');

            let isClosed = false;
            function cleanupAndClose() {
                if (isClosed) return;
                isClosed = true;
                modal.classList.remove('open');
                overlay.classList.remove('open');
                document.removeEventListener('keydown', handleKeydown);
                overlay.removeEventListener('click', handleOverlayClick);
                document.body.style.overflow = '';
            }

            function handleConfirm() {
                cleanupAndClose();
                if (typeof options.onConfirm === 'function') {
                    options.onConfirm();
                }
                resolve(true);
            }

            function handleCancel() {
                cleanupAndClose();
                if (typeof options.onCancel === 'function') {
                    options.onCancel();
                }
                resolve(false);
            }

            function handleOverlayClick(e) {
                if (e.target === overlay) {
                    handleCancel();
                }
            }

            function handleKeydown(e) {
                if (e.key === 'Escape') {
                    handleCancel();
                } else if (e.key === 'Enter' && (!btnCancel || document.activeElement !== btnCancel)) {
                    handleConfirm();
                }
            }

            btnConfirm.addEventListener('click', handleConfirm);
            if (btnCancel) btnCancel.addEventListener('click', handleCancel);
            if (btnClose) btnClose.addEventListener('click', handleCancel);
            overlay.addEventListener('click', handleOverlayClick);
            document.addEventListener('keydown', handleKeydown);

            // Open modal
            document.body.style.overflow = 'hidden';
            overlay.classList.add('open');
            modal.classList.add('open');
            setTimeout(() => btnConfirm.focus(), 50);
        });
    };

    window.MaisonUngod.showConfirm = function (message, title, options) {
        options = options || {};
        return window.MaisonUngod.showModal({
            title: title || 'Confirmation',
            message: String(message || ''),
            icon: options.icon || (options.isDanger ? 'danger' : 'warning'),
            confirmText: options.confirmText || 'Confirm',
            cancelText: options.cancelText || 'Cancel',
            isDanger: !!options.isDanger
        });
    };

    window.MaisonUngod.showAlert = function (message, title, icon) {
        const msgStr = String(message || '');
        let defaultIcon = icon;
        if (!defaultIcon) {
            const lower = msgStr.toLowerCase();
            if (lower.includes('success') || (title && title.toLowerCase().includes('success'))) {
                defaultIcon = 'success';
            } else if (lower.includes('error') || lower.includes('failed') || (title && title.toLowerCase().includes('error'))) {
                defaultIcon = 'danger';
            } else if (lower.includes('warn')) {
                defaultIcon = 'warning';
            } else {
                defaultIcon = 'info';
            }
        }
        return window.MaisonUngod.showModal({
            title: title || (defaultIcon === 'success' ? 'Success' : (defaultIcon === 'danger' ? 'Error' : 'Notice')),
            message: msgStr,
            icon: defaultIcon,
            confirmText: 'OK',
            cancelText: null
        });
    };

    // Global delete confirmation handler for forms
    window.confirmDelete = function(event, form, message) {
        if (event) {
            event.preventDefault();
            event.stopPropagation();
        }
        window.MaisonUngod.showConfirm(
            message || 'Are you sure you want to delete this product?',
            'Delete Product',
            {
                isDanger: true,
                confirmText: 'Delete',
                cancelText: 'Cancel'
            }
        ).then((confirmed) => {
            if (confirmed && form) {
                HTMLFormElement.prototype.submit.call(form);
            }
        });
        return false;
    };

    // Route native browser alert in admin to custom luxury modal
    window.alert = function (msg) {
        window.MaisonUngod.showAlert(msg);
    };

    // Logout handler
    const logoutBtn = document.getElementById('adminLogoutBtn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', async (e) => {
            e.preventDefault();
            await fetch('../api/logout.php');
            window.location.href = 'login.php';
        });
    }

    // Helper functions for API requests and formatting
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

    // Mobile sidebar toggle
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const adminSidebar = document.getElementById('adminSidebar');
    if (mobileMenuBtn && adminSidebar) {
        mobileMenuBtn.addEventListener('click', () => {
            adminSidebar.classList.toggle('open');
        });
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', (e) => {
            if (window.innerWidth <= 768 && 
                !adminSidebar.contains(e.target) && 
                !mobileMenuBtn.contains(e.target) && 
                adminSidebar.classList.contains('open')) {
                adminSidebar.classList.remove('open');
            }
        });
    }

    // Dashboard statistics and widgets
    window.fetchDashboardStats = async function() {
        const data = await apiCall('dashboard_stats');
        if (!data || !data.success) return;

        if(document.getElementById('statProducts')) document.getElementById('statProducts').textContent = data.stats.total_products;
        if(document.getElementById('statStock')) document.getElementById('statStock').textContent = data.stats.total_stock;
        if(document.getElementById('statPending')) document.getElementById('statPending').textContent = data.stats.pending_orders;
        if(document.getElementById('statApproved')) document.getElementById('statApproved').textContent = data.stats.approved_orders;
        if(document.getElementById('statProcessing')) document.getElementById('statProcessing').textContent = data.stats.processing_orders;
        if(document.getElementById('statShipped')) document.getElementById('statShipped').textContent = data.stats.shipped_orders;
        if(document.getElementById('statCompleted')) document.getElementById('statCompleted').textContent = data.stats.completed_orders;
        if(document.getElementById('statCustomers')) document.getElementById('statCustomers').textContent = data.stats.total_customers;

        const tbody = document.querySelector('#recentOrdersTable tbody');
        if (data.recent_orders.length === 0) {
            tbody.innerHTML = '<tr><td colspan="9" style="text-align:center;">No recent orders.</td></tr>';
        } else {
            let html = '';
            data.recent_orders.forEach(o => {
                html += `
                    <tr>
                        <td style="color:var(--text-muted);">#${String(o.id).padStart(5, '0')}</td>
                        <td>${o.full_name}<br><small style="color:#8A8A8A">${o.email || ''}</small></td>
                        <td style="max-width: 120px; white-space: normal; word-wrap: break-word; line-height: 1.4;"><small>${o.shipping_address || 'N/A'}</small></td>
                        <td style="max-width: 150px; white-space: normal; word-wrap: break-word; line-height: 1.4;"><small>${o.products_list || 'N/A'}</small></td>
                        <td style="color:var(--text-muted);">${formatDate(o.created_at)}</td>
                        <td><span class="badge ${badgeColor(o.status)}">${o.status}</span></td>
                        <td style="color:var(--text-muted);">${o.payment_method || 'N/A'}</td>
                        <td style="font-weight:500;">₱${parseFloat(o.total_amount).toFixed(2)}</td>
                        <td>
                            <button class="action-btn" title="View Options" onclick="window.location.href='orders.php?status=${o.status}'">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/></svg>
                            </button>
                        </td>
                    </tr>
                `;
            });
            tbody.innerHTML = html;
        }

        // Best Selling Products Widget
        const bestSellingList = document.getElementById('bestSellingList');
        if (bestSellingList && data.best_sellers) {
            if (data.best_sellers.length === 0) {
                bestSellingList.innerHTML = '<div style="padding: 24px; text-align: center; color: var(--text-muted);">No sales data available yet.</div>';
            } else {
                let bsHtml = '';
                const maxSold = Math.max(...data.best_sellers.map(p => parseInt(p.total_sold) || 0), 1);
                
                data.best_sellers.forEach(p => {
                    const sold = parseInt(p.total_sold) || 0;
                    const percent = Math.min((sold / maxSold) * 100, 100);
                    const imgSrc = p.image ? `../${p.image}` : '../assets/placeholder.jpg';
                    
                    bsHtml += `
                        <div class="best-selling-item">
                            <img src="${imgSrc}" alt="${p.name}">
                            <div class="bs-info">
                                <div class="bs-name">${p.name}</div>
                                <div class="bs-sold">${sold} Sold</div>
                                <div class="bs-bar">
                                    <div class="bs-progress" style="width: ${percent}%;"></div>
                                </div>
                            </div>
                            <div class="bs-price">₱${parseFloat(p.price).toFixed(2)}</div>
                        </div>
                    `;
                });
                bestSellingList.innerHTML = bsHtml;
            }
        }

        // Notifications
        if (data.notifications) {
            const notifBadge = document.getElementById('notifBadge');
            const notifList = document.getElementById('notificationList');
            if (notifBadge && notifList) {
                if (data.notifications.length > 0) {
                    notifBadge.textContent = data.notifications.length;
                    notifBadge.style.display = 'flex';
                    let notifHtml = '';
                    data.notifications.forEach(n => {
                        const icon = n.type === 'low_stock' 
                            ? `<svg class="text-warning" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>`
                            : `<svg class="text-success" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>`;
                        notifHtml += `
                            <div class="notif-item">
                                <div class="notif-icon">${icon}</div>
                                <div class="notif-content">
                                    <div class="notif-msg">${n.message}</div>
                                    <div class="notif-time">${formatDate(n.time)}</div>
                                </div>
                            </div>
                        `;
                    });
                    notifList.innerHTML = notifHtml;
                } else {
                    notifBadge.style.display = 'none';
                    notifList.innerHTML = '<div style="padding: 1rem; text-align: center; color: var(--text-muted);">No new notifications</div>';
                }
            }
        }

    }

    // Product management
    const editProductModal = document.getElementById('editProductModal');
    const editProductModalOverlay = document.getElementById('editProductModalOverlay');
    const editProductForm = document.getElementById('editProductForm');
    const editModalFormError = document.getElementById('editModalFormError');

    function closeEditProductModal() {
        if (editProductModal) editProductModal.classList.remove('open');
        if (editProductModalOverlay) editProductModalOverlay.classList.remove('open');
        if (editProductForm) editProductForm.reset();
        if (editModalFormError) editModalFormError.style.display = 'none';
        document.body.style.overflow = '';
    }

    if (document.getElementById('closeEditProductModal')) {
        document.getElementById('closeEditProductModal').addEventListener('click', closeEditProductModal);
    }
    
    if (document.getElementById('cancelEditProductBtn')) {
        document.getElementById('cancelEditProductBtn').addEventListener('click', closeEditProductModal);
    }

    function openAddProductModal() {
        if (!editProductModal) return;
        if (editProductForm) editProductForm.reset();
        if (editModalFormError) editModalFormError.style.display = 'none';

        const modalTitle = document.getElementById('productModalTitle');
        if (modalTitle) modalTitle.textContent = 'Add New Product';
        const actionInput = document.getElementById('productFormAction');
        if (actionInput) actionInput.value = 'add_product';
        document.getElementById('editProductId').value = '';
        document.getElementById('editProductStatus').value = 'Active';
        document.getElementById('editProductCat').value = 'Uncategorized';
        const saveBtn = document.getElementById('saveEditProductBtn');
        if (saveBtn) saveBtn.textContent = 'Add Product';

        const imgPreviewWrap = document.getElementById('editProductImgPreviewWrap');
        if (imgPreviewWrap) imgPreviewWrap.style.display = 'none';
        const imgLabel = document.getElementById('editProductImageLabel');
        if (imgLabel) imgLabel.innerHTML = 'Product Image <small>(optional, Max 5MB)</small>';

        editProductModal.classList.add('open');
        editProductModalOverlay.classList.add('open');
        document.body.style.overflow = 'hidden';
    }

    window.openAddProductModal = openAddProductModal;
    window.closeEditProductModal = closeEditProductModal;

    if (document.getElementById('openAddProductBtn')) {
        document.getElementById('openAddProductBtn').addEventListener('click', openAddProductModal);
    }

    window.editProduct = function(id) {
        if (typeof productsList === 'undefined') return;
        const p = productsList.find(x => x.id == id);
        if(!p) return;

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

        editProductModal.classList.add('open');
        editProductModalOverlay.classList.add('open');
        document.body.style.overflow = 'hidden';
    }

    if (editProductForm && !editProductForm.dataset.bound) {
        editProductForm.dataset.bound = "true";
        editProductForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = document.getElementById('saveEditProductBtn');
            const originalText = btn.innerHTML;
            const actionInput = document.getElementById('productFormAction');
            const isAdd = actionInput && actionInput.value === 'add_product';

            btn.innerHTML = isAdd ? 'Creating...' : 'Updating...';
            btn.disabled = true;
            editModalFormError.style.display = 'none';

            const formData = new FormData(editProductForm);

            try {
                const response = await fetch('../api/admin.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();
                
                if (data.success) {
                    closeEditProductModal();
                    window.MaisonUngod.showAlert(isAdd ? 'Product created successfully!' : 'Product updated successfully!', 'Success', 'success');
                    fetchProducts();
                } else {
                    let errHtml = '<strong>Error:</strong><br>';
                    if (data.errors) {
                        for (let field in data.errors) {
                            errHtml += `- ${data.errors[field]}<br>`;
                        }
                    } else {
                        errHtml += data.message || 'An unknown error occurred.';
                    }
                    editModalFormError.innerHTML = errHtml;
                    editModalFormError.style.display = 'block';
                }
            } catch (err) {
                editModalFormError.innerHTML = '<strong>Connection Error:</strong> Could not connect to server.';
                editModalFormError.style.display = 'block';
            } finally {
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        });
    }

    window.fetchProducts = async function() {
        const data = await apiCall('get_products');
        if (!data || !data.success) return;
        
        productsList = data.products; // Update global array
        const tbody = document.getElementById('productsTableBody');
        if (!tbody) return;
        
        if (data.products.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;">No products found.</td></tr>';
        } else {
            let html = '';
            data.products.forEach(p => {
                const statusColor = p.status === 'Active' ? '#e2f5ec' : '#f0f0f0';
                const textColor = p.status === 'Active' ? '#1b8b54' : '#666';
                const imgTag = p.image ? `<img src="../${p.image}" alt="Product" style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px;">` : '';
                html += `
                    <tr>
                        <td>${imgTag}</td>
                        <td>${p.name}</td>
                        <td>${p.category}</td>
                        <td>₱${parseFloat(p.price).toFixed(2)}</td>
                        <td>${p.stock}</td>
                        <td>
                            <span style="display:inline-block; padding: 2px 8px; border-radius: 12px; font-size: 12px; background: ${statusColor}; color: ${textColor};">
                                ${p.status}
                            </span>
                        </td>
                        <td>
                            <div style="display:flex; gap: 5px;">
                                <button type="button" class="btn" style="padding: 4px 8px; font-size: 12px;" onclick="editProduct(${p.id})">Edit</button>
                                <form action="product_action.php" method="POST" style="display:inline;" onsubmit="return confirmDelete(event, this, 'Are you sure you want to delete this product?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="${p.id}">
                                    <input type="hidden" name="csrf_token" value="${document.querySelector('meta[name="csrf-token"]').content}">
                                    <button type="submit" class="btn" style="padding: 4px 8px; font-size: 12px; background-color: #dc3545;">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                `;
            });
            tbody.innerHTML = html;
        }
    }

    // Order management
    let ordersList = [];
    window.fetchOrders = async function() {
        let statusQuery = '';
        
        const filterDropdown = document.getElementById('orderStatusFilter');
        if (filterDropdown) {
            statusQuery = filterDropdown.value;
        } else {
            const urlParams = new URLSearchParams(window.location.search);
            statusQuery = urlParams.get('status') || '';
        }

        let action = 'get_orders';
        if (statusQuery) {
            action += '&status=' + encodeURIComponent(statusQuery);
        }

        const data = await apiCall(action);
        if (!data || !data.success) return;
        ordersList = data.orders;

        const tbody = document.querySelector('#ordersTable tbody');
        if (ordersList.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;">No orders found.</td></tr>';
        } else {
            let html = '';
            ordersList.forEach(o => {
                html += `
                    <tr>
                        <td>#${String(o.id).padStart(5, '0')}</td>
                        <td>${o.customer_name}<br><small style="color:#8A8A8A">${o.email}</small></td>
                        <td style="max-width: 120px; white-space: normal; word-wrap: break-word; line-height: 1.4;"><small>${o.shipping_address || 'N/A'}</small></td>
                        <td style="max-width: 150px; white-space: normal; word-wrap: break-word; line-height: 1.4;"><small>${o.products_list || 'N/A'}</small></td>
                        <td>${formatDate(o.created_at)}</td>
                        <td>₱${parseFloat(o.total_amount).toFixed(2)}</td>
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
                closeOrderModal();
                fetchOrders();
            } else {
                alert(data ? data.message : 'Error updating order');
            }
        });
    }

    // Toast notifications - route to luxury modal
    function showToast(message, type = 'success') {
        const oldToast = document.getElementById('adminToast');
        if (oldToast) oldToast.remove();

        const title = type === 'success' ? 'Success' : (type === 'danger' || type === 'error' ? 'Error' : 'Notice');
        window.MaisonUngod.showAlert(message, title, type === 'danger' ? 'danger' : type);
    }

    window.showToast = showToast;

    // User management
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
        const confirmed = await window.MaisonUngod.showConfirm(
            `Are you sure you want to change user #${id} role to ${role}?`,
            'Change User Role',
            {
                confirmText: 'Change Role',
                cancelText: 'Cancel',
                isDanger: role === 'admin'
            }
        );
        if(confirmed) {
            const data = await apiCall('update_user_role', 'POST', { id, role });
            if (data && data.success) {
                fetchUsers();
            } else {
                window.MaisonUngod.showAlert(data ? data.message : 'Error updating role', 'Error');
                fetchUsers(); // reset select
            }
        } else {
            fetchUsers(); // reset select
        }
    }

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            if (editProductModal && editProductModal.classList.contains('open')) {
                closeEditProductModal();
            }

            if (orderModal && orderModal.classList.contains('open')) {
                if (typeof closeOrderModal === 'function') closeOrderModal();
            }
        }
    });

})();