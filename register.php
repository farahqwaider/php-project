<?php

require_once 'includes/header.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name  = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (empty($name)) {
        $errors[] = 'الاسم مطلوب.';
    }

    if (empty($email)) {
        $errors[] = 'البريد الإلكتروني مطلوب.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'يرجى إدخال بريد إلكتروني صحيح.';
    }

    if (empty($pass)) {
        $errors[] = 'كلمة المرور مطلوبة.';
    } elseif (strlen($pass) < 6) {
        $errors[] = 'كلمة المرور يجب أن تكون 6 أحرف على الأقل.';
    }

    if ($pass !== $confirm) {
        $errors[] = 'تأكيد كلمة المرور غير مطابق.';
    }

    if (empty($errors)) {

        $stmt = $conn->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $errors[] = 'هذا البريد الإلكتروني مسجّل بالفعل. يرجى تسجيل الدخول.';
        } else {

            $hashedPassword = password_hash($pass, PASSWORD_DEFAULT);

            $insert = $conn->prepare('INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, "customer")');
            $insert->bind_param('sss', $name, $email, $hashedPassword);

            if ($insert->execute()) {

                setFlash('success', 'تم إنشاء الحساب بنجاح! يرجى تسجيل الدخول.');
                redirect('login.php');
            } else {
                $errors[] = 'حدث خطأ ما. حاول مرة أخرى.';
            }
            $insert->close();
        }
        $stmt->close();
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card shadow-sm">
            <div class="card-body">
                <h3 class="card-title text-center mb-4">إنشاء حساب</h3>

                <?php if (!empty($errors)): ?>

                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo clean($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" action="register.php" novalidate>
                    <div class="mb-3">
                        <label for="name" class="form-label">الاسم الكامل</label>
                        <input type="text" name="name" id="name" class="form-control" required
                               value="<?php echo isset($_POST['name']) ? clean($_POST['name']) : ''; ?>">
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">البريد الإلكتروني</label>
                        <input type="email" name="email" id="email" class="form-control" required
                               value="<?php echo isset($_POST['email']) ? clean($_POST['email']) : ''; ?>">
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">كلمة المرور</label>
                        <input type="password" name="password" id="password" class="form-control" required minlength="6">
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">تأكيد كلمة المرور</label>
                        <input type="password" name="confirm_password" id="confirm_password" class="form-control" required>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">تسجيل</button>
                    </div>
                </form>

                <p class="text-center mt-3 mb-0">لديك حساب بالفعل؟ <a href="login.php">سجّل الدخول</a></p>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
