<?php
session_start();

require_once __DIR__ . '/includes/db.php';

// التأكد من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$isLoggedIn = true;

// الحصول على ID الطالب
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    header("Location: view_students.php");
    exit;
}

// جلب بيانات الطالب
$stmt = $pdo->prepare("
    SELECT id, name, email, phone, address, profile_picture
    FROM students
    WHERE id = :id
");

$stmt->execute([':id' => $id]);

$student = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    header("Location: view_students.php");
    exit;
}

// الاحتفاظ بالقيم عند وجود خطأ
$name = $student['name'];
$email = $student['email'];
$phone = $student['phone'];
$address = $student['address'];

$errors = [];
$success = "";

// معالجة النموذج
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');

    // التحقق من المدخلات
    if ($name === '') {
        $errors['name'] = "يرجى إدخال اسم الطالب.";
    }

    if ($email === '') {
        $errors['email'] = "يرجى إدخال البريد الإلكتروني.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "البريد الإلكتروني غير صالح.";
    }

    if ($phone === '') {
        $errors['phone'] = "يرجى إدخال رقم الهاتف.";
    }

    if ($address === '') {
        $errors['address'] = "يرجى إدخال العنوان.";
    }

    // التأكد من عدم استخدام البريد لطالب آخر
    if (!isset($errors['email'])) {

        $checkEmail = $pdo->prepare("
            SELECT id
            FROM students
            WHERE email = :email AND id != :id
            LIMIT 1
        ");

        $checkEmail->execute([
            ':email' => $email,
            ':id' => $id
        ]);

        if ($checkEmail->fetch()) {
            $errors['email'] = "هذا البريد الإلكتروني مستخدم لطالب آخر.";
        }
    }

    // الاحتفاظ بمسار الصورة القديمة
    $picturePath = $student['profile_picture'];
    $newImagePath = null;

    // التحقق من الصورة الجديدة إن وجدت
    if (
        isset($_FILES['profile_picture']) &&
        $_FILES['profile_picture']['error'] !== UPLOAD_ERR_NO_FILE
    ) {
        $picture = $_FILES['profile_picture'];

        if ($picture['error'] !== UPLOAD_ERR_OK) {
            $errors['profile_picture'] = "حدث خطأ أثناء رفع الصورة.";
        } else {

            $imageInfo = getimagesize($picture['tmp_name']);

            if ($imageInfo === false) {
                $errors['profile_picture'] = "الملف المحدد ليس صورة صالحة.";
            } else {

                $allowedTypes = [
                    IMAGETYPE_JPEG => 'jpg',
                    IMAGETYPE_PNG  => 'png',
                    IMAGETYPE_WEBP => 'webp'
                ];

                $imageType = $imageInfo[2];

                if (!isset($allowedTypes[$imageType])) {
                    $errors['profile_picture'] =
                        "يسمح فقط بصور JPG و PNG و WEBP.";
                } else {

                    $uploadDirectory =
                        __DIR__ . '/assets/img/students_img/';

                    if (
                        !is_dir($uploadDirectory) &&
                        !mkdir($uploadDirectory, 0755, true)
                    ) {
                        $errors['profile_picture'] =
                            "تعذر إنشاء مجلد الصور.";
                    } elseif (!is_writable($uploadDirectory)) {
                        $errors['profile_picture'] =
                            "لا توجد صلاحية لحفظ الصور في المجلد.";
                    } else {

                        $fileName = bin2hex(random_bytes(16))
                            . '.' . $allowedTypes[$imageType];

                        $newImagePath =
                            'assets/img/students_img/' . $fileName;

                        $destination =
                            $uploadDirectory . $fileName;
                    }
                }
            }
        }
    }

    // تنفيذ التعديل إذا لم توجد أخطاء
    if (empty($errors)) {

        try {

            // رفع الصورة الجديدة
            if ($newImagePath !== null) {

                if (!move_uploaded_file(
                    $picture['tmp_name'],
                    $destination
                )) {
                    throw new RuntimeException(
                        "تعذر حفظ الصورة."
                    );
                }

                $picturePath = $newImagePath;
            }

            // تحديث بيانات الطالب
            $update = $pdo->prepare("
                UPDATE students
                SET
                    name = :name,
                    email = :email,
                    phone = :phone,
                    address = :address,
                    profile_picture = :profile_picture
                WHERE id = :id
            ");

            $update->execute([
                ':name' => $name,
                ':email' => $email,
                ':phone' => $phone,
                ':address' => $address,
                ':profile_picture' => $picturePath,
                ':id' => $id
            ]);

            // حذف الصورة القديمة بعد نجاح التحديث
            if ($newImagePath !== null) {

                $oldPicture = $student['profile_picture'];

                if (
                    $oldPicture &&
                    strpos(
                        $oldPicture,
                        'assets/img/students_img/'
                    ) === 0
                ) {
                    $oldFile = __DIR__ . '/' . $oldPicture;

                    if (is_file($oldFile)) {
                        unlink($oldFile);
                    }
                }
            }

            // إعادة التوجيه بعد نجاح التعديل
            header("Location: view_students.php?updated=1");
            exit;

        } catch (Throwable $e) {

            // حذف الصورة الجديدة إذا فشلت العملية
            if (
                $newImagePath !== null &&
                isset($destination) &&
                is_file($destination)
            ) {
                unlink($destination);
            }

            $errors['general'] =
                "حدث خطأ أثناء تحديث بيانات الطالب.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>بوابة SVU</title>

    <!-- Bootstrap RTL CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.rtl.min.css"
          rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css"
          rel="stylesheet">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

    <!-- Header -->
    <header>

        <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">

            <div class="container">

                <!-- Logo and Site Name -->
                <a class="navbar-brand d-flex align-items-center gap-2"
                   href="index.php">

                    <img src="assets/img/logo.jpeg"
                         alt="شعار بوابة SVU"
                         class="navbar-logo">

                    <span class="fw-bold site-name">بوابة SVU</span>

                </a>

                <!-- Mobile Toggle -->
                <button class="navbar-toggler"
                        type="button"
                        data-bs-toggle="collapse"
                        data-bs-target="#navbarContent"
                        aria-controls="navbarContent"
                        aria-expanded="false"
                        aria-label="فتح القائمة">

                    <span class="navbar-toggler-icon"></span>

                </button>

                <!-- Navbar Links -->
                <div class="collapse navbar-collapse" id="navbarContent">

                    <ul class="navbar-nav me-auto align-items-lg-center gap-lg-3">

                        <li class="nav-item">
                            <a class="nav-link" href="index.php">
                                الصفحة الرئيسية
                            </a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link" href="view_students.php">
                                عرض الطلاب
                            </a>
                        </li>

                        <?php if ($isLoggedIn): ?>

                            <li class="nav-item">
                                <a class="btn btn-logout"
                                   href="logout.php">

                                    <i class="bi bi-box-arrow-right"></i>
                                    تسجيل الخروج

                                </a>
                            </li>

                        <?php else: ?>

                            <li class="nav-item">
                                <a class="nav-link" href="login.php">
                                    تسجيل الدخول
                                </a>
                            </li>

                            <li class="nav-item">
                                <a class="btn btn-register"
                                   href="register.php">

                                    إنشاء حساب

                                </a>
                            </li>

                        <?php endif; ?>

                    </ul>

                </div>

            </div>

        </nav>

    </header>


    <!-- Main Content -->
<main class="flex-grow-1 py-5">

    <div class="container">

        <div class="text-center mb-4">
            <h2 class="fw-bold">تعديل بيانات الطالب</h2>

            <p class="text-muted">
                يمكنك تعديل بيانات الطالب وتحديث معلوماته.
            </p>
        </div>

        <div class="card border shadow-sm mx-auto"
             style="max-width: 700px;">

            <div class="card-body p-4">

                <?php if (isset($errors['general'])): ?>
                    <div class="alert alert-danger">
                        <?= htmlspecialchars($errors['general']) ?>
                    </div>
                <?php endif; ?>

                <form method="POST"
                      enctype="multipart/form-data">

                    <!-- Name -->
                    <div class="mb-3">

                        <label for="name" class="form-label">
                            اسم الطالب
                        </label>

                        <input
                            type="text"
                            class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>"
                            id="name"
                            name="name"
                            value="<?= htmlspecialchars($name) ?>"
                        >

                        <?php if (isset($errors['name'])): ?>
                            <div class="invalid-feedback">
                                <?= htmlspecialchars($errors['name']) ?>
                            </div>
                        <?php endif; ?>

                    </div>

                    <!-- Email -->
                    <div class="mb-3">

                        <label for="email" class="form-label">
                            البريد الإلكتروني
                        </label>

                        <input
                            type="email"
                            class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                            id="email"
                            name="email"
                            value="<?= htmlspecialchars($email) ?>"
                        >

                        <?php if (isset($errors['email'])): ?>
                            <div class="invalid-feedback">
                                <?= htmlspecialchars($errors['email']) ?>
                            </div>
                        <?php endif; ?>

                    </div>

                    <!-- Phone -->
                    <div class="mb-3">

                        <label for="phone" class="form-label">
                            رقم الهاتف
                        </label>

                        <input
                            type="text"
                            class="form-control <?= isset($errors['phone']) ? 'is-invalid' : '' ?>"
                            id="phone"
                            name="phone"
                            value="<?= htmlspecialchars($phone) ?>"
                        >

                        <?php if (isset($errors['phone'])): ?>
                            <div class="invalid-feedback">
                                <?= htmlspecialchars($errors['phone']) ?>
                            </div>
                        <?php endif; ?>

                    </div>

                    <!-- Address -->
                    <div class="mb-3">

                        <label for="address" class="form-label">
                            العنوان
                        </label>

                        <textarea
                            class="form-control <?= isset($errors['address']) ? 'is-invalid' : '' ?>"
                            id="address"
                            name="address"
                            rows="3"
                        ><?= htmlspecialchars($address) ?></textarea>

                        <?php if (isset($errors['address'])): ?>
                            <div class="invalid-feedback">
                                <?= htmlspecialchars($errors['address']) ?>
                            </div>
                        <?php endif; ?>

                    </div>

                    <!-- Current Picture -->
                    <div class="mb-3">

                        <label class="form-label">
                            الصورة الحالية
                        </label>

                        <?php if (!empty($student['profile_picture'])): ?>

                            <div class="mb-2">
                                <img
                                    src="<?= htmlspecialchars($student['profile_picture']) ?>"
                                    alt="Student Picture"
                                    width="100"
                                    height="100"
                                    class="rounded border"
                                    style="object-fit: cover;"
                                >
                            </div>

                        <?php else: ?>

                            <p class="text-muted">
                                لا توجد صورة حالية.
                            </p>

                        <?php endif; ?>

                    </div>

                    <!-- New Picture -->
                    <div class="mb-4">

                        <label for="profile_picture"
                               class="form-label">
                            تغيير الصورة (اختياري)
                        </label>

                        <input
                            type="file"
                            class="form-control <?= isset($errors['profile_picture']) ? 'is-invalid' : '' ?>"
                            id="profile_picture"
                            name="profile_picture"
                            accept="image/jpeg,image/png,image/webp"
                        >

                        <?php if (isset($errors['profile_picture'])): ?>
                            <div class="invalid-feedback">
                                <?= htmlspecialchars($errors['profile_picture']) ?>
                            </div>
                        <?php endif; ?>

                        <div class="form-text">
                            الصيغ المدعومة: JPG و PNG و WEBP.
                        </div>

                    </div>

                    <!-- Buttons -->
                    <div class="d-flex gap-2">

                        <button type="submit"
                                class="btn btn-success">
                            <i class="bi bi-check-circle"></i>
                            حفظ التعديلات
                        </button>

                        <a href="view_students.php"
                           class="btn btn-outline-secondary">
                            إلغاء
                        </a>

                    </div>

                </form>

            </div>
        </div>

    </div>

</main>


    <!-- Footer -->
    <footer class="site-footer">

        <div class="container py-4">

            <div class="row align-items-center gy-3">

                <div class="col-md-6 text-center text-md-start">

                    <h5 class="footer-title">
                        بوابة SVU
                    </h5>

                    <p class="footer-description mb-0">
                        نظام بسيط وفعال لتسجيل الطلاب وإدارة بياناتهم.
                    </p>

                </div>

                <div class="col-md-6 text-center text-md-end">

                    <p class="mb-1">
                        الجهة المنظمة: الجامعة الافتراضية السورية
                    </p>

                    <p class="copyright mb-0">
                        &copy; <?php echo date('Y'); ?>
                        بوابة SVU. جميع الحقوق محفوظة.
                    </p>

                </div>

            </div>

        </div>

    </footer>


    <!-- Bootstrap JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js">
    </script>

    <!-- Custom JavaScript -->
    <script src="assets/js/script.js"></script>

</body>
</html>
