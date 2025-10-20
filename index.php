<?php
session_start();
include 'db_connect.php';

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT user_id, username, password, role FROM users WHERE username = ?");
    if ($stmt === false) {
        $error_message = 'خطأ في إعداد الاستعلام: ' . $conn->error;
        $conn->close();
        die($error_message);
    }
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user["password"])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION["username"] = $user["username"];
            $_SESSION["role"] = $user["role"];
            header('Location: store.php');
            exit();
        } else {
            $error_message = 'اسم المستخدم أو كلمة المرور غير صحيحة.';
        }
    } else {
        $error_message = 'اسم المستخدم أو كلمة المرور غير صحيحة.';
    }
    $stmt->close();
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>تسجيل الدخول - متجرنا الإلكتروني</title>

    <!-- وصف الموقع -->
    <meta name="description" content="متجر إلكتروني بسيط باستخدام PHP و MySQL مع تسجيل دخول وإنشاء حساب.">

    <!-- كلمات مفتاحية -->
    <meta name="keywords" content="متجر, الكتروني, PHP, MySQL, تسجيل الدخول, إنشاء حساب, shopping">

    <!-- اسم المبرمج -->
    <meta name="author" content="محمد ماجد الخولاني - Mohammed Majed Al-Khawlani">

    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="background-animation">
        <div class="floating-shapes">
            <div class="shape shape-1"></div>
            <div class="shape shape-2"></div>
            <div class="shape shape-3"></div>
            <div class="shape shape-4"></div>
            <div class="shape shape-5"></div>
        </div>
    </div>
    
    <div class="login-container">
        <div class="login-header">
            <div class="logo">
                <div class="logo-icon">🛍️</div>
                <h1>متجرنا الإلكتروني</h1>
            </div>
            <h2>مرحباً بك مرة أخرى</h2>
            <p>سجل دخولك للوصول إلى حسابك</p>
        </div>
        
        <form class="login-form" action="index.php" method="POST">
            <?php if ($error_message): ?>
                <p style="color: red; text-align: center;"><?php echo $error_message; ?></p>
            <?php endif; ?>
            <div class="input-group">
                <label for="username">اسم المستخدم</label>
                <div class="input-wrapper">
                    <span class="input-icon">👤</span>
                    <input type="text" id="username" name="username" placeholder="أدخل اسم المستخدم" required>
                </div>
            </div>
            
            <div class="input-group">
                <label for="password">كلمة المرور</label>
                <div class="input-wrapper">
                    <span class="input-icon">🔒</span>
                    <input type="password" id="password" name="password" placeholder="أدخل كلمة المرور" required>
                </div>
            </div>
            
            <div class="form-options">
                <label class="checkbox-container">
                    <input type="checkbox" id="remember">
                    <span class="checkmark"></span>
                    تذكرني
                </label>
                <a href="#" class="forgot-password">نسيت كلمة المرور؟</a>
            </div>
            
            <button type="submit" class="login-btn">
                <span>تسجيل الدخول</span>
                <div class="btn-animation"></div>
            </button>
        </form>
        
        <div class="login-footer">
            <p>ليس لديك حساب؟ <a href="register.php" class="signup-link">إنشاء حساب جديد</a></p>
            <div class="social-login">
                <p>أو سجل الدخول باستخدام</p>
                <div class="social-buttons">
                    <button class="social-btn google">Google</button>
                    <button class="social-btn facebook">Facebook</button>
                </div>
            </div>

            <!-- توقيع المبرمج مع الإيميل الجديد -->
            <p style="margin-top:15px; font-size:13px; color:#777;">
                © 2025 - تم التطوير بواسطة <strong>محمد ماجد الخولاني - Mohammed Majed Al-Khawlani</strong><br>
                📧 للتواصل: <a href="mailto:mohammedmajedalkawlani@gmail.com">mohammedmajedalkawlani@gmail.com</a>
            </p>
        </div>
    </div>
    
    <script src="js/script.js"></script>
</body>
</html>
