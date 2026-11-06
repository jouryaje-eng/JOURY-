<?php

session_start();

require_once __DIR__ . '/includes/db.php';

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$isLoggedIn = isset($_SESSION['user_id']);

// متغير البحث
$search = trim($_GET['search'] ?? '');

$students = [];
$error = '';

try {

    // جلب الطلاب مع إمكانية البحث
    $query = "SELECT id, name, email, phone, address,
                     profile_picture, created_at
              FROM students";

    // إضافة شروط البحث عند إدخال كلمة
    if ($search !== '') {

        $query .= " WHERE name LIKE :search_name
                    OR email LIKE :search_email
                    OR phone LIKE :search_phone";

    }

    $query .= " ORDER BY id DESC";

    $stmt = $pdo->prepare($query);

    // تمرير قيمة البحث
    if ($search !== '') {

        $searchValue = "%$search%";

        $stmt->execute([
            ':search_name' => $searchValue,
            ':search_email' => $searchValue,
            ':search_phone' => $searchValue
        ]);

    } else {

        $stmt->execute();

    }

    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    $error = 'حدث خطأ أثناء تحميل بيانات الطلاب.';

}

?>


<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>بوابة SVU</title>

    <!-- Bootstrap RTL CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.rtl.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

    <!-- Header -->
    <header>

        <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">

            <div class="container">

                <!-- Logo and Site Name -->
                <a class="navbar-brand d-flex align-items-center gap-2" href="index.php">

                    <img src="assets/img/logo.jpeg" alt="شعار بوابة SVU" class="navbar-logo">

                    <span class="fw-bold site-name">بوابة SVU</span>

                </a>

                <!-- Mobile Toggle -->
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent"
                    aria-controls="navbarContent" aria-expanded="false" aria-label="فتح القائمة">

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
                            <a class="btn btn-logout" href="logout.php">

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
                            <a class="btn btn-register" href="register.php">

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

    <!-- Main Content -->
    <main class="flex-grow-1 py-5">

        <div class="container">

            <!-- Page Title -->
            <div class="text-center mb-4">

                <h2 class="fw-bold" style="color: #087a54;">
                    إدارة الطلاب
                </h2>

                <p class="text-secondary small mb-0">
                    عرض والبحث عن بيانات الطلاب المسجلين في النظام.
                </p>

            </div>


            <!-- Add Student Button -->
            <div class="d-flex justify-content-start mb-4">

                <a href="add_student.php" class="btn btn-success">

                    <i class="bi bi-person-plus-fill"></i>
                    إضافة طالب

                </a>

            </div>


            <!-- Search Box -->
            <div class="row justify-content-center mb-4">

                <div class="col-12 col-md-7 col-lg-5">

                    <form action="view_students.php" method="GET">

                        <div class="input-group">

                            <input type="search" name="search" class="form-control"
                                placeholder="ابحث بالاسم أو البريد أو رقم الهاتف..."
                                value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>">

                            <button type="submit" class="btn btn-success">

                                <i class="bi bi-search"></i>
                                بحث

                            </button>

                        </div>

                    </form>

                </div>

            </div>


            <!-- Students Table -->
            <div class="card border shadow-sm">

                <div class="card-body p-0">

                    <div class="table-responsive">

                        <?php if (isset($_GET['updated'])): ?>

                        <div class="alert alert-success">
                            تم تحديث بيانات الطالب بنجاح.
                        </div>

                        <?php elseif (isset($_GET['deleted'])): ?>

                        <div class="alert alert-success">
                            تم حذف الطالب بنجاح.
                        </div>

                        <?php elseif (isset($_GET['delete_error'])): ?>

                        <div class="alert alert-danger">
                            تعذر حذف الطالب.
                        </div>

                        <?php endif; ?>
                        <table class="table table-hover table-striped align-middle mb-0">

                            <thead class="table-success">

                                <tr>

                                    <th scope="col">الرقم</th>

                                    <th scope="col">اسم الطالب</th>

                                    <th scope="col">البريد الإلكتروني</th>

                                    <th scope="col">رقم الهاتف</th>

                                    <th scope="col">العنوان</th>

                                    <th scope="col" class="text-center">
                                        الصورة
                                    </th>

                                    <th scope="col" class="text-center">
                                        الإجراءات
                                    </th>

                                </tr>

                            </thead>


                            <tbody>

                                <?php if ($error !== ''): ?>

                                <tr>
                                    <td colspan="7" class="text-center text-danger py-4">
                                        <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
                                    </td>
                                </tr>

                                <?php elseif (!empty($students)): ?>

                                <?php foreach ($students as $student): ?>

                                <tr>

                                    <td>
                                        <?php echo (int) $student['id']; ?>
                                    </td>

                                    <td>
                                        <?php echo htmlspecialchars($student['name'], ENT_QUOTES, 'UTF-8'); ?>
                                    </td>

                                    <td>
                                        <?php echo htmlspecialchars($student['email'], ENT_QUOTES, 'UTF-8'); ?>
                                    </td>

                                    <td>
                                        <?php echo htmlspecialchars($student['phone'], ENT_QUOTES, 'UTF-8'); ?>
                                    </td>

                                    <td>
                                        <?php echo htmlspecialchars($student['address'], ENT_QUOTES, 'UTF-8'); ?>
                                    </td>

                                    <td class="text-center">

                                        <?php if (!empty($student['profile_picture'])): ?>

                                        <img src="<?php echo htmlspecialchars($student['profile_picture'], ENT_QUOTES, 'UTF-8'); ?>"
                                            alt="صورة الطالب" width="45" height="45"
                                            class="rounded-circle student-picture">

                                        <?php else: ?>

                                        <i class="bi bi-person-circle fs-3 text-secondary"></i>

                                        <?php endif; ?>

                                    </td>

                                    <td class="text-center">

                                        <!-- Update Button -->
                                        <a href="update_student.php?id=<?= $student['id'] ?>"
                                            class="btn btn-sm btn-outline-success">
                                            Update
                                        </a>

                                        <!-- Delete Button -->
                                        <form action="delete_student.php" method="POST" class="d-inline"
                                            onsubmit="return confirm('هل أنت متأكد من حذف هذا الطالب؟');">

                                            <input type="hidden" name="id" value="<?= $student['id'] ?>">

                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                Delete
                                            </button>

                                        </form>

                                    </td>

                                </tr>

                                <?php endforeach; ?>

                                <?php else: ?>

                                <tr>

                                    <td colspan="7" class="text-center py-5 text-secondary">
                                        <?php if ($search !== ''): ?>

                                        لا توجد نتائج مطابقة لعملية البحث

                                        <?php else: ?>

                                        لا يوجد طلاب مضافون حتى الآن

                                        <?php endif; ?>

                                    </td>

                                </tr>

                                <?php endif; ?>

                            </tbody>

                        </table>

                    </div>

                </div>


                <!-- Table Footer -->
                <div class="card-footer bg-white">

                    <div class="text-center text-secondary">

                        <small>
                            عدد الطلاب: <?php echo count($students); ?>
                        </small>

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