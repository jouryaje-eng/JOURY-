<?php

session_start();

require_once __DIR__ . '/includes/db.php';

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        http_response_code(401);

        header('Content-Type: application/json; charset=utf-8');

        echo json_encode([
            'success' => false,
            'message' => 'انتهت جلسة تسجيل الدخول، يرجى تسجيل الدخول مجدداً.'
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    header("Location: login.php");
    exit;
}

$isLoggedIn = true;


// معالجة طلب AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    header('Content-Type: application/json; charset=utf-8');

    $errors = [];

    // استقبال البيانات
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');

    // التحقق من الاسم
    if ($name === '') {
        $errors['name'] = 'يرجى إدخال اسم الطالب.';
    }

    // التحقق من البريد الإلكتروني
    if ($email === '') {
        $errors['email'] = 'يرجى إدخال البريد الإلكتروني.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'البريد الإلكتروني غير صالح.';
    }

    // التحقق من رقم الهاتف
    if ($phone === '') {
        $errors['phone'] = 'يرجى إدخال رقم الهاتف.';
    }

    // التحقق من العنوان
    if ($address === '') {
        $errors['address'] = 'يرجى إدخال عنوان الطالب.';
    }

    // التحقق من الصورة
    $picture = $_FILES['profile_picture'] ?? null;
    $picturePath = null;
    $destination = null;

    if ($picture && $picture['error'] !== UPLOAD_ERR_NO_FILE) {

        if ($picture['error'] !== UPLOAD_ERR_OK) {

            $uploadErrors = [
                UPLOAD_ERR_INI_SIZE => 'حجم الصورة تجاوز الحد الذي يسمح به الخادم.',
                UPLOAD_ERR_FORM_SIZE => 'حجم الصورة تجاوز الحد المسموح به في النموذج.',
                UPLOAD_ERR_PARTIAL => 'لم يتم رفع الصورة بالكامل.',
                UPLOAD_ERR_NO_TMP_DIR => 'مجلد الملفات المؤقتة غير موجود على الخادم.',
                UPLOAD_ERR_CANT_WRITE => 'تعذر حفظ الصورة على الخادم.',
                UPLOAD_ERR_EXTENSION => 'تم إيقاف رفع الصورة بواسطة إعدادات PHP.'
            ];

            $errors['profile_picture'] =
                $uploadErrors[$picture['error']] ?? 'حدث خطأ غير معروف أثناء رفع الصورة.';

        } else {

            // التحقق من أن الملف صورة حقيقية
            $imageInfo = @getimagesize($picture['tmp_name']);

            if ($imageInfo === false) {

                $errors['profile_picture'] = 'الملف المرفوع ليس صورة صالحة.';

            } else {

                // أنواع الصور المسموحة
                $allowedTypes = [
                    IMAGETYPE_JPEG => 'jpg',
                    IMAGETYPE_PNG => 'png',
                    IMAGETYPE_WEBP => 'webp'
                ];

                $imageType = $imageInfo[2];

                if (!array_key_exists($imageType, $allowedTypes)) {

                    $errors['profile_picture'] =
                        'نوع الصورة غير مسموح. استخدم JPG أو PNG أو WEBP.';

                } else {

                    $extension = $allowedTypes[$imageType];

                    // تحديد مجلد حفظ الصور
                    $uploadDirectory = __DIR__ . '/assets/img/students_img/';

                    // إنشاء المجلد إذا لم يكن موجوداً
                    if (!is_dir($uploadDirectory)) {

                        if (!mkdir($uploadDirectory, 0755, true) && !is_dir($uploadDirectory)) {
                            $errors['profile_picture'] =
                                'تعذر إنشاء مجلد حفظ الصور. تحقق من صلاحيات المجلد.';
                        }

                    }

                    // التحقق من صلاحيات الكتابة
                    if (
                        empty($errors['profile_picture']) &&
                        !is_writable($uploadDirectory)
                    ) {

                        $errors['profile_picture'] =
                            'مجلد الصور لا يملك صلاحية الكتابة. تحقق من صلاحيات المجلد.';
                    }

                    if (empty($errors['profile_picture'])) {

                        // إنشاء اسم عشوائي للصورة
                        $fileName = bin2hex(random_bytes(16)) . '.' . $extension;

                        $destination = $uploadDirectory . $fileName;

                        $picturePath = 'assets/img/students_img/' . $fileName;
                    }
                }
            }
        }
    }


    // إيقاف العملية عند وجود أخطاء
    if (!empty($errors)) {

        http_response_code(422);

        echo json_encode([
            'success' => false,
            'errors' => $errors
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }


    try {

        // التأكد من عدم تكرار البريد الإلكتروني
        $checkEmail = $pdo->prepare(
            "SELECT id FROM students WHERE email = :email LIMIT 1"
        );

        $checkEmail->execute([
            ':email' => $email
        ]);

        if ($checkEmail->fetch()) {

            http_response_code(409);

            echo json_encode([
                'success' => false,
                'errors' => [
                    'email' => 'هذا البريد الإلكتروني مسجل مسبقاً.'
                ]
            ], JSON_UNESCAPED_UNICODE);

            exit;
        }


        // نقل الصورة إلى مجلد الصور
        if ($destination !== null) {

            if (!move_uploaded_file($picture['tmp_name'], $destination)) {

                http_response_code(500);

                echo json_encode([
                    'success' => false,
                    'errors' => [
                        'profile_picture' => 'تعذر نقل الصورة إلى مجلد الحفظ. تحقق من صلاحيات المجلد.'
                    ]
                ], JSON_UNESCAPED_UNICODE);

                exit;
            }
        }


        // إضافة بيانات الطالب إلى قاعدة البيانات
        $query = "INSERT INTO students
                  (name, email, phone, address, profile_picture)
                  VALUES
                  (:name, :email, :phone, :address, :profile_picture)";

        $stmt = $pdo->prepare($query);

        $stmt->execute([
            ':name' => $name,
            ':email' => $email,
            ':phone' => $phone,
            ':address' => $address,
            ':profile_picture' => $picturePath
        ]);


        // نجاح العملية
        echo json_encode([
            'success' => true,
            'message' => 'تمت إضافة الطالب بنجاح.'
        ], JSON_UNESCAPED_UNICODE);

    } catch (PDOException $e) {

        // حذف الصورة إذا فشلت عملية الحفظ في قاعدة البيانات
        if ($destination !== null && file_exists($destination)) {
            unlink($destination);
        }

        if ($e->getCode() === '23000') {

            http_response_code(409);

            echo json_encode([
                'success' => false,
                'errors' => [
                    'email' => 'هذا البريد الإلكتروني مسجل مسبقاً.'
                ]
            ], JSON_UNESCAPED_UNICODE);

        } else {

            http_response_code(500);

            echo json_encode([
                'success' => false,
                'message' => 'حدث خطأ أثناء حفظ بيانات الطالب في قاعدة البيانات.'
            ], JSON_UNESCAPED_UNICODE);
        }

    } catch (Throwable $e) {

        if ($destination !== null && file_exists($destination)) {
            unlink($destination);
        }

        http_response_code(500);

        echo json_encode([
            'success' => false,
            'message' => 'حدث خطأ غير متوقع أثناء إضافة الطالب.'
        ], JSON_UNESCAPED_UNICODE);
    }

    exit;
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

        <!-- Page Title -->
        <div class="text-center mb-4">

            <h2 class="fw-bold" style="color: #087a54;">
                إضافة طالب جديد
            </h2>

            <p class="text-secondary small">
                أدخل المعلومات المطلوبة لإضافة طالب إلى قاعدة البيانات.
            </p>

        </div>


        <!-- Form Container -->
        <div class="row justify-content-center">

            <div class="col-12 col-md-9 col-lg-7">

                <div class="card border shadow-sm">

                    <div class="card-body p-4">

                        <!-- Form Heading -->
                        <div class="d-flex align-items-center gap-2 mb-4">

                            <i class="bi bi-person-plus-fill fs-4"
                               style="color: #087a54;"></i>

                            <h5 class="fw-bold mb-0">
                                معلومات الطالب
                            </h5>

                        </div>


                        <!-- Response Message -->
                        <div id="formMessage"></div>


                        <!-- Student Form -->
                        <form id="addStudentForm"
                              enctype="multipart/form-data"
                              novalidate>


                            <!-- Name -->
                            <div class="mb-3">

                                <label for="name" class="form-label">
                                    اسم الطالب
                                </label>

                                <input type="text"
                                       class="form-control"
                                       id="name"
                                       name="name"
                                       placeholder="أدخل اسم الطالب"
                                       required>

                                <div class="invalid-feedback"></div>

                            </div>


                            <!-- Email -->
                            <div class="mb-3">

                                <label for="email" class="form-label">
                                    البريد الإلكتروني
                                </label>

                                <input type="email"
                                       class="form-control"
                                       id="email"
                                       name="email"
                                       placeholder="example@email.com"
                                       required>

                                <div class="invalid-feedback"></div>

                            </div>


                            <!-- Phone -->
                            <div class="mb-3">

                                <label for="phone" class="form-label">
                                    رقم الهاتف
                                </label>

                                <input type="tel"
                                       class="form-control"
                                       id="phone"
                                       name="phone"
                                       placeholder="أدخل رقم الهاتف"
                                       required>

                                <div class="invalid-feedback"></div>

                            </div>


                            <!-- Address -->
                            <div class="mb-3">

                                <label for="address" class="form-label">
                                    العنوان
                                </label>

                                <textarea class="form-control"
                                          id="address"
                                          name="address"
                                          rows="3"
                                          placeholder="أدخل عنوان الطالب"
                                          required></textarea>

                                <div class="invalid-feedback"></div>

                            </div>


                            <!-- Profile Picture -->
                            <div class="mb-4">

                                <label for="profile_picture" class="form-label">
                                    الصورة الشخصية
                                    <span class="text-secondary small">(اختياري)</span>
                                </label>

                                <input type="file"
                                       class="form-control"
                                       id="profile_picture"
                                       name="profile_picture"
                                       accept="image/jpeg,image/png,image/webp">

                                <div class="invalid-feedback"></div>

                                <div class="form-text">
                                    الصيغ المسموحة: JPG و PNG و WEBP.
                                </div>

                            </div>


                            <!-- Buttons -->
                            <div class="d-flex gap-2">

                                <button type="submit"
                                        class="btn btn-success flex-grow-1"
                                        id="submitButton">

                                    <i class="bi bi-person-plus-fill"></i>
                                    إضافة الطالب

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
