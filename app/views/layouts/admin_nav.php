<?php

$active = $active ?? '';

$navItems = [
    ['key' => 'dashboard',    'label' => 'Dashboard',         'icon' => 'bi-speedometer2',      'url' => '/admin/dashboard.php',           'ability' => null],
    ['key' => 'reports',      'label' => 'Reports',            'icon' => 'bi-flag-fill',          'url' => '/admin/reports/',                'ability' => 'manage_reports'],
    ['key' => 'cases',        'label' => 'Case Tracking',      'icon' => 'bi-clipboard2-pulse',   'url' => '/admin/reports/case_tracking.php','ability' => 'track_cases'],
    ['key' => 'foster',       'label' => 'Foster Applications','icon' => 'bi-house-heart',        'url' => '/admin/foster/',                 'ability' => 'review_foster'],
    ['key' => 'pets',         'label' => 'Pets',               'icon' => 'bi-heart-fill',         'url' => '/admin/pets/',                   'ability' => 'manage_pets'],
    ['key' => 'adoption',     'label' => 'Adoptions',          'icon' => 'bi-bag-heart',          'url' => '/admin/adoption/',               'ability' => 'review_adoption'],
    ['key' => 'announcements','label' => 'Announcements',      'icon' => 'bi-megaphone-fill',     'url' => '/admin/announcements/',          'ability' => 'manage_announcements'],
    ['key' => 'resources',    'label' => 'Resources',          'icon' => 'bi-journal-richtext',   'url' => '/admin/resources/',              'ability' => 'manage_resources'],
    ['key' => 'users',        'label' => 'Users',              'icon' => 'bi-people-fill',        'url' => '/admin/users/',                  'ability' => 'manage_users'],
    ['key' => 'analytics',    'label' => 'Analytics',          'icon' => 'bi-bar-chart-fill',     'url' => '/admin/analytics/',              'ability' => 'view_analytics'],
    ['key' => 'criteria',     'label' => 'Verification Criteria','icon' => 'bi-check-circle-fill','url' => '/admin/system/criteria.php',     'ability' => 'edit_criteria'],
    ['key' => 'audit',        'label' => 'Audit Logs',         'icon' => 'bi-shield-lock-fill',   'url' => '/admin/system/audit_logs.php',   'ability' => 'view_audit'],
];
?>
<div class="paw-admin-subnav mb-4">
    <nav class="nav nav-pills flex-nowrap" aria-label="Admin sections">
<?php foreach ($navItems as $item): ?>
<?php   if ($item['ability'] !== null && !can($item['ability'])) continue; ?>
<?php   $isActive = ($active === $item['key']); ?>
        <a href="<?= url($item['url']) ?>"
           class="nav-link paw-admin-nav-link<?= $isActive ? ' active' : '' ?>"
           title="<?= e($item['label']) ?>">
            <i class="bi <?= e($item['icon']) ?> me-1"></i>
            <span class="admin-nav-label"><?= e($item['label']) ?></span>
        </a>
<?php endforeach; ?>
    </nav>
</div>
