<?php
require_once __DIR__ . '/includes/header.php';
?>
<div class="stats-grid" id="dashboardStats">
    <div class="stat-card">
        <h4>Total Products</h4>
        <p id="statProducts">0</p>
    </div>
    <div class="stat-card">
        <h4>Total Stock</h4>
        <p id="statStock">0</p>
    </div>
    <div class="stat-card">
        <h4>Pending Orders</h4>
        <p id="statPending">0</p>
    </div>
    <div class="stat-card">
        <h4>Approved Orders</h4>
        <p id="statApproved">0</p>
    </div>
    <div class="stat-card">
        <h4>Completed Orders</h4>
        <p id="statCompleted">0</p>
    </div>
    <div class="stat-card">
        <h4>Total Customers</h4>
        <p id="statCustomers">0</p>
    </div>
</div>

<h3 style="font-family: var(--font-heading); margin-bottom: 15px; color: var(--accent);">Recent Orders</h3>
<div class="admin-table-container">
    <table class="admin-table" id="recentOrdersTable">
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Customer</th>
                <th>Date</th>
                <th>Status</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            <tr><td colspan="5" style="text-align:center;">Loading...</td></tr>
        </tbody>
    </table>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        document.getElementById('pageTitle').textContent = 'Dashboard Overview';
        fetchDashboardStats();
    });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
