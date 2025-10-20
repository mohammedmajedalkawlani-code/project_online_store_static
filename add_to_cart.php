<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['product_id'], $_POST['price'])) {
    $product_id = $_POST['product_id'];
    $price = $_POST['price'];
    $quantity = 1; // افتراض إضافة منتج واحد في كل مرة

    // التحقق من وجود المنتج وكمية المخزون
    $stmt_product = $conn->prepare("SELECT stock_quantity FROM products WHERE product_id = ?");
    $stmt_product->bind_param("i", $product_id);
    $stmt_product->execute();
    $result_product = $stmt_product->get_result();
    $product_data = $result_product->fetch_assoc();
    $stmt_product->close();

    if (!$product_data || $product_data['stock_quantity'] < $quantity) {
        $message = "<p style='color: red;'>المنتج غير متوفر بكمية كافية في المخزون.</p>";
        header('Location: store.php?message=' . urlencode($message));
        exit();
    }

    // البحث عن عربة التسوق الحالية للمستخدم أو إنشائها إذا لم تكن موجودة
    $stmt_cart = $conn->prepare("SELECT cart_id FROM carts WHERE user_id = ?");
    $stmt_cart->bind_param("i", $user_id);
    $stmt_cart->execute();
    $result_cart = $stmt_cart->get_result();
    $cart = $result_cart->fetch_assoc();
    $stmt_cart->close();

    $cart_id;
    if ($cart) {
        $cart_id = $cart['cart_id'];
    } else {
        // إنشاء عربة تسوق جديدة للمستخدم
        $stmt_new_cart = $conn->prepare("INSERT INTO carts (user_id) VALUES (?)");
        $stmt_new_cart->bind_param("i", $user_id);
        $stmt_new_cart->execute();
        $cart_id = $conn->insert_id;
        $stmt_new_cart->close();
    }

    // التحقق مما إذا كان المنتج موجودًا بالفعل في عربة التسوق
    $stmt_cart_item = $conn->prepare("SELECT cart_item_id, quantity FROM cart_items WHERE cart_id = ? AND product_id = ?");
    $stmt_cart_item->bind_param("ii", $cart_id, $product_id);
    $stmt_cart_item->execute();
    $result_cart_item = $stmt_cart_item->get_result();
    $cart_item = $result_cart_item->fetch_assoc();
    $stmt_cart_item->close();

    if ($cart_item) {
        // تحديث الكمية إذا كان المنتج موجودًا بالفعل
        $new_quantity = $cart_item['quantity'] + $quantity;
        if ($product_data['stock_quantity'] < $new_quantity) {
            $message = "<p style='color: red;'>لا يمكن إضافة المزيد من هذا المنتج، الكمية المتوفرة في المخزون غير كافية.</p>";
        } else {
            $stmt_update_item = $conn->prepare("UPDATE cart_items SET quantity = ? WHERE cart_item_id = ?");
            $stmt_update_item->bind_param("ii", $new_quantity, $cart_item['cart_item_id']);
            if ($stmt_update_item->execute()) {
                $message = "<p style='color: green;'>تم تحديث كمية المنتج في عربة التسوق.</p>";
            } else {
                $message = "<p style='color: red;'>حدث خطأ أثناء تحديث عربة التسوق.</p>";
            }
            $stmt_update_item->close();
        }
    } else {
        // إضافة المنتج كعنصر جديد في عربة التسوق
        $stmt_add_item = $conn->prepare("INSERT INTO cart_items (cart_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
        $stmt_add_item->bind_param("iiid", $cart_id, $product_id, $quantity, $price);
        if ($stmt_add_item->execute()) {
            $message = "<p style='color: green;'>تم إضافة المنتج إلى عربة التسوق بنجاح!</p>";
        } else {
            $message = "<p style='color: red;'>حدث خطأ أثناء إضافة المنتج إلى عربة التسوق.</p>";
        }
        $stmt_add_item->close();
    }
} else {
    $message = "<p style='color: red;'>طلب غير صالح.</p>";
}

$conn->close();
header('Location: store.php?message=' . urlencode($message));
exit();
?>
