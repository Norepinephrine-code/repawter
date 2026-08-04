<?php
require __DIR__ . '/../app/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

$start = isset($_GET['start']) && $_GET['start'] !== ''
    ? (string)$_GET['start']
    : '2000-01-01T00:00:00';

$end = isset($_GET['end']) && $_GET['end'] !== ''
    ? (string)$_GET['end']
    : '2100-12-31T23:59:59';

$categoryColors = [
    'vaccination_drive'  => '#2A9D8F',
    'adoption_event'     => '#F4794E',
    'tnr_schedule'       => '#6C5CE7',
    'welfare_operation'  => '#0984E3',
    'general'            => '#636E72',
];

$rows   = AnnouncementModel::events_between($start, $end);
$events = [];

foreach ($rows as $row) {
    $events[] = [
        'id'            => $row['id'],
        'title'         => $row['title'],
        'start'         => $row['event_start'],
        'end'           => $row['event_end'] ?: null,
        'allDay'        => (bool)(int)$row['is_all_day'],
        'url'           => url('/announcements/view.php?id=' . $row['id']),
        'color'         => $categoryColors[$row['category']] ?? '#636E72',
        'extendedProps' => [
            'category'     => $row['category'],
            'facebook_url' => $row['facebook_url'],
        ],
    ];
}

echo json_encode($events, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
