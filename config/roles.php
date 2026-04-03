<?php
if (!function_exists('getRoleLevel')) {
    function getRoleLevel($role)
    {
        return match ($role) {
            'user' => 1,
            'admin' => 2,
            'super' => 3,
            'master' => 4,
            default => 0
        };
    }
}

if (!function_exists('canAccessProjectByCreatorRole')) {
    function canAccessProjectByCreatorRole(int $currentUserId, string $currentRole, int $projectCreatorId, string $projectCreatorRole): bool
    {
        $currentLevel = getRoleLevel($currentRole);
        $creatorLevel = getRoleLevel($projectCreatorRole);

        return $currentLevel > $creatorLevel || $projectCreatorId === $currentUserId;
    }
}
