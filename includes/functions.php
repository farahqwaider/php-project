<?php

function redirect($url)
{
    header('Location: ' . $url);
    exit();
}

function clean($data)
{
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function showFlash()
{
    if (isset($_SESSION['flash'])) {
        $type = $_SESSION['flash']['type'];
        $message = $_SESSION['flash']['message'];
        unset($_SESSION['flash']);
        return '<div class="alert alert-' . clean($type) . ' alert-dismissible fade show" role="alert">'
             . clean($message)
             . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
    }
    return '';
}

function setFlash($type, $message)
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function productImage($image)
{

    $fsPath = dirname(__DIR__) . '/uploads/' . $image;
    if (!empty($image) && file_exists($fsPath)) {
        return BASE_URL . 'uploads/' . $image;
    }
    return 'https://via.placeholder.com/300x200?text=No+Image';
}
