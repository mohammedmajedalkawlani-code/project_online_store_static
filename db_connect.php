<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$servername = "localhost";
$username = "root"; // اسم المستخدم الافتراضي لـ XAMPP
$password = "";     // كلمة المرور الافتراضية لـ XAMPP (عادة ما تكون فارغة)
$dbname = "online_store";

// إنشاء الاتصال
$conn = new mysqli($servername, $username, $password, $dbname);

// التحقق من الاتصال
if ($conn->connect_error) {
    die("فشل الاتصال بقاعدة البيانات: " . $conn->connect_error);
}
// echo "تم الاتصال بنجاح"; // يمكن إزالة هذا السطر بعد التأكد من الاتصال
?>
