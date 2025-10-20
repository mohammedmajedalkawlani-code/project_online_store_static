<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';

// معالجة تحديث الكمية أو حذف المنتج من العربة
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_quantity'])) {
        $cart_item_id = $_POST['cart_item_id'];
        $new_quantity = $_POST['quantity'];

        if ($new_quantity <= 0) {
            // إذا كانت الكمية صفر أو أقل، احذف العنصر
            $stmt = $conn->prepare("DELETE FROM cart_items WHERE cart_item_id = ? AND cart_id IN (SELECT cart_id FROM carts WHERE user_id = ?)");
            $stmt->bind_param("ii", $cart_item_id, $user_id);
            if ($stmt->execute()) {
                $message = "<p style='color: green;'>تم حذف المنتج من عربة التسوق.</p>";
            } else {
                $message = "<p style='color: red;'>حدث خطأ أثناء حذف المنتج.</p>";
            }
            $stmt->close();
        } else {
            // التحقق من الكمية المتوفرة في المخزون
            $stmt_check_stock = $conn->prepare("SELECT p.stock_quantity FROM products p JOIN cart_items ci ON p.product_id = ci.product_id WHERE ci.cart_item_id = ?");
            $stmt_check_stock->bind_param("i", $cart_item_id);
            $stmt_check_stock->execute();
            $result_stock = $stmt_check_stock->get_result();
            $stock_data = $result_stock->fetch_assoc();
            $stmt_check_stock->close();

            if ($stock_data && $new_quantity <= $stock_data['stock_quantity']) {
                $stmt = $conn->prepare("UPDATE cart_items SET quantity = ? WHERE cart_item_id = ? AND cart_id IN (SELECT cart_id FROM carts WHERE user_id = ?)");
                $stmt->bind_param("iii", $new_quantity, $cart_item_id, $user_id);
                if ($stmt->execute()) {
                    $message = "<p style='color: green;'>تم تحديث كمية المنتج.</p>";
                } else {
                    $message = "<p style='color: red;'>حدث خطأ أثناء تحديث الكمية.</p>";
                }
                $stmt->close();
            } else {
                $message = "<p style='color: red;'>الكمية المطلوبة غير متوفرة في المخزون.</p>";
            }
        }
    } elseif (isset($_POST['remove_item'])) {
        $cart_item_id = $_POST['cart_item_id'];
        $stmt = $conn->prepare("DELETE FROM cart_items WHERE cart_item_id = ? AND cart_id IN (SELECT cart_id FROM carts WHERE user_id = ?)");
        $stmt->bind_param("ii", $cart_item_id, $user_id);
        if ($stmt->execute()) {
            $message = "<p style='color: green;'>تم حذف المنتج من عربة التسوق.</p>";
        } else {
            $message = "<p style='color: red;'>حدث خطأ أثناء حذف المنتج.</p>";
        }
        $stmt->close();
    }
}

// جلب عناصر عربة التسوق للمستخدم
$cart_items = [];
$total_amount = 0;

