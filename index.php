
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isLoggedIn = isset($_SESSION['user_id']);
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

<main class="flex-grow-1">

    <!-- Hero Section -->
    <section class="py-5">
        <div class="container">

            <div class="row align-items-center g-4">

                <!-- Hero Text -->
                <div class="col-lg-6">

                    <h1 class="fw-bold mb-3" style="color: #087a54;">
                        مرحباً بك في بوابة
                        <span style="color: #159b65;">SVU</span>
                    </h1>

                    <p class="lead text-secondary mb-4">
                        منصتك لإدارة وتسجيل بيانات الطلاب بسهولة وفعالية.
                        توفر لك بوابة SVU نظاماً متكاملاً لإدارة سجلات
                        الطلاب والوصول إلى بياناتهم وتنظيمها بطريقة سهلة
                        وسريعة.
                    </p>

                    <?php if ($isLoggedIn): ?>

                        <a href="view_students.php"
                           class="btn btn-success px-4 py-2 me-2">
                            <i class="bi bi-people"></i>
                            عرض الطلاب
                        </a>

                        <a href="add_student.php"
                           class="btn btn-outline-success px-4 py-2">
                            <i class="bi bi-person-plus"></i>
                            إضافة طالب
                        </a>

                    <?php else: ?>

                        <a href="register.php"
                           class="btn btn-success px-4 py-2 me-2">
                            <i class="bi bi-person-plus"></i>
                            إنشاء حساب
                        </a>

                        <a href="login.php"
                           class="btn btn-outline-success px-4 py-2">
                            <i class="bi bi-box-arrow-in-left"></i>
                            تسجيل الدخول
                        </a>

                    <?php endif; ?>

                </div>


                <!-- Hero Image -->
                <div class="col-lg-6">

                    <img src="assets/img/hero.jpeg"
                         alt="بوابة الجامعة الافتراضية السورية"
                         class="img-fluid rounded-4 shadow-sm w-100">

                </div>

            </div>

        </div>
    </section>


    <!-- Features Section -->
    <section class="py-5 bg-light">

        <div class="container">

            <div class="text-center mb-5">

                <h2 class="fw-bold" style="color: #087a54;">
                    مميزات بوابة SVU
                </h2>

                <p class="text-secondary">
                    كل ما تحتاجه لإدارة بيانات الطلاب في مكان واحد.
                </p>

            </div>


            <div class="row g-4">

                <!-- Feature 1 -->
                <div class="col-md-4">

                    <div class="card border-0 shadow-sm h-100 text-center p-3">

                        <div class="card-body">

                            <i class="bi bi-people-fill fs-1"
                               style="color: #159b65;"></i>

                            <h5 class="card-title fw-bold mt-3">
                                إدارة الطلاب
                            </h5>

                            <p class="card-text text-secondary">
                                إمكانية إضافة سجلات الطلاب وعرض بياناتهم
                                وتحديثها وحذفها بسهولة من خلال واجهة
                                بسيطة ومنظمة.
                            </p>

                        </div>

                    </div>

                </div>


                <!-- Feature 2 -->
                <div class="col-md-4">

                    <div class="card border-0 shadow-sm h-100 text-center p-3">

                        <div class="card-body">

                            <i class="bi bi-search fs-1"
                               style="color: #159b65;"></i>

                            <h5 class="card-title fw-bold mt-3">
                                البحث والتنظيم
                            </h5>

                            <p class="card-text text-secondary">
                                الوصول إلى بيانات الطلاب بسهولة من خلال
                                البحث والفرز، مع إمكانية تقسيم النتائج
                                إلى صفحات لتسهيل التصفح.
                            </p>

                        </div>

                    </div>

                </div>


                <!-- Feature 3 -->
                <div class="col-md-4">

                    <div class="card border-0 shadow-sm h-100 text-center p-3">

                        <div class="card-body">

                            <i class="bi bi-shield-lock-fill fs-1"
                               style="color: #159b65;"></i>

                            <h5 class="card-title fw-bold mt-3">
                                حماية الحسابات
                            </h5>

                            <p class="card-text text-secondary">
                                نظام لتسجيل الحسابات وتسجيل الدخول
                                والخروج، مع حماية كلمات المرور
                                وإدارة جلسات المستخدمين.
                            </p>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </section>

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
