<?php require_once __DIR__ . '/includes/header.php'; ?>

<div style="margin-bottom: 20px;">
    <h3 style="font-family: var(--font-heading); color: var(--accent);">Registered Users</h3>
</div>

<div class="admin-table-container">
    <table class="admin-table" id="usersTable">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Joined Date</th>
                <th>Role</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <tr><td colspan="6" style="text-align:center;">Loading...</td></tr>
        </tbody>
    </table>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        document.getElementById('pageTitle').textContent = 'Manage Users';
        fetchUsers();
    });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
