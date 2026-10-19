<?php
/**
 * Master Footer Component
 * Graduate Predicting Analysis System
 */
?>
        </div> <!-- End app-content -->

        <footer class="py-3 px-4 bg-white border-top text-center text-muted small">
            <div class="container-fluid d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2">
                <span>&copy; <?= date('Y') ?> <strong>Graduate Predicting Analysis System</strong>. Academic Decision Support Prototype.</span>
                <span class="text-secondary"><i class="fas fa-shield-cat me-1"></i> Transparent AI & Rule-Based Analytics</span>
            </div>
        </footer>
    </main> <!-- End app-main -->
</div> <!-- End app-wrapper -->

<!-- Bootstrap 5.3 Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Custom App Scripts -->
<script src="<?= baseUrl('assets/js/main.js') ?>"></script>
<script src="<?= baseUrl('assets/js/charts.js') ?>"></script>
<script src="<?= baseUrl('assets/js/student-form.js') ?>"></script>

</body>
</html>
