<?php
session_start();
include 'db_connect.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $email = $_POST['email'];
    $role = 'user'; // الدور الافتراضي للمستخدم الجديد هو 'user'

    // التحقق من أن جميع الحقول المطلوبة ليست فارغة
    if (empty($username) || empty($password) || empty($email)) {
        $message = '<p style="color: red;">الرجاء ملء جميع الحقول المطلوبة.</p>';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = '<p style="color: red;">صيغة البريد الإلكتروني غير صحيحة.</p>';
    } else {
        // التحقق مما إذا كان اسم المستخدم أو البريد الإلكتروني موجودًا بالفعل
        $stmt_check = $conn->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
        $stmt_check->bind_param("ss", $username, $email);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            $message = '<p style="color: red;">اسم المستخدم أو البريد الإلكتروني موجود بالفعل.</p>';
        } else {
            // تشفير كلمة المرور قبل حفظها (مهم جدًا للأمان)
            // لأغراض هذا المشروع، سنستخدم كلمة المرور كما هي، ولكن في مشروع حقيقي يجب استخدام password_hash()
            // $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("INSERT INTO users (username, password, email, role) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $username, $hashed_password, $email, $role);

            if ($stmt->execute()) {
                $message = '<p style="color: green;">تم إنشاء الحساب بنجاح! يمكنك الآن تسجيل الدخول.</p>';
            } else {
                $message = '<p style="color: red;">حدث خطأ أثناء إنشاء الحساب: ' . $conn->error . '</p>';
            }
            $stmt->close();
        }
        $stmt_check->close();
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إنشاء حساب جديد - متجرنا الإلكتروني</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        .login-container {
            max-width: 500px;
            margin: 50px auto;
            padding: 30px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        .login-header h2 {
            color: #333;
            margin-bottom: 10px;
            font-size: 2em;
        }
        .login-header p {
            color: #666;
            margin-bottom: 20px;
        }
        .input-group {
            margin-bottom: 20px;
            text-align: right;
        }
        .input-group label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 600;
        }
        .input-wrapper {
            position: relative;
        }
        .input-wrapper .input-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #aaa;
        }
        .input-group input[type="text"],
        .input-group input[type="password"],
        .input-group input[type="email"] {
            width: calc(100% - 40px); /* Adjust for icon */
            padding: 12px 40px 12px 15px; /* Padding for icon */
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1em;
            box-sizing: border-box;
            transition: border-color 0.3s ease;
            text-align: right;
        }
        .input-group input[type="text"]:focus,
        .input-group input[type="password"]:focus,
        .input-group input[type="email"]:focus {
            border-color: #007bff;
            outline: none;
        }
        .login-btn {
            background-color: #007bff;
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
            width: 100%;
        }
        .login-btn:hover {
            background-color: #0056b3;
            transform: translateY(-2px);
        }
        .login-btn .btn-animation {
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.2);
            transition: all 0.4s ease;
        }
        .login-btn:hover .btn-animation {
            left: 100%;
        }
        .login-footer p {
            margin-top: 20px;
            color: #666;
        }
        .login-footer a {
            color: #007bff;
            text-decoration: none;
            font-weight: 600;
        }
        .login-footer a:hover {
            text-decoration: underline;
        }
    </style>
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
            <h2>إنشاء حساب جديد</h2>
            <p>املأ البيانات لإنشاء حسابك</p>
        </div>
        
        <form class="login-form" action="register.php" method="POST">
            <?php if ($message): ?>
                <?php echo $message; ?>
            <?php endif; ?>
            <div class="input-group">
                <label for="username">اسم المستخدم</label>
                <div class="input-wrapper">
                    <span class="input-icon">👤</span>
                    <input type="text" id="username" name="username" placeholder="أدخل اسم المستخدم" required>
                </div>
            </div>
            
            <div class="input-group">
                <label for="email">البريد الإلكتروني</label>
                <div class="input-wrapper">
                    <span class="input-icon">📧</span>
                    <input type="email" id="email" name="email" placeholder="أدخل بريدك الإلكتروني" required>
                </div>
            </div>

            <div class="input-group">
                <label for="password">كلمة المرور</label>
                <div class="input-wrapper">
                    <span class="input-icon">🔒</span>
                    <input type="password" id="password" name="password" placeholder="أدخل كلمة المرور" required>
                </div>
            </div>
            
            <button type="submit" class="login-btn">
                <span>تسجيل</span>
                <div class="btn-animation"></div>
            </button>
        </form>
        
        <div class="login-footer">
            <p>لديك حساب بالفعل؟ <a href="index.php" class="signup-link">تسجيل الدخول</a></p>
        </div>
    </div>
    
    <script src="js/script.js"></script>
</body>
</html>
