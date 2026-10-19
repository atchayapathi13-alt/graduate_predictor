/**
 * Interactive Student Form & Rating Handler
 * Graduate Predicting Analysis System
 */

document.addEventListener('DOMContentLoaded', function () {
    // 1. Interactive Star / Rating Badges for Skills
    const ratingBadges = document.querySelectorAll('.rating-badge');
    ratingBadges.forEach(function (badge) {
        badge.addEventListener('click', function () {
            const val = this.getAttribute('data-value');
            const parent = this.closest('.rating-container');
            if (!parent) return;

            const hiddenInput = parent.querySelector('input[type="hidden"]');
            if (hiddenInput) {
                hiddenInput.value = val;
            }

            // Update active state among siblings
            parent.querySelectorAll('.rating-badge').forEach(function (b) {
                b.classList.remove('active');
            });
            this.classList.add('active');
        });
    });

    // 2. Client-side Real-time Input Validation
    const cgpaInput = document.getElementById('current_cgpa');
    if (cgpaInput) {
        cgpaInput.addEventListener('input', function () {
            const val = parseFloat(this.value);
            if (val < 0 || val > 10.0 || isNaN(val)) {
                this.classList.add('is-invalid');
            } else {
                this.classList.remove('is-invalid');
            }
        });
    }

    const attendanceInput = document.getElementById('attendance_percentage');
    if (attendanceInput) {
        attendanceInput.addEventListener('input', function () {
            const val = parseFloat(this.value);
            if (val < 0 || val > 100.0 || isNaN(val)) {
                this.classList.add('is-invalid');
            } else {
                this.classList.remove('is-invalid');
            }
        });
    }

    // 3. Internship Yes/No conditional collapse
    const internshipSelect = document.getElementById('has_internship');
    const internshipFields = document.getElementById('internship_details_section');
    if (internshipSelect && internshipFields) {
        internshipSelect.addEventListener('change', function () {
            if (this.value === '1') {
                internshipFields.style.display = 'block';
            } else {
                internshipFields.style.display = 'none';
            }
        });
    }
});
