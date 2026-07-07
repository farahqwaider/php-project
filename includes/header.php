<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'db.php';
require_once 'functions.php';

$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$basePath = dirname($scriptName);
if (strpos($basePath, '/admin') !== false) {
    $basePath = dirname($basePath);
}
define('BASE_URL', rtrim(str_replace('\\', '/', $basePath), '/') . '/');

$isLoggedIn = isset($_SESSION['user_id']);
$role = $isLoggedIn ? $_SESSION['role'] : '';

$isAdminPage = strpos($scriptName, '/admin/') !== false;

function adminActive($page)
{
    return basename($_SERVER['SCRIPT_NAME']) === $page ? 'active' : 'text-white';
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>متجر أمل</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>

        body { background-color: #f8f9fa; }
        .product-card { transition: transform 0.2s; }
        .product-card:hover { transform: translateY(-5px); box-shadow: 0 8px 16px rgba(0,0,0,0.1); }
        .hero { background: linear-gradient(135deg, #0d6efd, #6610f2); color: #fff; padding: 3rem 1rem; border-radius: 0.5rem; margin-bottom: 2rem; }

        .admin-sidebar {
            width: 280px;
            min-height: 100vh;
        }
        .admin-sidebar .nav-link {
            color: #fff;
        }
        .admin-sidebar .nav-link .bi {
            width: 16px;
            height: 16px;
        }
        @media (max-width: 991.98px) {
            .admin-sidebar {
                width: 100%;
                min-height: auto;
            }
        }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">

    <?php if (!$isAdminPage): ?>

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="<?php echo BASE_URL; ?>index.php"><i class="bi bi-shop"></i> متجر أمل</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>index.php">الرئيسية</a></li>
                    <?php if ($isLoggedIn && $role === 'admin'): ?>
                        <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>admin/dashboard.php">لوحة التحكم</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>admin/categories.php">الفئات</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>admin/products.php">المنتجات</a></li>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav">
                    <?php if ($isLoggedIn): ?>
                        <li class="nav-item"><span class="nav-link">مرحبًا، <?php echo clean($_SESSION['name']); ?></span></li>
                        <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>logout.php"><i class="bi bi-box-arrow-left"></i> تسجيل الخروج</a></li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>login.php">تسجيل الدخول</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>register.php">إنشاء حساب</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    <?php endif; ?>

    <div class="container-fluid <?php echo $isAdminPage ? 'p-0' : 'mt-4 mb-5'; ?>">

        <?php if (!$isAdminPage) echo showFlash(); ?>

        <?php if ($isAdminPage): ?>
    </div>

    <div class="d-flex flex-nowrap">
        <div class="d-flex flex-column flex-shrink-0 p-3 text-bg-dark admin-sidebar">
            <a href="<?php echo BASE_URL; ?>index.php" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto text-white text-decoration-none">
                <i class="bi bi-bootstrap-fill me-2 fs-4"></i>
                <span class="fs-4">متجر أمل</span>
            </a>
            <hr>
            <ul class="nav nav-pills flex-column mb-auto">
                <li class="nav-item">
                    <a href="<?php echo BASE_URL; ?>admin/dashboard.php" class="nav-link <?php echo adminActive('dashboard.php'); ?>" <?php echo adminActive('dashboard.php') === 'active' ? 'aria-current="page"' : ''; ?>>
                        <i class="bi bi-speedometer2 me-2"></i>
                        لوحة التحكم
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?php echo BASE_URL; ?>admin/categories.php" class="nav-link <?php echo adminActive('categories.php'); ?>" <?php echo adminActive('categories.php') === 'active' ? 'aria-current="page"' : ''; ?>>
                        <i class="bi bi-tags me-2"></i>
                        الفئات
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?php echo BASE_URL; ?>admin/products.php" class="nav-link <?php echo adminActive('products.php'); ?>" <?php echo adminActive('products.php') === 'active' ? 'aria-current="page"' : ''; ?>>
                        <i class="bi bi-box-seam me-2"></i>
                        المنتجات
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?php echo BASE_URL; ?>index.php" class="nav-link text-white">
                        <i class="bi bi-shop me-2"></i>
                        المتجر
                    </a>
                </li>
            </ul>
            <hr>
            <div class="dropdown">
                <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-person-circle rounded-circle me-2 fs-5"></i>
                    <strong><?php echo clean($_SESSION['name'] ?? 'Admin'); ?></strong>
                </a>
                <ul class="dropdown-menu dropdown-menu-dark text-small shadow">
                    <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>index.php">عرض المتجر</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>logout.php">تسجيل الخروج</a></li>
                </ul>
            </div>
        </div>

        <main class="flex-grow-1 p-4">
            <?php echo showFlash(); ?>
    <?php endif; ?>
