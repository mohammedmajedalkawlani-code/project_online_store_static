<?php
session_start();
include 'db_connect.php';

// التحقق مما إذا كان المستخدم مسجلاً للدخول ودوره مسؤول
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: store.php"); // أو صفحة خطأ/عدم صلاحية
    exit();
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $image_url = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
        $target_dir = "uploads/";
        $image_file_type = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $unique_filename = uniqid() . "." . $image_file_type;
        $target_file = $target_dir . $unique_filename;

        // التحقق من نوع الملف
        $allowed_types = array("jpg", "png", "jpeg", "gif");
        if (!in_array($image_file_type, $allowed_types)) {
             $message = 	erase("<p style=\"color: red;\">عذرًا، فقط ملفات JPG, JPEG, PNG & GIF مسموح بها.</p>");
        } else {
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                $image_url = $target_file;
            } else {
                $message = 	erase("<p style=\"color: red;\">عذرًا، حدث خطأ أثناء رفع ملفك.</p>");
            }
        }
    }
    $stock_quantity = $_POST['stock_quantity'];

    // التحقق من صحة البيانات المدخلة
    if (empty($name) || empty($price) || empty($stock_quantity)) {
        $message = '<p style="color: red;">الرجاء ملء جميع الحقول المطلوبة (الاسم، السعر، الكمية).</p>';
    } elseif (!is_numeric($price) || $price <= 0) {
        $message = '<p style="color: red;">السعر يجب أن يكون رقمًا موجبًا.</p>';
    } elseif (!is_numeric($stock_quantity) || $stock_quantity < 0) {
        $message = '<p style="color: red;">الكمية يجب أن تكون رقمًا موجبًا أو صفرًا.</p>';
    } else {
        // إعداد وحفظ الصورة (هذا مثال بسيط، في مشروع حقيقي يجب التعامل مع رفع الملفات بشكل آمن)
        // هنا نفترض أن image_url هو مسار الصورة أو اسمها
        
        $stmt = $conn->prepare("INSERT INTO products (name, description, price, image_url, stock_quantity) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdss", $name, $description, $price, $image_url, $stock_quantity);

        if ($stmt->execute()) {
            $message = '<p style="color: green;">تم إضافة المنتج بنجاح!</p>';
        } else {
            $message = '<p style="color: red;">حدث خطأ أثناء إضافة المنتج: ' . $conn->error . '</p>';
        }
        $stmt->close();
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إضافة منتج جديد</title>
    <link rel="stylesheet" href="css/store_style.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        .add-product-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 30px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        .add-product-container h2 {
            color: #333;
            margin-bottom: 25px;
            font-size: 2em;
        }
        .add-product-form .input-group {
            margin-bottom: 20px;
            text-align: right;
        }
        .add-product-form label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 600;
        }
        .add-product-form input[type="text"],
        .add-product-form input[type="number"],
        .add-product-form textarea {
            width: calc(100% - 20px);
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1em;
            box-sizing: border-box;
            transition: border-color 0.3s ease;
        }
        .add-product-form input[type="text"]:focus,
        .add-product-form input[type="number"]:focus,
        .add-product-form textarea:focus {
            border-color: #007bff;
            outline: none;
        }
        .add-product-form textarea {
            resize: vertical;
            min-height: 100px;
        }
        .add-product-btn {
            background-color: #28a745;
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1.1em;
            font-weight: 700;
            transition: background-color 0.3s ease, transform 0.2s ease;
            position: relative;
            overflow: hidden;
            margin-top: 20px;
        }
        .add-product-btn:hover {
            background-color: #218838;
            transform: translateY(-2px);
        }
        .add-product-btn .btn-animation {
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.2);
            transition: all 0.4s ease;
        }
        .add-product-btn:hover .btn-animation {
            left: 100%;
        }
        .back-to-store {
            display: block;
            margin-top: 20px;
            color: #007bff;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }
        .back-to-store:hover {
            color: #0056b3;
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
                    <li><a href="#">عربة التسوق</a></li>
                    <li><a href="add_product.php">إضافة منتج جديد</a></li>
                    <li><a href="logout.php">تسجيل الخروج</a></li>
                </ul>
            </nav>
        </div>
    </header>
    <main>
        <div class="add-product-container">
            <h2>إضافة منتج جديد</h2>
            <?php echo $message; // عرض رسائل النجاح أو الخطأ ?>
            <form class="add-product-form" action="add_product.php" method="POST" enctype="multipart/form-data">
                <div class="input-group">
                    <label for="name">اسم المنتج:</label>
                    <input type="text" id="name" name="name" placeholder="أدخل اسم المنتج" required>
                </div>
                <div class="input-group">
                    <label for="description">الوصف:</label>
                    <textarea id="description" name="description" placeholder="أدخل وصف المنتج"></textarea>
                </div>
                <div class="input-group">
                    <label for="price">السعر (ريال):</label>
                    <input type="number" id="price" name="price" step="0.01" min="0" placeholder="أدخل سعر المنتج" required>
                </div>
                <div class="input-group">
                    <label for="image">صورة المنتج:</label>
                    <input type="file" id="image" name="image" accept="image/*">
                </div>
                <div class="input-group">
                    <label for="stock_quantity">الكمية المتوفرة:</label>
                    <input type="number" id="stock_quantity" name="stock_quantity" min="0" placeholder="أدخل الكمية المتوفرة" required>
                </div>
                <button type="submit" class="add-product-btn">
                    <span>إضافة المنتج</span>
                    <div class="btn-animation"></div>
                </button>
            </form>
            <a href="store.php" class="back-to-store">العودة إلى المتجر</a>
        </div>
    </main>
    <footer>
        <p>&copy; 2025 متجرنا الإلكتروني. جميع الحقوق محفوظة.</p>
    </footer>
</body>
</html>
