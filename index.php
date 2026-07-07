<?php

require_once 'includes/header.php';

$categories = $conn->query('SELECT id, name FROM categories ORDER BY name ASC');

$selectedCategory = isset($_GET['category']) ? intval($_GET['category']) : 0;
if ($selectedCategory > 0) {
    $stmt = $conn->prepare('SELECT p.*, c.name AS category_name FROM products p JOIN categories c ON p.category_id = c.id WHERE p.category_id = ? AND p.stock > 0 ORDER BY p.name ASC');
    $stmt->bind_param('i', $selectedCategory);
    $stmt->execute();
    $products = $stmt->get_result();
    $stmt->close();
} else {
    $products = $conn->query('SELECT p.*, c.name AS category_name FROM products p JOIN categories c ON p.category_id = c.id WHERE p.stock > 0 ORDER BY p.name ASC');
}
?>

<div class="hero text-center">
    <h1 class="display-5 fw-bold">أهلًا بك في متجر أمل</h1>
    <p class="lead">تصفح المنتجات حسب الفئة واطلب بسهولة.</p>
    <?php if (!$isLoggedIn): ?>
        <a href="register.php" class="btn btn-light btn-lg mt-2">إنشاء حساب</a>
        <a href="login.php" class="btn btn-outline-light btn-lg mt-2 ms-2">تسجيل الدخول</a>
    <?php endif; ?>
</div>

<div class="row">

    <div class="col-md-3 mb-4">
        <div class="list-group shadow-sm">
            <a href="index.php" class="list-group-item list-group-item-action <?php echo $selectedCategory === 0 ? 'active' : ''; ?>">
                كل الفئات
            </a>
            <?php while ($cat = $categories->fetch_assoc()): ?>
                <a href="index.php?category=<?php echo (int)$cat['id']; ?>"
                   class="list-group-item list-group-item-action <?php echo $selectedCategory === (int)$cat['id'] ? 'active' : ''; ?>">
                    <?php echo clean($cat['name']); ?>
                </a>
            <?php endwhile; ?>
        </div>
    </div>

    <div class="col-md-9">
        <div class="row g-4">
            <?php if ($products->num_rows === 0): ?>
                <div class="col-12">
                    <div class="alert alert-info">لا توجد منتجات في هذه الفئة حاليًا.</div>
                </div>
            <?php endif; ?>

            <?php while ($product = $products->fetch_assoc()): ?>
                <div class="col-sm-6 col-lg-4">
                    <div class="card h-100 product-card shadow-sm">
                        <img src="<?php echo productImage($product['image']); ?>" class="card-img-top" alt="<?php echo clean($product['name']); ?>" style="height: 200px; object-fit: cover;">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title"><?php echo clean($product['name']); ?></h5>
                            <p class="card-text text-muted small"><?php echo clean($product['category_name']); ?></p>
                            <p class="card-text"><?php echo clean(mb_substr($product['description'], 0, 80, 'UTF-8')) . (mb_strlen($product['description'], 'UTF-8') > 80 ? '...' : ''); ?></p>
                            <div class="mt-auto">
                                <p class="fw-bold text-primary">$<?php echo number_format($product['price'], 2); ?></p>
                                <p class="small text-muted">المخزون: <?php echo (int)$product['stock']; ?></p>

                                <?php if ($isLoggedIn && $role === 'customer'): ?>

                                    <form method="POST" action="place_order.php" class="d-flex align-items-center">
                                        <input type="hidden" name="product_id" value="<?php echo (int)$product['id']; ?>">
                                        <input type="number" name="quantity" value="1" min="1" max="<?php echo (int)$product['stock']; ?>" class="form-control form-control-sm me-2" style="width: 70px;">
                                        <button type="submit" class="btn btn-success btn-sm">طلب</button>
                                    </form>
                                <?php elseif ($isLoggedIn && $role === 'admin'): ?>
                                    <span class="badge bg-secondary">عرض المشرف</span>
                                <?php else: ?>
                                    <a href="login.php" class="btn btn-outline-primary btn-sm">سجّل الدخول للطلب</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
