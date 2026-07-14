<?php
require __DIR__ . '/../../app/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect($_SERVER['HTTP_REFERER'] ?? '/');
}

csrf_verify();
require_role(ROLE_OFFICIAL, ROLE_WELFARE, ROLE_ADMIN);

$id = isset($_POST['id']) && $_POST['id'] !== '' ? (int)$_POST['id'] : 0;

// Quick-publish shortcut (from the index table)
$quickPublish = !empty($_POST['_quick_publish']);

if ($quickPublish && $id > 0) {
    $ann = AnnouncementModel::find($id);
    if ($ann && $ann['status'] !== 'published') {
        AnnouncementModel::publish($id, (int)user_id());
        notify_role(
            ROLE_RESIDENT,
            'announcement',
            'New announcement: ' . mb_substr($ann['title'], 0, 200),
            mb_substr(strip_tags($ann['body']), 0, 180),
            '/announcements/view.php?id=' . $id
        );
    }
    flash('success', 'Announcement published.');
    redirect('/admin/announcements/index.php');
}

// ------------------------------------------------------------------
// Full save / create / update
// ------------------------------------------------------------------

$validCategories = ['vaccination_drive', 'adoption_event', 'tnr_schedule', 'welfare_operation', 'general'];
$validStatuses   = ['draft', 'scheduled', 'published'];

[$clean, $errors] = validate($_POST, [
    'category'      => 'required|in:vaccination_drive,adoption_event,tnr_schedule,welfare_operation,general',
    'title'         => 'required|max:200',
    'body'          => 'required',
    'status'        => 'required|in:draft,scheduled,published',
    'location_text' => 'nullable',
    'event_start'   => 'nullable',
    'event_end'     => 'nullable',
    'facebook_url'  => 'nullable',
    'publish_at'    => 'nullable',
    'barangay_id'   => 'nullable',
]);

// Validate facebook_url format if provided
if (!empty($clean['facebook_url'])) {
    if (!filter_var($clean['facebook_url'], FILTER_VALIDATE_URL)) {
        $errors['facebook_url'] = 'Facebook URL must be a valid URL.';
    }
}

if (!empty($errors)) {
    flash_old($_POST);
    flash('error', 'Please fix the errors below.');
    $back = $id > 0
        ? '/admin/announcements/edit.php?id=' . $id
        : '/admin/announcements/edit.php';
    redirect($back);
}

// Normalize empty datetime strings to null
$nullableFields = ['event_start', 'event_end', 'publish_at'];
foreach ($nullableFields as $f) {
    if (isset($clean[$f]) && trim($clean[$f]) === '') {
        $clean[$f] = null;
    }
}

// Normalize nullable strings
foreach (['location_text', 'facebook_url', 'barangay_id'] as $f) {
    if (isset($clean[$f]) && trim($clean[$f]) === '') {
        $clean[$f] = null;
    }
}

// is_all_day comes as checkbox ('1' or absent)
$isAllDay = !empty($_POST['is_all_day']) ? 1 : 0;

$wasPublished = false;
$prevStatus   = null;

if ($id > 0) {
    // Update existing
    $existing   = AnnouncementModel::find($id);
    $prevStatus = $existing['status'] ?? null;

    AnnouncementModel::update($id, [
        'barangay_id'   => $clean['barangay_id'] ?? null,
        'category'      => $clean['category'],
        'title'         => $clean['title'],
        'body'          => $clean['body'],
        'location_text' => $clean['location_text'] ?? null,
        'event_start'   => $clean['event_start']   ?? null,
        'event_end'     => $clean['event_end']      ?? null,
        'is_all_day'    => $isAllDay,
        'facebook_url'  => $clean['facebook_url']   ?? null,
        'status'        => $clean['status'],
        'publish_at'    => $clean['publish_at']     ?? null,
        'published_at'  => ($clean['status'] === 'published' && $prevStatus !== 'published')
                              ? date('Y-m-d H:i:s')
                              : ($existing['published_at'] ?? null),
    ]);

    $wasPublished = ($clean['status'] === 'published' && $prevStatus !== 'published');
    flash('success', 'Announcement updated.');
} else {
    // Create new
    $publishedAt = $clean['status'] === 'published' ? date('Y-m-d H:i:s') : null;

    $id = AnnouncementModel::create([
        'author_id'     => user_id(),
        'barangay_id'   => $clean['barangay_id'] ?? null,
        'category'      => $clean['category'],
        'title'         => $clean['title'],
        'body'          => $clean['body'],
        'location_text' => $clean['location_text'] ?? null,
        'event_start'   => $clean['event_start']   ?? null,
        'event_end'     => $clean['event_end']      ?? null,
        'is_all_day'    => $isAllDay,
        'facebook_url'  => $clean['facebook_url']   ?? null,
        'status'        => $clean['status'],
        'publish_at'    => $clean['publish_at']     ?? null,
        'published_at'  => $publishedAt,
    ]);

    $wasPublished = ($clean['status'] === 'published');
    flash('success', 'Announcement created.');
}

// Notify residents when an announcement becomes published for the first time
if ($wasPublished) {
    notify_role(
        ROLE_RESIDENT,
        'announcement',
        'New announcement: ' . mb_substr($clean['title'], 0, 200),
        mb_substr(strip_tags($clean['body']), 0, 180),
        '/announcements/view.php?id=' . $id
    );
}

redirect('/admin/announcements/index.php');
