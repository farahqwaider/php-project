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

$errors = [];
$editing = false;
$editId = 0;
$editName = $editDescription = $editPrice = $editStock = $editImage = '';
$editCategoryId = 0;

$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$maxSize = 2 * 1024 * 1024;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id'] ?? 0);
    $categoryId = intval($_POST['category_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $stock = intval($_POST['stock'] ?? 0);

    if (empty($name)) {
        $errors[] = 'اسم المنتج مطلوب.';
    }
    if ($categoryId <= 0) {
        $errors[] = 'يرجى اختيار فئة.';
    }
    if ($price <= 0) {
        $errors[] = 'السعر يجب أن يكون أكبر من صفر.';
    }
    if ($stock < 0) {
        $errors[] = 'لا يمكن أن يكون المخزون سالبًا.';
    }

    $imageName = $_POST['existing_image'] ?? '';
    $uploadDir = dirname(__DIR__) . '/uploads/';

    if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['image'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'فشل رفع الصورة. حاول مرة أخرى.';
        } elseif (!in_array($file['type'], $allowedTypes)) {
            $errors[] = 'يُسمح فقط بصور JPG و PNG و GIF و WEBP.';
        } elseif ($file['size'] > $maxSize) {
            $errors[] = 'حجم الصورة يجب ألا يتجاوز 2 ميجابايت.';
        } else {
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $newFileName = uniqid('product_', true) . '.' . strtolower($extension);
            $destination = $uploadDir . $newFileName;

            if (move_uploaded_file($file['tmp_name'], $destination)) {
                if ($id > 0 && !empty($imageName) && file_exists($uploadDir . $imageName)) {
                    unlink($uploadDir . $imageName);
                }
                $imageName = $newFileName;
            } else {
                $errors[] = 'فشل حفظ الصورة المرفوعة.';
            }
        }
    }

    if (empty($errors)) {
        if ($id > 0) {
            $stmt = $conn->prepare('UPDATE products SET category_id = ?, name = ?, description = ?, price = ?, stock = ?, image = ? WHERE id = ?');
            $stmt->bind_param('issdssi', $categoryId, $name, $description, $price, $stock, $imageName, $id);
            if ($stmt->execute()) {
                setFlash('success', 'تم تحديث المنتج بنجاح.');
                redirect('products.php');
            } else {
                $errors[] = 'تعذر تحديث المنتج.';
            }
            $stmt->close();
        } else {
            $stmt = $conn->prepare('INSERT INTO products (category_id, name, description, price, stock, image) VALUES (?, ?, ?, ?, ?, ?)');
            $stmt->bind_param('issdis', $categoryId, $name, $description, $price, $stock, $imageName);
            if ($stmt->execute()) {
                setFlash('success', 'تم إنشاء المنتج بنجاح.');
                redirect('products.php');
            } else {
                $errors[] = 'تعذر إنشاء المنتج.';
            }
            $stmt->close();
        }
    } else {
        $editing = ($id > 0);
        $editId = $id;
        $editCategoryId = $categoryId;
        $editName = $name;
        $editDescription = $description;
        $editPrice = $price;
        $editStock = $stock;
        $editImage = $imageName;
    }
}

if (isset($_GET['delete'])) {
    $deleteId = intval($_GET['delete']);

    $stmt = $conn->prepare('SELECT image FROM products WHERE id = ?');
    $stmt->bind_param('i', $deleteId);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();
    $stmt->close();

    $stmt = $conn->prepare('DELETE FROM products WHERE id = ?');
    $stmt->bind_param('i', $deleteId);
    $stmt->execute();
    $stmt->close();

    if (!empty($product['image']) && file_exists(dirname(__DIR__) . '/uploads/' . $product['image'])) {
        unlink(dirname(__DIR__) . '/uploads/' . $product['image']);
    }

    setFlash('success', 'تم حذف المنتج بنجاح.');
    redirect('products.php');
}

require_once '../includes/header.php';

