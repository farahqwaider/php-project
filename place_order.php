<?php

require_once 'includes/header.php';

if (!$isLoggedIn || $role !== 'customer') {
    setFlash('danger', 'يرجى تسجيل الدخول كعميل لتتمكن من وضع الطلب.');
    redirect('login.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productId = intval($_POST['product_id'] ?? 0);
    $quantity = intval($_POST['quantity'] ?? 0);

    if ($productId <= 0) {
        $errors[] = 'تم اختيار منتج غير صالح.';
    }
    if ($quantity <= 0) {
        $errors[] = 'الكمية يجب أن تكون 1 على الأقل.';
    }

    if (empty($errors)) {

        $stmt = $conn->prepare('SELECT id, name, price, stock FROM products WHERE id = ? AND stock > 0');
        $stmt->bind_param('i', $productId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $product = $result->fetch_assoc();

            if ($quantity > $product['stock']) {
                $errors[] = 'المخزون غير كافٍ. متبقٍ ' . (int)$product['stock'] . ' فقط.';
            } else {
                $total = $product['price'] * $quantity;

                $conn->begin_transaction();

                try {

                    $orderStmt = $conn->prepare('INSERT INTO orders (user_id, total_amount, status) VALUES (?, ?, "pending")');
                    $orderStmt->bind_param('id', $_SESSION['user_id'], $total);
                    $orderStmt->execute();
                    $orderId = $orderStmt->insert_id;
                    $orderStmt->close();

                    $detailStmt = $conn->prepare('INSERT INTO order_details (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)');
                    $detailStmt->bind_param('iiid', $orderId, $productId, $quantity, $product['price']);
                    $detailStmt->execute();
                    $detailStmt->close();

                    $newStock = $product['stock'] - $quantity;
                    $stockStmt = $conn->prepare('UPDATE products SET stock = ? WHERE id = ?');
                    $stockStmt->bind_param('ii', $newStock, $productId);
                    $stockStmt->execute();
                    $stockStmt->close();

                    $conn->commit();

                    setFlash('success', 'تم وضع الطلب بنجاح! رقم الطلب #' . $orderId);
                    redirect('index.php');
                } catch (Exception $e) {

                    $conn->rollback();
                    $errors[] = 'فشل وضع الطلب. حاول مرة أخرى.';
                }
            }
        } else {
            $errors[] = 'المنتج غير موجود أو نفد المخزون.';
        }
        $stmt->close();
    }
}

foreach ($errors as $error) {
    setFlash('danger', $error);
}
redirect('index.php');
