
<?php

session_start();

require_once __DIR__ . '/includes/db.php';

$errors = [];
$identifier = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Get form data
    $identifier = trim($_POST["identifier"] ?? "");
    $password = $_POST["password"] ?? "";


    // Validate empty fields
    if (empty($identifier) || empty($password)) {

        $errors[] = "يرجى إدخال اسم المستخدم أو البريد الإلكتروني وكلمة المرور.";

    }


    // Check user credentials
    if (empty($errors)) {

$query = "SELECT id, username, email, password
          FROM users
          WHERE username = :username
          OR email = :email";

$stmt = $pdo->prepare($query);

$stmt->execute([
    ':username' => $identifier,
    ':email' => $identifier
]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);


        // Verify password
        if ($user && password_verify($password, $user['password'])) {

            // Regenerate session ID
            session_regenerate_id(true);

            // Store user information in session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];

            // Redirect after successful login
            header("Location: index.php");
            exit;

        } else {

            $errors[] = "اسم المستخدم أو البريد الإلكتروني أو كلمة المرور غير صحيحة.";

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

        <!-- Page Title -->
        <div class="text-center mb-4">

            <h2 class="fw-bold" style="color: #087a54;">
                تسجيل الدخول
            </h2>

            <p class="text-secondary">
                سجل الدخول إلى حسابك للوصول إلى بوابة SVU وإدارة بيانات الطلاب.
            </p>

        </div>


        <!-- Login Form Card -->
        <div class="row justify-content-center">

            <div class="col-md-8 col-lg-6">

                <div class="card border shadow-sm rounded-3">

                    <div class="card-body p-4">

                        <h5 class="fw-bold mb-4 text-center">

                            <i class="bi bi-box-arrow-in-left"
                               style="color: #159b65;"></i>

                            بيانات تسجيل الدخول

                        </h5>


                        <!-- Error Messages -->
                        <?php if (!empty($errors)): ?>

                            <div class="alert alert-danger fade show"
                                 role="alert">

                                <ul class="mb-0">

                                    <?php foreach ($errors as $error): ?>

                                        <li>
                                            <?php echo htmlspecialchars($error); ?>
                                        </li>

                                    <?php endforeach; ?>

                                </ul>

                            </div>

                        <?php endif; ?>


                        <!-- Login Form -->
                        <form action="login.php" method="POST">

                            <!-- Username or Email -->
                            <div class="mb-3">

                                <label for="identifier" class="form-label">
                                    اسم المستخدم أو البريد الإلكتروني
                                </label>

                                <input type="text"
                                       class="form-control"
                                       id="identifier"
                                       name="identifier"
                                       placeholder="أدخل اسم المستخدم أو البريد الإلكتروني"
                                       value="<?php echo htmlspecialchars($identifier); ?>"
                                       required>

                            </div>


                            <!-- Password -->
                            <div class="mb-4">

                                <label for="password" class="form-label">
                                    كلمة المرور
                                </label>

                                <input type="password"
                                       class="form-control"
                                       id="password"
                                       name="password"
                                       placeholder="أدخل كلمة المرور"
                                       required>

                            </div>


                            <!-- Submit Button -->
                            <div class="d-grid">

                                <button type="submit"
                                        class="btn btn-success py-2">

                                    <i class="bi bi-box-arrow-in-left"></i>

                                    تسجيل الدخول

                                </button>

                            </div>

                        </form>


                        <!-- Register Link -->
                        <div class="text-center mt-4">

                            <p class="text-secondary mb-0">

                                ليس لديك حساب؟

                                <a href="register.php"
                                   class="text-decoration-none fw-bold"
                                   style="color: #159b65;">

                                    إنشاء حساب جديد

                                </a>

                            </p>

                        </div>

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
