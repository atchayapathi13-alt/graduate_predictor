<?php
/**
 * Flash Alert Messages Component
 * Graduate Predicting Analysis System
 */

$flashes = getFlashes();
foreach ($flashes as $type => $message):
    $alertClass = 'alert-info';
    $icon = 'fa-info-circle';
    if ($type === 'success') {
        $alertClass = 'alert-success';
        $icon = 'fa-check-circle';
    } elseif ($type === 'error') {
        $alertClass = 'alert-danger';
        $icon = 'fa-exclamation-circle';
    } elseif ($type === 'warning') {
        $alertClass = 'alert-warning';
        $icon = 'fa-triangle-exclamation';
    }
?>
<div class="alert <?= $alertClass ?> alert-dismissible fade show d-flex align-items-center mb-4 shadow-sm" role="alert">
    <i class="fas <?= $icon ?> me-2 fs-5"></i>
    <div class="flex-grow-1"><?= sanitize($message) ?></div>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endforeach; ?>
