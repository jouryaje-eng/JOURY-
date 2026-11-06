<?php
session_start();

require_once __DIR__ . '/includes/db.php';

// التأكد من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// السماح بطلبات POST فقط
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: view_students.php");
    exit;
}

// الحصول على ID الطالب
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    header("Location: view_students.php?delete_error=1");
    exit;
}

try {

    // جلب بيانات الطالب قبل الحذف
    $stmt = $pdo->prepare("
        SELECT profile_picture
        FROM students
        WHERE id = :id
    ");

    $stmt->execute([':id' => $id]);

    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        header("Location: view_students.php?delete_error=1");
        exit;
    }

    // حذف الطالب من قاعدة البيانات
    $delete = $pdo->prepare("
        DELETE FROM students
        WHERE id = :id
    ");

    $delete->execute([':id' => $id]);

    // حذف صورة الطالب من مجلد الصور
    $picture = $student['profile_picture'];

    if (
        $picture &&
        strpos($picture, 'assets/img/students_img/') === 0
    ) {
        $picturePath = __DIR__ . '/' . $picture;

        if (is_file($picturePath)) {
            unlink($picturePath);
        }
    }

    // العودة إلى صفحة الطلاب
    header("Location: view_students.php?deleted=1");
    exit;

} catch (PDOException $e) {

    header("Location: view_students.php?delete_error=1");
    exit;
}
?>