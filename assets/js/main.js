/**
 * Global Application JavaScript
 * Graduate Predicting Analysis System
 */

document.addEventListener('DOMContentLoaded', function () {
    // 1. Mobile Sidebar Toggle
    const sidebar = document.querySelector('.app-sidebar');
    const toggler = document.querySelector('.sidebar-toggler');
    const overlay = document.querySelector('.sidebar-overlay');

    if (toggler && sidebar) {
        toggler.addEventListener('click', function () {
            sidebar.classList.toggle('show');
            if (overlay) overlay.classList.toggle('show');
        });
    }

    if (overlay) {
        overlay.addEventListener('click', function () {
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
        });
    }

    // 2. Initialize Bootstrap Tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // 3. Auto-dismiss alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(function (alert) {
        setTimeout(function () {
            const bsAlert = bootstrap.Alert.getInstance(alert) || new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });

    // 4. Quick Demo Login Autofill Helper
    const demoButtons = document.querySelectorAll('.btn-demo-login');
    demoButtons.forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const email = this.getAttribute('data-email');
            const role = this.getAttribute('data-role');
            const emailInput = document.getElementById('loginEmail');
            const passInput = document.getElementById('loginPassword');
            const form = document.getElementById('loginForm');

            if (emailInput && passInput) {
                emailInput.value = email;
                passInput.value = 'Password@123';
                if (form) {
                    form.submit();
                }
            }
        });
    });
});
