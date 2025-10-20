<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';

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

// إذا كانت العربة فارغة، لا يمكن إتمام الشراء
if (empty($cart_items)) {
    header('Location: cart.php?message=' . urlencode('<p style="color: red;">عربة التسوق فارغة، لا يمكن إتمام الشراء.</p>'));
    exit();
}

// معالجة إتمام الشراء
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['place_order'])) {
    $conn->begin_transaction();
    try {
        // 1. إنشاء طلب جديد في جدول الطلبات (orders)
        $stmt_order = $conn->prepare("INSERT INTO orders (user_id, total_amount, status) VALUES (?, ?, 'Pending')");
        $stmt_order->bind_param("id", $user_id, $total_amount);
        $stmt_order->execute();
        $order_id = $conn->insert_id;
        $stmt_order->close();

        // 2. نقل عناصر العربة إلى عناصر الطلب (order_items) وتحديث المخزون
        foreach ($cart_items as $item) {
            // التحقق من المخزون مرة أخرى قبل إتمام الطلب
            $stmt_check_stock = $conn->prepare("SELECT stock_quantity FROM products WHERE product_id = ? FOR UPDATE"); // FOR UPDATE لقفل الصف
            $stmt_check_stock->bind_param("i", $item['product_id']);
            $stmt_check_stock->execute();
            $result_stock = $stmt_check_stock->get_result();
            $product_stock = $result_stock->fetch_assoc();
            $stmt_check_stock->close();

            if (!$product_stock || $product_stock['stock_quantity'] < $item['quantity']) {
                throw new Exception('المنتج ' . $item['name'] . ' غير متوفر بكمية كافية في المخزون.');
            }

            // إضافة عنصر الطلب
            $stmt_order_item = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
            $stmt_order_item->bind_param("iiid", $order_id, $item['product_id'], $item['quantity'], $item['item_price']);
            $stmt_order_item->execute();
            $stmt_order_item->close();

            // تحديث كمية المخزون
            $stmt_update_stock = $conn->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE product_id = ?");
            $stmt_update_stock->bind_param("ii", $item['quantity'], $item['product_id']);
            $stmt_update_stock->execute();
            $stmt_update_stock->close();
        }

        // 3. حذف عناصر العربة للمستخدم
        $stmt_clear_cart = $conn->prepare("DELETE FROM cart_items WHERE cart_id IN (SELECT cart_id FROM carts WHERE user_id = ?)");
        $stmt_clear_cart->bind_param("i", $user_id);
        $stmt_clear_cart->execute();
        $stmt_clear_cart->close();

        $conn->commit();
        header('Location: orders.php?message=' . urlencode('<p style="color: green;">تم إتمام طلبك بنجاح! رقم الطلب: ' . $order_id . '</p>'));
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        $message = '<p style="color: red;">حدث خطأ أثناء إتمام الشراء: ' . $e->getMessage() . '</p>';
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إتمام الشراء</title>
    <link rel="stylesheet" href="css/store_style.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        .checkout-container {
            max-width: 800px;
            margin: 50px auto;
            padding: 30px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        .checkout-container h2 {
            color: #333;
            margin-bottom: 25px;
            font-size: 2.2em;
        }
        .order-summary-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid #eee;
            padding: 10px 0;
            margin-bottom: 10px;
        }
        .order-summary-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }
        .order-summary-item img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 5px;
            margin-left: 15px;
        }
        .item-info {
            flex-grow: 1;
            text-align: right;
        }
        .item-info h3 {
            margin: 0 0 5px 0;
            font-size: 1.1em;
            color: #333;
        }
        .item-info p {
            margin: 0;
            color: #666;
        }
        .total-section {
            border-top: 2px solid #eee;
            padding-top: 20px;
            margin-top: 30px;
            text-align: left;
            font-size: 1.5em;
            font-weight: 700;
            color: #333;
        }
        .place-order-btn {
            background-color: #28a745;
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
        .place-order-btn:hover {
            background-color: #218838;
            transform: translateY(-2px);
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
        <div class="checkout-container">
            <h2>إتمام الشراء</h2>
            <?php if (!empty($message)): ?>
                <div class="message">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <?php if (empty($cart_items)): ?>
                <p>عربة التسوق فارغة.</p>
                <a href="store.php" class="place-order-btn">العودة إلى المتجر</a>
            <?php else: ?>
                <h3>ملخص الطلب</h3>
                <?php foreach ($cart_items as $item): ?>
                    <div class="order-summary-item">
                        <img src="<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                        <div class="item-info">
                            <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                            <p>الكمية: <?php echo htmlspecialchars($item['quantity']); ?> x <?php echo htmlspecialchars(number_format($item['item_price'], 2)); ?> ريال</p>
                        </div>
                        <span><?php echo htmlspecialchars(number_format($item['quantity'] * $item['item_price'], 2)); ?> ريال</span>
                    </div>
                <?php endforeach; ?>

                <div class="total-section">
                    <p>المجموع الكلي: <?php echo htmlspecialchars(number_format($total_amount, 2)); ?> ريال</p>
                </div>

                <form action="checkout.php" method="post">
                    <button type="submit" name="place_order" class="place-order-btn">تأكيد الطلب</button>
                </form>
            <?php endif; ?>
        </div>
    </main>
    <footer>
        <p>&copy; 2025 متجرنا الإلكتروني. جميع الحقوق محفوظة.</p>
    </footer>
</body>
</html>
