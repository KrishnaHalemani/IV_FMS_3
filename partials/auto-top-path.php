<?php
require_once __DIR__ . '/../config/notifications.php';

if (!function_exists('iv_topbar_prefix')) {
    function iv_topbar_prefix(): string
    {
        $scriptPath = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
        foreach (['/admin/', '/super/', '/user/'] as $segment) {
            if (strpos($scriptPath, $segment) !== false) {
                return '../';
            }
        }

        return '';
    }
}

if (!function_exists('iv_render_auto_top_path')) {
    function iv_render_auto_top_path(string $homeLink = 'index.php'): void
    {
        $scriptFile = $_SERVER['SCRIPT_FILENAME'] ?? '';
        if (is_file($scriptFile)) {
            $contents = @file_get_contents($scriptFile);
            if ($contents !== false) {
                $skipMarkers = [
                    'class="nxl-header"',
                    "class='nxl-header'",
                ];

                foreach ($skipMarkers as $marker) {
                    if (strpos($contents, $marker) !== false) {
                        return;
                    }
                }
            }
        }

        $script = basename($_SERVER['SCRIPT_NAME'] ?? '');
        $name = preg_replace('/\.php$/i', '', $script);
        $name = str_replace(['-', '_'], ' ', $name);
        $fallbackPage = ucwords(trim($name));

        $pathMap = [
            'index.php' => ['Dashboard', 'FMS'],
            'adashboard.php' => ['Dashboard', 'FMS'],
            'sdashboard.php' => ['Dashboard', 'FMS'],
            'udashboard.php' => ['Dashboard', 'FMS'],
            'reports-project.php' => ['Reports', 'Project Reports'],
            'reports-sales.php' => ['Reports', 'Sales Reports'],
            'reports-timesheets.php' => ['Reports', 'Timesheets Reports'],
            'payment.php' => ['IMS', 'Payment'],
            'invoice-create.php' => ['IMS', 'Invoice Create'],
            'invoice-view.php' => ['IMS', 'Invoice View'],
            'customers.php' => ['Clients', 'Clients'],
            'customers-create.php' => ['Clients', 'Clients Create'],
            'students.php' => ['Clients', 'Students'],
            'students-create.php' => ['Clients', 'Students Create'],
            'students-view.php' => ['Clients', 'Students View'],
            'employee.php' => ['Employees', 'Employees'],
            'employee-create.php' => ['Employees', 'Employees Create'],
            'projects.php' => ['Projects', 'Projects'],
            'projects-create.php' => ['Projects', 'Projects Create'],
            'projects-view.php' => ['Projects', 'Projects View'],
            'login.php' => ['Authentication', 'Login'],
            'auth-register-cover.php' => ['Authentication', 'Register'],
        ];

        $section = 'Navigation';
        $page = $fallbackPage;
        if (isset($pathMap[$script])) {
            $section = $pathMap[$script][0];
            $page = $pathMap[$script][1];
        }

        static $stylePrinted = false;
        if (!$stylePrinted) {
            $stylePrinted = true;
            echo '<style>
                .iv-auto-top-path {
                    background: linear-gradient(90deg, #ffffff 0%, #f8fbff 100%) !important;
                    border-bottom: 1px solid #e2e8f0 !important;
                    box-shadow: 0 8px 25px rgba(15, 23, 42, 0.04) !important;
                }
                .iv-auto-top-path .header-wrapper {
                    min-height: 80px !important;
                    padding: 16px 28px !important;
                    display: flex !important;
                    align-items: center !important;
                    justify-content: space-between !important;
                    gap: 16px !important;
                }
                .iv-auto-top-path .iv-top-left {
                    display: flex !important;
                    flex-direction: column !important;
                    gap: 4px !important;
                    min-width: 0 !important;
                }
                .iv-auto-top-path .iv-top-title {
                    margin: 0 !important;
                    line-height: 1.2 !important;
                    font-size: 20px !important;
                    font-weight: 700 !important;
                    color: #0f172a !important;
                }
                .iv-auto-top-path .iv-top-path {
                    margin: 0 !important;
                    font-size: 13px !important;
                    line-height: 1.3 !important;
                    color: #667085 !important;
                    font-weight: 500 !important;
                }
                .iv-auto-top-path .iv-top-path .sep {
                    margin: 0 6px !important;
                    color: #98a2b3 !important;
                }
                .iv-auto-top-path .iv-top-right {
                    display: flex !important;
                    align-items: center !important;
                    flex-wrap: wrap !important;
                    justify-content: flex-end !important;
                    gap: 10px !important;
                }
                .iv-auto-top-path .iv-top-actions {
                    display: flex !important;
                    align-items: center !important;
                    flex-wrap: wrap !important;
                    gap: 10px !important;
                }
                .iv-auto-top-path .iv-top-pill {
                    display: inline-flex !important;
                    align-items: center !important;
                    gap: 6px !important;
                    padding: 8px 12px !important;
                    border-radius: 999px !important;
                    border: 1px solid #dbe4f0 !important;
                    background: #ffffff !important;
                    color: #334155 !important;
                    font-size: 12px !important;
                    font-weight: 700 !important;
                    text-transform: uppercase !important;
                    letter-spacing: 0.04em !important;
                }
                .iv-auto-top-path .iv-top-pill.iv-top-pill-primary {
                    background: #eff6ff !important;
                    border-color: #bfdbfe !important;
                    color: #1d4ed8 !important;
                }
                .iv-auto-top-path .iv-top-action {
                    display: inline-flex !important;
                    align-items: center !important;
                    gap: 8px !important;
                    padding: 9px 14px !important;
                    border-radius: 999px !important;
                    border: 1px solid #dbe4f0 !important;
                    background: #ffffff !important;
                    color: #334155 !important;
                    font-size: 12px !important;
                    font-weight: 700 !important;
                    text-transform: uppercase !important;
                    letter-spacing: 0.04em !important;
                }
                .iv-auto-top-path .iv-top-action:hover {
                    color: #1d4ed8 !important;
                    border-color: #bfdbfe !important;
                    background: #eff6ff !important;
                }
                .iv-auto-top-path .iv-top-action-danger:hover {
                    color: #b91c1c !important;
                    border-color: #fecaca !important;
                    background: #fef2f2 !important;
                }
                .iv-auto-top-path .iv-top-badge {
                    display: inline-flex !important;
                    align-items: center !important;
                    justify-content: center !important;
                    min-width: 18px !important;
                    height: 18px !important;
                    padding: 0 5px !important;
                    border-radius: 999px !important;
                    background: #1d4ed8 !important;
                    color: #fff !important;
                    font-size: 10px !important;
                    font-weight: 700 !important;
                    line-height: 1 !important;
                }
                @media (max-width: 767px) {
                    .iv-auto-top-path .header-wrapper {
                        align-items: flex-start !important;
                        flex-direction: column !important;
                    }
                    .iv-auto-top-path .iv-top-right {
                        justify-content: flex-start !important;
                    }
                }
            </style>';
        }

        $sessionRole = (string) ($_SESSION['role'] ?? '');
        $roleLabel = $sessionRole !== '' ? ucfirst($sessionRole) : 'Workspace';
        $safeRoleLabel = htmlspecialchars($roleLabel, ENT_QUOTES, 'UTF-8');
        $safeSection = htmlspecialchars($section, ENT_QUOTES, 'UTF-8');
        $safePage = htmlspecialchars($page, ENT_QUOTES, 'UTF-8');
        $safeDate = htmlspecialchars(date('d M Y'), ENT_QUOTES, 'UTF-8');
        $prefix = iv_topbar_prefix();
        $notificationsUrl = htmlspecialchars($prefix . 'notifications.php', ENT_QUOTES, 'UTF-8');
        $logoutUrl = htmlspecialchars($prefix . 'logout.php', ENT_QUOTES, 'UTF-8');
        $unreadCount = 0;
        if (isset($GLOBALS['conn']) && $GLOBALS['conn'] instanceof mysqli && !empty($_SESSION['user_id'])) {
            $unreadCount = iv_count_unread_notifications($GLOBALS['conn'], (int) $_SESSION['user_id']);
        }
        $notificationBadge = $unreadCount > 0
            ? '<span class="iv-top-badge">' . (int) $unreadCount . '</span>'
            : '';

        echo '<header class="nxl-header iv-auto-top-path">
                <div class="header-wrapper">
                    <div class="iv-top-left">
                        <h5 class="iv-top-title">Infinite Vision FMS</h5>
                        <p class="iv-top-path">' . $safeSection . '<span class="sep">></span>' . $safePage . '</p>
                    </div>
                    <div class="iv-top-right">
                        <span class="iv-top-pill">' . $safeDate . '</span>
                        <span class="iv-top-pill iv-top-pill-primary">' . $safeRoleLabel . '</span>
                        <div class="iv-top-actions">
                            <a href="' . $notificationsUrl . '" class="iv-top-action">Notifications ' . $notificationBadge . '</a>
                            <a href="' . $logoutUrl . '" class="iv-top-action iv-top-action-danger">Logout</a>
                        </div>
                    </div>
                </div>
            </header>';
    }
}
