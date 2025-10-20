<?php
session_start();
include 'db_connect.php';

// التحقق مما إذا كان المستخدم مسجلاً للدخول ودوره مسؤول
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: store.php'); // أو صفحة خطأ/عدم صلاحية
    exit();
}

$message = '';

if (isset($_GET['product_id']) && !empty($_GET['product_id'])) {
    $product_id = $_GET['product_id'];

    // جلب معلومات المنتج قبل الحذف للحصول على مسار الصورة
    $stmt_select = $conn->prepare("SELECT image_url FROM products WHERE product_id = ?");
    $stmt_select->bind_param("i", $product_id);
    $stmt_select->execute();
    $result_select = $stmt_select->get_result();
    $product = $result_select->fetch_assoc();
    $stmt_select->close();

    if ($product) {
        // حذف الصورة من مجلد uploads إذا كانت موجودة
        if (!empty($product['image_url']) && file_exists($product['image_url'])) {
            unlink($product['image_url']);
        }

        // حذف المنتج من قاعدة البيانات
        $stmt_delete = $conn->prepare("DELETE FROM products WHERE product_id = ?");
        $stmt_delete->bind_param("i", $product_id);

        if ($stmt_delete->execute()) {
            $message = "<p style='color: green;'>تم حذف المنتج بنجاح!</p>";
        } else {
            $message = "<p style='color: red;'>حدث خطأ أثناء حذف المنتج: " . $conn->error . "</p>";
        }
        $stmt_delete->close();
    } else {
        $message = "<p style='color: red;'>المنتج غير موجود.</p>";
    }
} else {
    $message = "<p style='color: red;'>معرف المنتج غير محدد.</p>";
}

$conn->close();

// إعادة التوجيه إلى صفحة المتجر بعد عرض الرسالة لفترة وجيزة أو مباشرة
// يمكن تعديل هذا لإظهار الرسالة ثم إعادة التوجيه باستخدام JavaScript
header('Location: store.php?message=' . urlencode($message));
exit();
?>
