<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';
if (isset($_GET['message'])) {
    $message = htmlspecialchars($_GET['message']);
}

$orders = [];

// جلب جميع طلبات المستخدم
$stmt_orders = $conn->prepare("SELECT order_id, order_date, total_amount, status FROM orders WHERE user_id = ? ORDER BY order_date DESC");
$stmt_orders->bind_param("i", $user_id);
$stmt_orders->execute();
$result_orders = $stmt_orders->get_result();

while ($order_row = $result_orders->fetch_assoc()) {
    $order_id = $order_row['order_id'];
    $order_items = [];

    // جلب تفاصيل المنتجات لكل طلب
    $stmt_items = $conn->prepare("
        SELECT oi.quantity, oi.price AS item_price, p.name, p.image_url
        FROM order_items oi
        JOIN products p ON oi.product_id = p.product_id
        WHERE oi.order_id = ?
    ");
    $stmt_items->bind_param("i", $order_id);
    $stmt_items->execute();
    $result_items = $stmt_items->get_result();

    while ($item_row = $result_items->fetch_assoc()) {
        $order_items[] = $item_row;
    }
    $stmt_items->close();

    $order_row['items'] = $order_items;
    $orders[] = $order_row;
}
$stmt_orders->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>سجل الطلبات</title>
    <link rel="stylesheet" href="css/store_style.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        .orders-container {
            max-width: 900px;
            margin: 50px auto;
            padding: 30px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        .orders-container h2 {
            color: #333;
            margin-bottom: 25px;
            font-size: 2.2em;
        }
        .order-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            margin-bottom: 20px;
            padding: 20px;
            text-align: right;
            background-color: #f9f9f9;
        }
        .order-card h3 {
            margin-top: 0;
            color: #007bff;
            font-size: 1.5em;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .order-card p {
            margin: 5px 0;
            color: #555;
        }
        .order-card .status {
            font-weight: bold;
            color: #28a745; /* Green for Pending, can be dynamic */
        }
        .order-items-list {
            list-style: none;
            padding: 0;
            margin-top: 15px;
            border-top: 1px solid #eee;
            padding-top: 15px;
        }
        .order-items-list li {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            font-size: 0.95em;
            color: #666;
        }
        .order-items-list li img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 3px;
            margin-left: 10px;
        }
        .order-items-list li span {
            flex-grow: 1;
        }
        .empty-orders-message {
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
                    <li><a href="orders.php">طلباتي</a></li>
                    <?php if (isset($_SESSION["role"]) && $_SESSION["role"] === "admin"): ?>
                    <li><a href="add_product.php">إضافة منتج جديد</a></li>
                    <?php endif; ?>
                    <li><a href="logout.php">تسجيل الخروج</a></li>
                </ul>
            </nav>
        </div>
    </header>
    <main>
        <div class="orders-container">
            <h2>سجل طلباتي</h2>
            <?php if (!empty($message)): ?>
                <div class="message">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <?php if (empty($orders)): ?>
                <p class="empty-orders-message">لم تقم بأي طلبات بعد.</p>
                <a href="store.php" class="checkout-btn">ابدأ التسوق</a>
            <?php else: ?>
                <?php foreach ($orders as $order): ?>
                    <div class="order-card">
                        <h3>الطلب رقم: <?php echo htmlspecialchars($order['order_id']); ?></h3>
                        <p>تاريخ الطلب: <?php echo htmlspecialchars($order['order_date']); ?></p>
                        <p>المجموع الكلي: <?php echo htmlspecialchars(number_format($order['total_amount'], 2)); ?> ريال</p>
                        <p>الحالة: <span class="status"><?php echo htmlspecialchars($order['status']); ?></span></p>
                        <h4>المنتجات:</h4>
                        <ul class="order-items-list">
                            <?php foreach ($order['items'] as $item): ?>
                                <li>
                                    <img src="<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                    <span><?php echo htmlspecialchars($item['name']); ?> (x<?php echo htmlspecialchars($item['quantity']); ?>) - <?php echo htmlspecialchars(number_format($item['item_price'], 2)); ?> ريال/قطعة</span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
    <footer>
        <p>&copy; 2025 متجرنا الإلكتروني. جميع الحقوق محفوظة.</p>
    </footer>
</body>
</html>
