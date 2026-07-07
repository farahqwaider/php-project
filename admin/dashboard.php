<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/db.php';
require_once '../includes/functions.php';

$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$basePath = dirname($scriptName);
if (strpos($basePath, '/admin') !== false) {
    $basePath = dirname($basePath);
}
define('BASE_URL', rtrim(str_replace('\\', '/', $basePath), '/') . '/');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    setFlash('danger', 'Access denied. Admins only.');
    redirect(BASE_URL . 'index.php');
}

require_once '../includes/header.php';

$usersCount      = $conn->query('SELECT COUNT(*) AS total FROM users')->fetch_assoc()['total'];
$categoriesCount = $conn->query('SELECT COUNT(*) AS total FROM categories')->fetch_assoc()['total'];
$productsCount   = $conn->query('SELECT COUNT(*) AS total FROM products')->fetch_assoc()['total'];
$ordersCount     = $conn->query('SELECT COUNT(*) AS total FROM orders')->fetch_assoc()['total'];
?>

<h2 class="mb-4">لوحة التحكم</h2>

<div class="row g-4">
    <div class="col-md-3">
        <div class="card text-white bg-primary">
            <div class="card-body">
                <h5 class="card-title">المستخدمون</h5>
                <p class="card-text display-6"><?php echo (int)$usersCount; ?></p>
                <a href="#" class="text-white text-decoration-underline">الحسابات المسجلة</a>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-success">
            <div class="card-body">
                <h5 class="card-title">الفئات</h5>
                <p class="card-text display-6"><?php echo (int)$categoriesCount; ?></p>
                <a href="categories.php" class="text-white text-decoration-underline">إدارة الفئات</a>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-warning">
            <div class="card-body">
                <h5 class="card-title">المنتجات</h5>
                <p class="card-text display-6"><?php echo (int)$productsCount; ?></p>
                <a href="products.php" class="text-white text-decoration-underline">إدارة المنتجات</a>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-danger">
            <div class="card-body">
                <h5 class="card-title">الطلبات</h5>
                <p class="card-text display-6"><?php echo (int)$ordersCount; ?></p>
                <span class="text-white">إجمالي الطلبات</span>
            </div>
        </div>
    </div>
</div>

<div class="row mt-5">
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">إجراءات سريعة</h5>
                <a href="categories.php" class="btn btn-outline-success me-2">إدارة الفئات</a>
                <a href="products.php" class="btn btn-outline-warning">إدارة المنتجات</a>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
