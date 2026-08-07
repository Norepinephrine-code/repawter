<?php
require_once __DIR__ . '/../../app/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect($_SERVER['HTTP_REFERER'] ?? '/admin/resources/index.php');
}

csrf_verify();
require_role(ROLE_ADMIN, ROLE_OFFICIAL, ROLE_WELFARE);

$id = isset($_POST['id']) && $_POST['id'] !== '' ? (int)$_POST['id'] : null;

[$clean, $errors] = validate($_POST, [
    'category_id'  => 'required|int',
    'title'        => 'required|max:200',
    'body'         => 'required',
    'is_published' => 'nullable|in:0,1',
]);

if (!empty($errors)) {
    flash_old($_POST);
    flash_errors($errors);
    $back = $id !== null
        ? '/admin/resources/edit.php?id=' . $id
        : '/admin/resources/edit.php';
    redirect($back);
}

$slug = sanitize_string($_POST['slug'] ?? '');
if ($slug === '') {

    $slug = strtolower($clean['title']);
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    $slug = trim($slug, '-');
}

$baseSlug  = $slug;
$suffix    = 1;
while (true) {
    $existing = db_one(
        'SELECT id FROM educational_resources WHERE slug = ? LIMIT 1',
        [$slug]
    );
    if ($existing === null || (int)$existing['id'] === $id) {
        break;
    }
    $suffix++;
    $slug = $baseSlug . '-' . $suffix;
}

$coverPath = null;
if (isset($_FILES['cover']) && $_FILES['cover']['error'] !== UPLOAD_ERR_NO_FILE) {
    $upload = handle_upload($_FILES['cover'], 'resources');
    if (!$upload['ok']) {
        flash_old($_POST);
        flash('error', 'Cover image upload failed: ' . ($upload['error'] ?? 'Unknown error'));
        $back = $id !== null
            ? '/admin/resources/edit.php?id=' . $id
            : '/admin/resources/edit.php';
        redirect($back);
    }
    $coverPath = $upload['path'];
}

$isPublished = (int)($clean['is_published'] ?? 0) === 1;

$publishedAt = null;
if ($isPublished) {
    if ($id !== null) {
        $existing = ResourceModel::find($id);
        $publishedAt = $existing['published_at'] ?? date('Y-m-d H:i:s');
    } else {
        $publishedAt = date('Y-m-d H:i:s');
    }
}

$data = [
    'category_id'      => (int)$clean['category_id'],
    'author_id'        => user_id(),
    'title'            => $clean['title'],
    'slug'             => $slug,
    'body'             => $clean['body'],
    'summary'          => sanitize_string($_POST['summary'] ?? ''),
    'cover_image_path' => $coverPath,
    'is_published'     => $isPublished,
    'published_at'     => $publishedAt,
];

if ($id !== null) {
    ResourceModel::update($id, $data);
    flash('success', 'Resource updated successfully.');
} else {
    ResourceModel::create($data);
    flash('success', 'Resource created successfully.');
}

redirect('/admin/resources/index.php');
