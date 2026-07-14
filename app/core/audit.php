<?php
declare(strict_types=1);

function audit_log(
    string $action,
    ?string $entityType = null,
    ?int $entityId = null,
    ?array $meta = null,
    ?int $targetUserId = null
): void {
    $ip        = $_SERVER['REMOTE_ADDR'] ?? null;
    $userAgent = isset($_SERVER['HTTP_USER_AGENT'])
        ? mb_substr($_SERVER['HTTP_USER_AGENT'], 0, 255)
        : null;

    db_exec(
        'INSERT INTO audit_logs
            (actor_id, target_user_id, action, entity_type, entity_id,
             description, metadata_json, ip_address, user_agent, created_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())',
        [
            user_id(),
            $targetUserId,
            $action,
            $entityType,
            $entityId,
            $entityType !== null ? $action . ' on ' . $entityType . ($entityId ? ':' . $entityId : '') : $action,
            $meta !== null ? json_encode($meta, JSON_UNESCAPED_UNICODE) : null,
            $ip,
            $userAgent,
        ]
    );
}
