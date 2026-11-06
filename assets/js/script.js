const alerts = document.querySelectorAll(".alert");

alerts.forEach(function (alert) {
  setTimeout(function () {
    const alertInstance = bootstrap.Alert.getOrCreateInstance(alert);

    alertInstance.close();
  }, 3000);
});


document.addEventListener('DOMContentLoaded', function () {

    const form = document.getElementById('addStudentForm');
    const message = document.getElementById('formMessage');
    const submitButton = document.getElementById('submitButton');

    // عرض رسالة عامة
    function showMessage(text, type) {

        const alert = document.createElement('div');

        alert.className = `alert alert-${type}`;
        alert.textContent = text;

        message.innerHTML = '';
        message.appendChild(alert);

    }

    // إزالة أخطاء الحقول
    function clearErrors() {

        form.querySelectorAll('.form-control').forEach(function (input) {
            input.classList.remove('is-invalid');
        });

        form.querySelectorAll('.invalid-feedback').forEach(function (error) {
            error.textContent = '';
        });

    }

    // عرض أخطاء PHP
    function showErrors(errors) {

        Object.keys(errors).forEach(function (field) {

            const input = document.getElementById(field);

            if (!input) {
                return;
            }

            input.classList.add('is-invalid');

            const feedback = input.parentElement.querySelector('.invalid-feedback');

            if (feedback) {
                feedback.textContent = errors[field];
            }

        });

    }


    // إرسال النموذج
    form.addEventListener('submit', function (event) {

        event.preventDefault();

        clearErrors();

        message.innerHTML = '';

        const formData = new FormData(form);

        submitButton.disabled = true;
        submitButton.textContent = 'جاري الإضافة...';

        fetch('add_student.php', {
            method: 'POST',
            body: formData
        })

        .then(async function (response) {

            const result = await response.json();

            return {
                status: response.status,
                data: result
            };

        })

        .then(function (result) {

            const data = result.data;

            if (data.success) {

                showMessage(data.message, 'success');

                form.reset();

                setTimeout(function () {
                    message.innerHTML = '';
                }, 3000);

            } else if (data.errors) {

                showErrors(data.errors);

            } else {

                showMessage(
                    data.message || 'حدث خطأ أثناء إضافة الطالب.',
                    'danger'
                );

            }

        })

        .catch(function () {

            showMessage(
                'تعذر الاتصال بالخادم أو أن الخادم أعاد استجابة غير صالحة.',
                'danger'
            );

        })

        .finally(function () {

            submitButton.disabled = false;

            submitButton.innerHTML =
                '<i class="bi bi-person-plus-fill"></i> إضافة الطالب';

        });

    });

});