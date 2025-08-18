<?php 
$page_title = 'Manage Users';
$current_page = 'users';
require_once 'includes/admin_header.php';

// Fetch all users from the database
$users_result = $mysqli->query("SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC");
?>

<header class="main-header">
    <div class="header-welcome">
        <h1>Manage Users</h1>
        <p>View, search, and manage all users in the system.</p>
    </div>
</header>

<section class="admin-table-container">
    <table class="admin-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Joined On</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($users_result->num_rows > 0): ?>
                <?php while($user = $users_result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $user['id']; ?></td>
                        <td><?php echo htmlspecialchars($user['name']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td>
                            <span class="role-tag <?php echo $user['role']; ?>">
                                <?php echo ucfirst($user['role']); ?>
                            </span>
                        </td>
                        <td><?php echo date("d M, Y", strtotime($user['created_at'])); ?></td>
                        <td class="action-buttons">
                            <a href="#" title="View Details"><i class="fas fa-eye"></i></a>
                            <a href="#" title="Edit User"><i class="fas fa-edit"></i></a>
                            <a href="#" title="Delete User" style="color: var(--error-color);"><i class="fas fa-trash-alt"></i></a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6">No users found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</section>

<?php 
require_once 'includes/admin_footer.php';
?>