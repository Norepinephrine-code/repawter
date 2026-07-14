<?php
/** @var string $status */
$statusLabels = [
    'submitted'    => 'Submitted',
    'under_review' => 'Under Review',
    'verified'     => 'Verified',
    'rejected'     => 'Rejected',
    'assigned'     => 'Assigned',
    'in_progress'  => 'In Progress',
    'resolved'     => 'Resolved',
    'archived'     => 'Archived',
];
$label = $statusLabels[$status ?? ''] ?? ucfirst(str_replace('_', ' ', $status ?? ''));
?>
<span class="badge badge-status--<?= e($status ?? '') ?>"><?= e($label) ?></span>
