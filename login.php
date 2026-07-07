<?php

require_once 'includes/header.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';

    if (empty($email)) {
        $errors[] = 'البريد الإلكتروني مطلوب.';
    }
    if (empty($pass)) {
        $errors[] = 'كلمة المرور مطلوبة.';
    }

    if (empty($errors)) {

        $stmt = $conn->prepare('SELECT id, name, email, password, role FROM users WHERE email = ?');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            if (password_verify($pass, $user['password'])) {

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name']    = $user['name'];
                $_SESSION['email']   = $user['email'];
                $_SESSION['role']    = $user['role'];

                setcookie('last_user', $user['email'], time() + (86400 * 30), '/');

                setFlash('success', 'مرحبًا بعودتك، ' . clean($user['name']) . '!');

                if ($user['role'] === 'admin') {
                    redirect('admin/dashboard.php');
                } else {
                    redirect('index.php');
                }
            } else {
                $errors[] = 'البريد الإلكتروني أو كلمة المرور غير صحيحة.';
            }
        } else {
            $errors[] = 'البريد الإلكتروني أو كلمة المرور غير صحيحة.';
        }
        $stmt->close();
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card shadow-sm">
            <div class="card-body">
                <h3 class="card-title text-center mb-4">تسجيل الدخول</h3>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo clean($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" action="login.php" novalidate>
                    <div class="mb-3">
                        <label for="email" class="form-label">البريد الإلكتروني</label>
                        <input type="email" name="email" id="email" class="form-control" required
                               value="<?php echo isset($_COOKIE['last_user']) ? clean($_COOKIE['last_user']) : ''; ?>">
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">كلمة المرور</label>
                        <input type="password" name="password" id="password" class="form-control" required>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">دخول</button>
                    </div>
                </form>

                <p class="text-center mt-3 mb-0">ليس لديك حساب؟ <a href="register.php">أنشئ حسابًا</a></p>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
