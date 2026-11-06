
<?php

session_start();

require_once __DIR__ . '/includes/db.php';

$errors = [];
$success = false;

$username = "";
$email = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Get form data
    $username = trim($_POST["username"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";


    // Validate empty fields
    if (empty($username) || empty($email) ||
        empty($password) || empty($confirm_password)) {

        $errors[] = "يرجى ملء جميع الحقول.";

    }


    // Validate email
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $errors[] = "يرجى إدخال بريد إلكتروني صحيح.";

    }


    // Validate password confirmation
    if ($password !== $confirm_password) {

        $errors[] = "كلمتا المرور غير متطابقتين.";

    }


    // Check username and email
    if (empty($errors)) {

        $checkQuery = "SELECT id, username, email
                       FROM users
                       WHERE username = :username
                       OR email = :email";

        $checkStmt = $pdo->prepare($checkQuery);

        $checkStmt->execute([
            ':username' => $username,
            ':email' => $email
        ]);

        $existingUser = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if ($existingUser) {

            if ($existingUser['username'] === $username) {
                $errors[] = "اسم المستخدم مستخدم مسبقاً.";
            }

            if ($existingUser['email'] === $email) {
                $errors[] = "البريد الإلكتروني مستخدم مسبقاً.";
            }

        }

    }


    // Insert new user
    if (empty($errors)) {

        try {

            // Hash password
            $hashedPassword = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            $insertQuery = "INSERT INTO users
                            (username, email, password)
                            VALUES
                            (:username, :email, :password)";

            $insertStmt = $pdo->prepare($insertQuery);

            $insertStmt->execute([
                ':username' => $username,
                ':email' => $email,
                ':password' => $hashedPassword
            ]);

            $success = true;

            // Clear form fields
            $username = "";
            $email = "";

        } catch (PDOException $e) {

            if ($e->getCode() === '23000') {

                $errors[] = "اسم المستخدم أو البريد الإلكتروني مستخدم مسبقاً.";

            } else {

                $errors[] = "حدث خطأ أثناء إنشاء الحساب، يرجى المحاولة لاحقاً.";

            }

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
                إنشاء حساب جديد
            </h2>

            <p class="text-secondary">
                أنشئ حسابك للبدء باستخدام بوابة SVU وإدارة بيانات الطلاب.
            </p>

        </div>


        <!-- Registration Form Card -->
        <div class="row justify-content-center">

            <div class="col-md-8 col-lg-6">

                <div class="card border shadow-sm rounded-3">

                    <div class="card-body p-4">

                        <h5 class="fw-bold mb-4 text-center">
                            <i class="bi bi-person-plus-fill"
                               style="color: #159b65;"></i>

                            بيانات الحساب
                        </h5>


                        <!-- Success Message -->
                        <?php if ($success): ?>

                            <div class="alert alert-success fade show"
                                 role="alert">

                                <i class="bi bi-check-circle-fill"></i>

                                تم إنشاء حسابك بنجاح!
                                يمكنك الآن تسجيل الدخول.

                                <a href="login.php" class="alert-link">
                                    تسجيل الدخول
                                </a>

                            </div>

                        <?php endif; ?>


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


                        <!-- Registration Form -->
                        <form action="register.php" method="POST">

                            <!-- Username -->
                            <div class="mb-3">

                                <label for="username" class="form-label">
                                    اسم المستخدم
                                </label>

                                <input type="text"
                                       class="form-control"
                                       id="username"
                                       name="username"
                                       placeholder="أدخل اسم المستخدم"
                                       value="<?php echo htmlspecialchars($username); ?>"
                                       required>

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
                                       value="<?php echo htmlspecialchars($email); ?>"
                                       required>

                            </div>


                            <!-- Password -->
                            <div class="mb-3">

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


                            <!-- Confirm Password -->
                            <div class="mb-4">

                                <label for="confirm_password" class="form-label">
                                    تأكيد كلمة المرور
                                </label>

                                <input type="password"
                                       class="form-control"
                                       id="confirm_password"
                                       name="confirm_password"
                                       placeholder="أعد إدخال كلمة المرور"
                                       required>

                            </div>


                            <!-- Submit Button -->
                            <div class="d-grid">

                                <button type="submit"
                                        class="btn btn-success py-2">

                                    <i class="bi bi-person-check-fill"></i>

                                    إنشاء الحساب

                                </button>

                            </div>

                        </form>


                        <!-- Login Link -->
                        <div class="text-center mt-4">

                            <p class="text-secondary mb-0">

                                لديك حساب بالفعل؟

                                <a href="login.php"
                                   class="text-decoration-none fw-bold"
                                   style="color: #159b65;">

                                    تسجيل الدخول

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
