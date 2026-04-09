<?php
require_once __DIR__ . '/current_user.php';

if (!function_exists('iv_current_business_role')) {
    function iv_current_business_role(): string
    {
        return (string) ($_SESSION['role'] ?? '');
    }
}

if (!function_exists('iv_is_master_business_role')) {
    function iv_is_master_business_role(?string $role = null): bool
    {
        $resolvedRole = $role ?? iv_current_business_role();
        return $resolvedRole === 'master';
    }
}

if (!function_exists('iv_current_business_franchisee_id')) {
    function iv_current_business_franchisee_id(?string $role = null): ?int
    {
        return iv_is_master_business_role($role) ? null : iv_current_session_franchisee_id();
    }
}

if (!function_exists('iv_business_scope_condition')) {
    function iv_business_scope_condition(string $column = 'franchisee_id', ?string $role = null): string
    {
        $resolvedRole = $role ?? iv_current_business_role();
        if (iv_is_master_business_role($resolvedRole)) {
            return '1=1';
        }

        $franchiseeId = iv_current_business_franchisee_id($resolvedRole);
        if ($franchiseeId === null) {
            return '1=0';
        }

        return $column . ' = ' . (int) $franchiseeId;
    }
}
?>
