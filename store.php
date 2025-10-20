<?php
session_start();
include 'db_connect.php';

$message = '';
if (isset($_GET['message'])) {
    $message = htmlspecialchars($_GET['message']);
}

// التحقق مما إذا كان المستخدم مسجلاً للدخول
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// جلب المنتجات من قاعدة البيانات
$sql = "SELECT product_id, name, description, price, image_url FROM products";
$result = $conn->query($sql);

?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>واجهة المتجر</title>
    <link rel="stylesheet" href="css/store_style.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <header>
        <div class="header-content">
            <h1>متجرنا الإلكتروني</h1>
            <nav>
                <ul>
                    <li><a href="#">الرئيسية</a></li>
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

    <main class="product-grid">
        <?php
        if ($result->num_rows > 0) {
            // عرض كل منتج
            while($row = $result->fetch_assoc()) {
                echo '<div class="product-card">';
                echo '<img src="' . htmlspecialchars($row["image_url"]) . '" alt="' . htmlspecialchars($row["name"]) . '">';
                echo '<h2>' . htmlspecialchars($row["name"]) . '</h2>';
                echo '<p>' . htmlspecialchars($row["description"]) . '</p>';
                echo '<span class="price">' . htmlspecialchars(number_format($row["price"], 2)) . ' ريال</span>';
                
                // نموذج إضافة إلى السلة
                echo '<form action="add_to_cart.php" method="post" style="display:inline;">';
                echo '<input type="hidden" name="product_id" value="' . $row["product_id"] . '">';
                echo '<input type="hidden" name="price" value="' . $row["price"] . '">';
                echo '<button type="submit">أضف إلى السلة</button>';
                echo '</form>';

                // زر الحذف للمسؤول فقط
                if (isset($_SESSION["role"]) && $_SESSION["role"] === "admin") {
                    echo '<a href="delete_product.php?product_id=' . $row["product_id"] . '" class="delete-btn" onclick="return confirm(\'هل أنت متأكد من حذف هذا المنتج؟\');">حذف المنتج</a>';
                }

                echo '</div>';
            }
        } else {
            echo "<p style='text-align: center; width: 100%;'>لا توجد منتجات لعرضها.</p>";
        }

        $conn->close();
        ?>
    </main>

    <footer>
        <p>&copy; 2025 متجرنا الإلكتروني. جميع الحقوق محفوظة.</p>
    </footer>

    <script src="js/script.js"></script>
</body>
</html>