$stmt_get_cart = $conn->prepare("
    SELECT ci.cart_item_id, p.product_id, p.name, p.price AS product_price, p.image_url, ci.quantity, ci.price AS item_price
    FROM cart_items ci
    JOIN products p ON ci.product_id = p.product_id
    JOIN carts c ON ci.cart_id = c.cart_id
    WHERE c.user_id = ?
");
$stmt_get_cart->bind_param("i", $user_id);
$stmt_get_cart->execute();
$result_get_cart = $stmt_get_cart->get_result();

while ($row = $result_get_cart->fetch_assoc()) {
    $cart_items[] = $row;
    $total_amount += ($row['quantity'] * $row['item_price']);
}
$stmt_get_cart->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>عربة التسوق</title>
    <link rel="stylesheet" href="css/store_style.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        .cart-container {
            max-width: 900px;
            margin: 50px auto;
            padding: 30px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        .cart-container h2 {
            color: #333;
            margin-bottom: 25px;
            font-size: 2.2em;
        }
        .cart-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid #eee;
            padding: 15px 0;
            margin-bottom: 15px;
        }
        .cart-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }
        .cart-item img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 5px;
            margin-left: 15px;
        }
        .item-details {
            flex-grow: 1;
            text-align: right;
        }
        .item-details h3 {
            margin: 0 0 5px 0;
            font-size: 1.2em;
            color: #333;
        }
        .item-details p {
            margin: 0;
            color: #666;
        }
        .item-quantity input {
            width: 50px;
            padding: 5px;
            border: 1px solid #ddd;
            border-radius: 5px;
            text-align: center;
            margin: 0 10px;
        }
        .item-actions button {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .item-actions button:hover {
            background-color: #c82333;
        }
        .cart-summary {
            border-top: 2px solid #eee;
            padding-top: 20px;
            margin-top: 30px;
            text-align: left;
        }
        .cart-summary p {
            font-size: 1.4em;
            font-weight: 700;
            color: #333;
        }
        .checkout-btn {
            background-color: #007bff;
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1.1em;
            font-weight: 700;
            transition: background-color 0.3s ease, transform 0.2s ease;
            margin-top: 20px;
            display: inline-block;
            text-decoration: none;
        }
        .checkout-btn:hover {
            background-color: #0056b3;
            transform: translateY(-2px);
        }
        .empty-cart-message {
            color: #666;
            font-size: 1.1em;
            margin-top: 20px;
        }
        .message {
            margin-bottom: 20px;
            padding: 10px;
            border-radius: 5px;
            text-align: center;
        }
        .message p {
            margin: 0;
        }
    </style>
</head>
<body>
    <header>
        <div class="header-content">
            <h1>متجرنا الإلكتروني</h1>
            <nav>
                <ul>
                    <li><a href="store.php">الرئيسية</a></li>
                    <li><a href="#">المنتجات</a></li>
                    <li><a href="cart.php">عربة التسوق</a></li>
                    <?php if (isset($_SESSION["role"]) && $_SESSION["role"] === "admin"): ?>
                    <li><a href="add_product.php">إضافة منتج جديد</a></li>
                    <?php endif; ?>
                    <li><a href="logout.php">تسجيل الخروج</a></li>
                </ul>
            </nav>
        </div>
    </header>
    <main>
        <div class="cart-container">
            <h2>عربة التسوق</h2>
            <?php if (!empty($message)): ?>
                <div class="message">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <?php if (empty($cart_items)): ?>
                <p class="empty-cart-message">عربة التسوق فارغة.</p>
                <a href="store.php" class="checkout-btn">العودة إلى المتجر</a>
            <?php else: ?>
                <?php foreach ($cart_items as $item): ?>
                    <div class="cart-item">
                        <img src="<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                        <div class="item-details">
                            <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                            <p>السعر: <?php echo htmlspecialchars(number_format($item['item_price'], 2)); ?> ريال</p>
                        </div>
                        <form action="cart.php" method="post" class="item-quantity">
                            <input type="hidden" name="cart_item_id" value="<?php echo $item['cart_item_id']; ?>">
                            <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" min="0" onchange="this.form.submit()">
                            <button type="submit" name="update_quantity" style="display:none;">تحديث</button>
                        </form>
                        <form action="cart.php" method="post" class="item-actions">
                            <input type="hidden" name="cart_item_id" value="<?php echo $item['cart_item_id']; ?>">
                            <button type="submit" name="remove_item">حذف</button>
                        </form>
                    </div>
                <?php endforeach; ?>

                <div class="cart-summary">
                    <p>المجموع الكلي: <?php echo htmlspecialchars(number_format($total_amount, 2)); ?> ريال</p>
                    <a href="checkout.php" class="checkout-btn">إتمام الشراء</a>
                </div>
            <?php endif; ?>
        </div>
    </main>
    <footer>
        <p>&copy; 2025 متجرنا الإلكتروني. جميع الحقوق محفوظة.</p>
    </footer>
</body>
</html>