$categories = $conn->query('SELECT id, name FROM categories ORDER BY name ASC');
$categoryList = [];
while ($c = $categories->fetch_assoc()) {
    $categoryList[$c['id']] = $c['name'];
}

$products = $conn->query('SELECT p.*, c.name AS category_name FROM products p JOIN categories c ON p.category_id = c.id ORDER BY p.id DESC');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0">إدارة المنتجات</h2>
    <button type="button" class="btn btn-primary" id="addProductBtn" data-bs-toggle="modal" data-bs-target="#productModal">
        <i class="bi bi-plus-lg"></i> إضافة منتج جديد
    </button>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
                <li><?php echo clean($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-body">
        <h5 class="card-title">قائمة المنتجات</h5>
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>الصورة</th>
                        <th>الاسم</th>
                        <th>الفئة</th>
                        <th>السعر</th>
                        <th>المخزون</th>
                        <th style="width: 150px;">إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($product = $products->fetch_assoc()): ?>
                        <tr>
                            <td><img src="<?php echo productImage($product['image']); ?>" alt="<?php echo clean($product['name']); ?>" width="80" class="rounded"></td>
                            <td><?php echo clean($product['name']); ?></td>
                            <td><?php echo clean($product['category_name']); ?></td>
                            <td>$<?php echo number_format($product['price'], 2); ?></td>
                            <td><?php echo (int)$product['stock']; ?></td>
                            <td>
                                <button type="button" class="btn btn-sm btn-outline-primary edit-product mb-1"
                                        data-bs-toggle="modal" data-bs-target="#productModal"
                                        data-id="<?php echo (int)$product['id']; ?>"
                                        data-category-id="<?php echo (int)$product['category_id']; ?>"
                                        data-name="<?php echo clean($product['name']); ?>"
                                        data-description="<?php echo clean($product['description']); ?>"
                                        data-price="<?php echo clean($product['price']); ?>"
                                        data-stock="<?php echo (int)$product['stock']; ?>"
                                        data-image="<?php echo clean($product['image']); ?>">
                                    <i class="bi bi-pencil"></i> تعديل
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger delete-product mb-1"
                                        data-bs-toggle="modal" data-bs-target="#deleteModal"
                                        data-id="<?php echo (int)$product['id']; ?>"
                                        data-name="<?php echo clean($product['name']); ?>">
                                    <i class="bi bi-trash"></i> حذف
                                </button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="productModal" tabindex="-1" aria-labelledby="productModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="products.php" enctype="multipart/form-data" id="productForm" novalidate>
                <div class="modal-header">
                    <h5 class="modal-title" id="productModalLabel"><?php echo $editing ? 'تعديل المنتج' : 'إضافة منتج جديد'; ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="prodId" value="<?php echo (int)$editId; ?>">
                    <input type="hidden" name="existing_image" id="prodExistingImage" value="<?php echo clean($editImage); ?>">

                    <div class="mb-3">
                        <label for="prodCategory" class="form-label">الفئة</label>
                        <select name="category_id" id="prodCategory" class="form-select" required>
                            <option value="">-- اختر الفئة --</option>
                            <?php foreach ($categoryList as $cid => $cname): ?>
                                <option value="<?php echo (int)$cid; ?>" <?php echo ($editCategoryId == $cid) ? 'selected' : ''; ?>>
                                    <?php echo clean($cname); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="prodName" class="form-label">اسم المنتج</label>
                        <input type="text" name="name" id="prodName" class="form-control" required
                               value="<?php echo clean($editName); ?>">
                    </div>

                    <div class="mb-3">
                        <label for="prodDescription" class="form-label">الوصف</label>
                        <textarea name="description" id="prodDescription" rows="3" class="form-control"><?php echo clean($editDescription); ?></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="prodPrice" class="form-label">السعر ($)</label>
                            <input type="number" step="0.01" min="0.01" name="price" id="prodPrice" class="form-control" required
                                   value="<?php echo $editPrice ? number_format($editPrice, 2) : ''; ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="prodStock" class="form-label">كمية المخزون</label>
                            <input type="number" min="0" name="stock" id="prodStock" class="form-control" required
                                   value="<?php echo (int)$editStock; ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="prodImage" class="form-label">صورة المنتج</label>
                        <input type="file" name="image" id="prodImage" class="form-control" accept="image/*">
                        <div class="form-text">JPG أو PNG أو GIF أو WEBP بحد أقصى 2 ميجابايت.</div>
                        <div class="mt-2" id="prodImagePreview">
                            <?php if ($editing && !empty($editImage)): ?>
                                <img src="<?php echo BASE_URL; ?>uploads/<?php echo clean($editImage); ?>" alt="الصورة الحالية" class="img-thumbnail" width="120">
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary" id="prodSubmit"><?php echo $editing ? 'حفظ التعديلات' : 'إضافة'; ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger" id="deleteModalLabel">
                    <i class="bi bi-exclamation-triangle-fill"></i> تأكيد الحذف
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>هل أنت متأكد من حذف المنتج <strong id="deleteItemName"></strong>؟</p>
                <p class="text-danger small mb-0"><i class="bi bi-info-circle"></i> لا يمكن التراجع عن هذا الإجراء، وسيتم حذف الصورة أيضًا.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                <a href="#" class="btn btn-danger" id="deleteConfirmBtn">
                    <i class="bi bi-trash"></i> حذف
                </a>
            </div>
        </div>
    </div>
</div>

<script>
(function(){
    var modalEl = document.getElementById('productModal');
    var titleEl = document.getElementById('productModalLabel');
    var submitEl = document.getElementById('prodSubmit');
    var idEl = document.getElementById('prodId');
    var catEl = document.getElementById('prodCategory');
    var nameEl = document.getElementById('prodName');
    var descEl = document.getElementById('prodDescription');
    var priceEl = document.getElementById('prodPrice');
    var stockEl = document.getElementById('prodStock');
    var existingImageEl = document.getElementById('prodExistingImage');
    var previewEl = document.getElementById('prodImagePreview');

    function resetForm(){
        titleEl.textContent = 'إضافة منتج جديد';
        submitEl.textContent = 'إضافة';
        idEl.value = 0;
        catEl.value = '';
        nameEl.value = '';
        descEl.value = '';
        priceEl.value = '';
        stockEl.value = '';
        existingImageEl.value = '';
        previewEl.innerHTML = '';
    }

    document.getElementById('addProductBtn').addEventListener('click', resetForm);

    document.querySelectorAll('.edit-product').forEach(function(btn){
        btn.addEventListener('click', function(){
            titleEl.textContent = 'تعديل المنتج';
            submitEl.textContent = 'حفظ التعديلات';
            idEl.value = this.dataset.id;
            catEl.value = this.dataset.categoryId;
            nameEl.value = this.dataset.name;
            descEl.value = this.dataset.description;
            priceEl.value = this.dataset.price;
            stockEl.value = this.dataset.stock;
            existingImageEl.value = this.dataset.image;
            if (this.dataset.image) {
                previewEl.innerHTML = '<img src="<?php echo BASE_URL; ?>uploads/' + this.dataset.image + '" alt="الصورة الحالية" class="img-thumbnail" width="120">';
            } else {
                previewEl.innerHTML = '';
            }
        });
    });

    var deleteConfirmBtn = document.getElementById('deleteConfirmBtn');
    var deleteItemName = document.getElementById('deleteItemName');
    document.querySelectorAll('.delete-product').forEach(function(btn){
        btn.addEventListener('click', function(){
            deleteConfirmBtn.href = 'products.php?delete=' + this.dataset.id;
            deleteItemName.textContent = '"' + this.dataset.name + '"';
        });
    });

    <?php if (!empty($errors) || $editing): ?>
    var myModal = new bootstrap.Modal(modalEl);
    myModal.show();
    <?php endif; ?>
})();
</script>

<?php require_once '../includes/footer.php'; ?>
