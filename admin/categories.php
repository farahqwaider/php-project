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
$editName = '';
$editDescription = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if (empty($name)) {
        $errors[] = 'اسم الفئة مطلوب.';
    }

    if (empty($errors)) {
        if ($id > 0) {
            $stmt = $conn->prepare('UPDATE categories SET name = ?, description = ? WHERE id = ?');
            $stmt->bind_param('ssi', $name, $description, $id);
            if ($stmt->execute()) {
                setFlash('success', 'تم تحديث الفئة بنجاح.');
                redirect('categories.php');
            } else {
                $errors[] = 'تعذر تحديث الفئة. قد يكون الاسم موجودًا بالفعل.';
            }
            $stmt->close();
        } else {
            $stmt = $conn->prepare('INSERT INTO categories (name, description) VALUES (?, ?)');
            $stmt->bind_param('ss', $name, $description);
            if ($stmt->execute()) {
                setFlash('success', 'تم إنشاء الفئة بنجاح.');
                redirect('categories.php');
            } else {
                $errors[] = 'تعذر إنشاء الفئة. قد يكون الاسم موجودًا بالفعل.';
            }
            $stmt->close();
        }
    } else {
        if ($id > 0) {
            $editing = true;
        }
        $editId = $id;
        $editName = $name;
        $editDescription = $description;
    }
}

if (isset($_GET['delete'])) {
    $deleteId = intval($_GET['delete']);
    $stmt = $conn->prepare('DELETE FROM categories WHERE id = ?');
    $stmt->bind_param('i', $deleteId);
    if ($stmt->execute()) {
        setFlash('success', 'تم حذف الفئة بنجاح.');
    } else {
        setFlash('danger', 'لا يمكن حذف الفئة لوجود منتجات مرتبطة بها.');
    }
    $stmt->close();
    redirect('categories.php');
}

require_once '../includes/header.php';

$categories = $conn->query('SELECT * FROM categories ORDER BY name ASC');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0">إدارة الفئات</h2>
    <button type="button" class="btn btn-primary" id="addCategoryBtn" data-bs-toggle="modal" data-bs-target="#categoryModal">
        <i class="bi bi-plus-lg"></i> إضافة فئة جديدة
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
        <h5 class="card-title">قائمة الفئات</h5>
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>م</th>
                        <th>الاسم</th>
                        <th>الوصف</th>
                        <th style="width: 150px;">إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($cat = $categories->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo (int)$cat['id']; ?></td>
                            <td><?php echo clean($cat['name']); ?></td>
                            <td><?php echo clean($cat['description']); ?></td>
                            <td>
                                <button type="button" class="btn btn-sm btn-outline-primary edit-cat"
                                        data-bs-toggle="modal" data-bs-target="#categoryModal"
                                        data-id="<?php echo (int)$cat['id']; ?>"
                                        data-name="<?php echo clean($cat['name']); ?>"
                                        data-description="<?php echo clean($cat['description']); ?>">
                                    <i class="bi bi-pencil"></i> تعديل
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger delete-cat"
                                        data-bs-toggle="modal" data-bs-target="#deleteModal"
                                        data-id="<?php echo (int)$cat['id']; ?>"
                                        data-name="<?php echo clean($cat['name']); ?>">
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

<div class="modal fade" id="categoryModal" tabindex="-1" aria-labelledby="categoryModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="categories.php" id="categoryForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="categoryModalLabel"><?php echo $editing ? 'تعديل الفئة' : 'إضافة فئة جديدة'; ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="catId" value="<?php echo (int)$editId; ?>">
                    <div class="mb-3">
                        <label for="catName" class="form-label">اسم الفئة</label>
                        <input type="text" name="name" id="catName" class="form-control" required
                               value="<?php echo clean($editName); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="catDescription" class="form-label">الوصف</label>
                        <textarea name="description" id="catDescription" rows="3" class="form-control"><?php echo clean($editDescription); ?></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary" id="catSubmit"><?php echo $editing ? 'حفظ التعديلات' : 'إضافة'; ?></button>
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
                <p>هل أنت متأكد من حذف الفئة <strong id="deleteItemName"></strong>؟</p>
                <p class="text-danger small mb-0"><i class="bi bi-info-circle"></i> لا يمكن التراجع عن هذا الإجراء.</p>
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
    var modalEl = document.getElementById('categoryModal');
    var titleEl = document.getElementById('categoryModalLabel');
    var submitEl = document.getElementById('catSubmit');
    var idEl = document.getElementById('catId');
    var nameEl = document.getElementById('catName');
    var descEl = document.getElementById('catDescription');

    document.getElementById('addCategoryBtn').addEventListener('click', function(){
        titleEl.textContent = 'إضافة فئة جديدة';
        submitEl.textContent = 'إضافة';
        idEl.value = 0;
        nameEl.value = '';
        descEl.value = '';
    });

    document.querySelectorAll('.edit-cat').forEach(function(btn){
        btn.addEventListener('click', function(){
            titleEl.textContent = 'تعديل الفئة';
            submitEl.textContent = 'حفظ التعديلات';
            idEl.value = this.dataset.id;
            nameEl.value = this.dataset.name;
            descEl.value = this.dataset.description;
        });
    });

    var deleteConfirmBtn = document.getElementById('deleteConfirmBtn');
    var deleteItemName = document.getElementById('deleteItemName');
    document.querySelectorAll('.delete-cat').forEach(function(btn){
        btn.addEventListener('click', function(){
            deleteConfirmBtn.href = 'categories.php?delete=' + this.dataset.id;
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
