<?php
if (!function_exists('iv_render_auto_top_path')) {
    function iv_render_auto_top_path(string $homeLink = 'index.php'): void
    {
        $scriptFile = $_SERVER['SCRIPT_FILENAME'] ?? '';
        if (is_file($scriptFile)) {
            $contents = @file_get_contents($scriptFile);
            if ($contents !== false && strpos($contents, 'class="nxl-header"') !== false) {
                return;
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
                .iv-auto-top-path .header-wrapper {
                    padding: 18px 28px 16px !important;
                    display: flex !important;
                    flex-direction: column !important;
                    align-items: flex-start !important;
                    justify-content: center !important;
                    gap: 8px !important;
                }
                .iv-auto-top-path .iv-top-title {
                    margin: 0 !important;
                    line-height: 1.2 !important;
                    font-size: 22px !important;
                    font-weight: 600 !important;
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
            </style>';
        }

        $safeSection = htmlspecialchars($section, ENT_QUOTES, 'UTF-8');
        $safePage = htmlspecialchars($page, ENT_QUOTES, 'UTF-8');

        echo '<header class="nxl-header iv-auto-top-path">
                <div class="header-wrapper">
                    <h5 class="iv-top-title">' . $safeSection . '</h5>
                    <p class="iv-top-path">' . $safeSection . '<span class="sep">></span>' . $safePage . '</p>
                </div>
            </header>';
    }
}
