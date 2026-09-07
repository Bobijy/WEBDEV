<?php require_once __DIR__ . '/includes/header.php'; ?>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 20px;">
    <h3 style="font-family: var(--font-heading); color: var(--accent);">Order Management</h3>
    <div>
        <select id="orderStatusFilter" class="form-control" style="width:200px;">
            <option value="">All Statuses</option>
            <option value="Pending">Pending</option>
            <option value="Approved">Approved</option>
            <option value="Processing">Processing</option>
            <option value="Shipped">Shipped</option>
            <option value="Completed">Completed</option>
            <option value="Cancelled">Cancelled</option>
        </select>
    </div>
</div>

<div class="admin-table-container">
    <table class="admin-table" id="ordersTable">
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Customer</th>
                <th>Address</th>
                <th>Products</th>
                <th>Date</th>
                <th>Total</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <tr><td colspan="8" style="text-align:center;">Loading...</td></tr>
        </tbody>
    </table>
</div>

<!-- Order Status Modal -->
<div class="admin-modal-overlay" id="orderModalOverlay"></div>
<div class="admin-modal" id="orderModal">
    <h3 style="font-family: var(--font-heading); color: var(--accent); margin-bottom: 20px;">Update Order Status</h3>
    <form id="orderForm">
        <input type="hidden" id="orderId" name="id">
        <div class="form-group">
            <label>Customer Name</label>
            <input type="text" id="orderCustomer" class="form-control" disabled>
        </div>
        <div class="form-group">
            <label>Update Status</label>
            <select id="orderStatus" name="status" class="form-control">
                <option value="Pending">Pending</option>
                <option value="Approved">Approved</option>
                <option value="Processing">Processing</option>
                <option value="Shipped">Shipped</option>
                <option value="Completed">Completed</option>
                <option value="Cancelled">Cancelled</option>
            </select>
        </div>
        <div style="display:flex; gap:10px; margin-top:20px;">
            <button type="submit" class="btn" style="flex:1;">Update Status</button>
            <button type="button" class="btn btn-outline" id="closeOrderModal" style="flex:1;">Cancel</button>
        </div>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        document.getElementById('pageTitle').textContent = 'Manage Orders';
        
        const filter = document.getElementById('orderStatusFilter');
        if (filter) {
            const urlParams = new URLSearchParams(window.location.search);
            const status = urlParams.get('status');
            if (status) {
                filter.value = status;
            }
            filter.addEventListener('change', () => {
                fetchOrders();
            });
        }

        fetchOrders();
    });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
