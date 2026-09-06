<?php
require_once __DIR__ . '/includes/header.php';
?>

<!-- STATS GRID -->
<div class="stats-grid" id="dashboardStats">
    <div class="stat-card">
        <div class="stat-icon purple">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
        </div>
        <div class="stat-info">
            <h4>Total Products</h4>
            <p id="statProducts">0</p>
            <div class="stat-subtext">All Products</div>
        </div>
        <!-- SVG Mini Chart -->
        <svg class="mini-chart" viewBox="0 0 100 30" preserveAspectRatio="none">
            <path d="M0,20 Q10,30 20,15 T40,25 T60,10 T80,20 T100,5" fill="none" stroke="var(--accent)" stroke-width="2" stroke-linecap="round"/>
        </svg>
    </div>

    <div class="stat-card">
        <div class="stat-icon purple">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
        </div>
        <div class="stat-info">
            <h4>Total Stock</h4>
            <p id="statStock">0</p>
            <div class="stat-subtext">In Inventory</div>
        </div>
        <svg class="mini-chart" viewBox="0 0 100 30" preserveAspectRatio="none">
            <path d="M0,15 Q15,5 30,20 T60,10 T80,25 T100,10" fill="none" stroke="var(--accent-hover)" stroke-width="2" stroke-linecap="round"/>
        </svg>
    </div>

    <div class="stat-card">
        <div class="stat-icon purple">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div class="stat-info">
            <h4>Pending Orders</h4>
            <p id="statPending">0</p>
            <div class="stat-subtext">Awaiting Action</div>
        </div>
        <svg class="mini-chart" viewBox="0 0 100 30" preserveAspectRatio="none">
            <path d="M0,25 Q20,20 30,28 T50,15 T70,25 T100,5" fill="none" stroke="var(--accent)" stroke-width="2" stroke-linecap="round"/>
        </svg>
    </div>

    <div class="stat-card">
        <div class="stat-icon green">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div class="stat-info">
            <h4>Approved Orders</h4>
            <p id="statApproved">0</p>
            <div class="stat-subtext">This Month</div>
        </div>
        <svg class="mini-chart" viewBox="0 0 100 30" preserveAspectRatio="none">
            <path d="M0,20 Q10,10 25,20 T50,5 T75,15 T100,0" fill="none" stroke="var(--accent)" stroke-width="2" stroke-linecap="round"/>
        </svg>
    </div>

    <div class="stat-card">
        <div class="stat-icon blue">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
        </div>
        <div class="stat-info">
            <h4>Completed Orders</h4>
            <p id="statCompleted">0</p>
            <div class="stat-subtext">This Month</div>
        </div>
        <svg class="mini-chart" viewBox="0 0 100 30" preserveAspectRatio="none">
            <path d="M0,25 Q15,25 30,15 T50,20 T75,5 T100,10" fill="none" stroke="var(--accent-hover)" stroke-width="2" stroke-linecap="round"/>
        </svg>
    </div>

    <div class="stat-card">
        <div class="stat-icon purple">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
        </div>
        <div class="stat-info">
            <h4>Total Customers</h4>
            <p id="statCustomers">0</p>
            <div class="stat-subtext">Registered Users</div>
        </div>
        <svg class="mini-chart" viewBox="0 0 100 30" preserveAspectRatio="none">
            <path d="M0,20 Q10,30 20,15 T40,25 T60,10 T80,20 T100,5" fill="none" stroke="var(--accent)" stroke-width="2" stroke-linecap="round"/>
        </svg>
    </div>
</div>

<!-- REVENUE STATS GRID -->
<div class="revenue-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 24px; margin-bottom: 30px;">
    <div class="stat-card" style="padding: 20px;">
        <div class="stat-info">
            <div class="stat-subtext" style="margin-bottom: 5px;">Today's Revenue</div>
            <h3 id="revToday" style="font-size: 1.5rem; margin: 0; color: #F5F5F5;">$0.00</h3>
        </div>
    </div>
    <div class="stat-card" style="padding: 20px;">
        <div class="stat-info">
            <div class="stat-subtext" style="margin-bottom: 5px;">Monthly Revenue</div>
            <h3 id="revMonth" style="font-size: 1.5rem; margin: 0; color: #F5F5F5;">$0.00</h3>
        </div>
    </div>
    <div class="stat-card" style="padding: 20px;">
        <div class="stat-info">
            <div class="stat-subtext" style="margin-bottom: 5px;">Yearly Revenue</div>
            <h3 id="revYear" style="font-size: 1.5rem; margin: 0; color: #F5F5F5;">$0.00</h3>
        </div>
    </div>
    <div class="stat-card" style="padding: 20px;">
        <div class="stat-info">
            <div class="stat-subtext" style="margin-bottom: 5px;">Avg Order Value</div>
            <h3 id="revAvg" style="font-size: 1.5rem; margin: 0; color: #F5F5F5;">$0.00</h3>
        </div>
    </div>
</div>

<!-- TABLES & LISTS GRID -->
<div class="tables-grid">
    <!-- Recent Orders -->
    <div class="admin-table-container">
        <div class="table-header">
            <div class="table-title">Recent Orders</div>
            <a href="orders.php" class="table-link">View All Orders <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg></a>
        </div>
        <div style="overflow-x:auto;">
            <table class="admin-table" id="recentOrdersTable">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Payment Method</th>
                        <th>Total</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td colspan="7" style="text-align:center;">Loading...</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Best Selling Products -->
    <div class="admin-table-container">
        <div class="table-header">
            <div class="table-title">Best Selling Products</div>
            <a href="products.php" class="table-link">View All <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg></a>
        </div>
        <div class="best-selling-list" id="bestSellingList">
            <div style="padding: 24px; text-align: center; color: var(--text-muted);">Loading...</div>
        </div>
    </div>
</div>

<script>
    // Initialize Dashboard UI specifically for index.php
    document.addEventListener('DOMContentLoaded', () => {
        const titleEl = document.getElementById('pageTitle');
        if(titleEl) {
            const userName = "<?= htmlspecialchars($admin_name) ?>";
            titleEl.innerHTML = `Welcome back, ${userName}! <span style="font-size:1.2rem;">👋</span>`;
        }
        
        if (typeof window.fetchDashboardStats === 'function') {
            window.fetchDashboardStats();
        }
    });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
