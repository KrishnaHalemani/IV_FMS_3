<?php
require_once __DIR__ . '/../config/roles.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/notifications.php';
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    header('Location: ../login.php');
    exit;
}
if (getRoleLevel($_SESSION['role']) < getRoleLevel('admin')) {
    http_response_code(403);
    exit('Forbidden');
}

?>
<!--! ================================================================ !-->
    <!--! [Start] Navigation Manu !-->
    <!--! ================================================================ !-->
    <nav class="nxl-navigation">
        <div class="navbar-wrapper">
            <div class="m-header">
                <a href="index.php" class="b-brand">
                    <!-- ========   change your logo hear   ============ -->
                    <img src="assets/images/Logo_IV.png" alt="" class="logo logo-lg"  height="100px" width="100px">
                    <img src="assets/images/Logo_IV.png" alt="" class="logo logo-sm" />
                </a>
            </div>
            <div class="navbar-content">
                <ul class="nxl-navbar">
                    <li class="nxl-item nxl-caption">
                        <label>Navigation</label>
                    </li>
                    <li class="nxl-item nxl-hasmenu">
                        <a href="javascript:void(0);" class="nxl-link">
                            <span class="nxl-micon"><i class="feather-airplay"></i></span>
                            <span class="nxl-mtext">Dashboards</span><span class="nxl-arrow"><i class="feather-chevron-right"></i></span>
                        </a>
                        <ul class="nxl-submenu">
                            <li class="nxl-item"><a class="nxl-link" href="adashboard.php">FMS</a></li>
                            <!-- <li class="nxl-item"><a class="nxl-link" href="analytics.php">Analytics</a></li> -->
                        </ul>
                    </li>
                    <li class="nxl-item nxl-hasmenu">
                        <a href="javascript:void(0);" class="nxl-link">
                            <span class="nxl-micon"><i class="feather-cast"></i></span>
                            <span class="nxl-mtext">Reports</span><span class="nxl-arrow"><i class="feather-chevron-right"></i></span>
                        </a>
                        <ul class="nxl-submenu">
                            <!-- <li class="nxl-item"><a class="nxl-link" href="reports-sales.php">Sales Report</a></li> -->
                            <!-- <li class="nxl-item"><a class="nxl-link" href="reports-leads.php">Leads Report</a></li> -->
                            <li class="nxl-item"><a class="nxl-link" href="reports-project.php">Project Report</a></li>
                            <!-- <li class="nxl-item"><a class="nxl-link" href="reports-timesheets.php">Timesheets Report</a></li> -->
                        </ul>
                    </li>
                    <!-- <li class="nxl-item nxl-hasmenu">
                        <a href="javascript:void(0);" class="nxl-link">
                            <span class="nxl-micon"><i class="feather-send"></i></span>
                            <span class="nxl-mtext">Applications</span><span class="nxl-arrow"><i class="feather-chevron-right"></i></span>
                        </a>
                        <ul class="nxl-submenu">
                            <li class="nxl-item"><a class="nxl-link" href="apps-chat.php">Chat</a></li>
                            <li class="nxl-item"><a class="nxl-link" href="apps-email.php">Email</a></li>
                            <li class="nxl-item"><a class="nxl-link" href="apps-tasks.php">Tasks</a></li>
                            <li class="nxl-item"><a class="nxl-link" href="apps-notes.php">Notes</a></li>
                            <li class="nxl-item"><a class="nxl-link" href="apps-storage.php">Storage</a></li>
                            <li class="nxl-item"><a class="nxl-link" href="apps-calendar.php">Calendar</a></li>
                        </ul>
                    </li> -->
                    <!-- <li class="nxl-item nxl-hasmenu">
                        <a href="javascript:void(0);" class="nxl-link">
                            <span class="nxl-micon"><i class="feather-at-sign"></i></span>
                            <span class="nxl-mtext">Proposal</span><span class="nxl-arrow"><i class="feather-chevron-right"></i></span>
                        </a>
                        <ul class="nxl-submenu">
                            <li class="nxl-item"><a class="nxl-link" href="proposal.php">Proposal</a></li>
                            <li class="nxl-item"><a class="nxl-link" href="proposal-view.php">Proposal View</a></li>
                            <li class="nxl-item"><a class="nxl-link" href="proposal-edit.php">Proposal Edit</a></li>
                            <li class="nxl-item"><a class="nxl-link" href="proposal-create.php">Proposal Create</a></li>
                        </ul>
                    </li> -->
                    <li class="nxl-item nxl-hasmenu">
                        <a href="javascript:void(0);" class="nxl-link">
                            <span class="nxl-micon"><i class="feather-dollar-sign"></i></span>
                            <span class="nxl-mtext">IMS</span><span class="nxl-arrow"><i class="feather-chevron-right"></i></span>
                        </a>
                        <ul class="nxl-submenu">
                            <li class="nxl-item"><a class="nxl-link" href="payment.php">Payment(IMS)</a></li>
                            <!-- <li class="nxl-item"><a class="nxl-link" href="invoice-view.php">Invoice View(IMS)</a></li> -->
                            <li class="nxl-item"><a class="nxl-link" href="invoice-create.php">Invoice Create(IMS)</a></li>
                        </ul>
                    </li>
                    <li class="nxl-item nxl-hasmenu">
                        <a href="javascript:void(0);" class="nxl-link">
                            <span class="nxl-micon"><i class="feather-users"></i></span>
                            <span class="nxl-mtext">Clients</span><span class="nxl-arrow"><i class="feather-chevron-right"></i></span>
                        </a>
                        <ul class="nxl-submenu">
                            <li class="nxl-item"><a class="nxl-link" href="customers.php">Clients</a></li>
                            <!-- <li class="nxl-item"><a class="nxl-link" href="customers-view.php">Cients View</a></li> -->
                            <li class="nxl-item"><a class="nxl-link" href="customers-create.php">Clients Create</a></li>
                            <li class="nxl-item"><a class="nxl-link" href="students.php">Students</a></li>
                            <li class="nxl-item"><a class="nxl-link" href="students-create.php">Students Create</a></li>
                        </ul>
                    </li>
                    <li class="nxl-item nxl-hasmenu">
                        <a href="javascript:void(0);" class="nxl-link">
                            <span class="nxl-micon"><i class="feather-user-check"></i></span>
                            <span class="nxl-mtext">User Access</span><span class="nxl-arrow"><i class="feather-chevron-right"></i></span>
                        </a>
                        <ul class="nxl-submenu">
                            <li class="nxl-item"><a class="nxl-link" href="user-management.php">User Management</a></li>
                            <li class="nxl-item"><a class="nxl-link" href="../auth-register-cover.php">Create User</a></li>
                        </ul>
                    </li>
                    
                    <li class="nxl-item nxl-hasmenu">
                        <a href="javascript:void(0);" class="nxl-link">
                            <span class="nxl-micon"><i class="feather-briefcase"></i></span>
                            <span class="nxl-mtext">Projects</span><span class="nxl-arrow"><i class="feather-chevron-right"></i></span>
                        </a>
                        <ul class="nxl-submenu">
                            <li class="nxl-item"><a class="nxl-link" href="projects.php">Projects</a></li>
                            
                            <li class="nxl-item"><a class="nxl-link" href="projects-create.php">Projects Create</a></li>
                        </ul>
                    </li>
                    
                    <li class="nxl-item nxl-hasmenu">
                        <a href="javascript:void(0);" class="nxl-link">
                            <span class="nxl-micon"><i class="feather-power"></i></span>
                            <span class="nxl-mtext">Authentication</span><span class="nxl-arrow"><i class="feather-chevron-right"></i></span>
                        </a>
                        <ul class="nxl-submenu">
                            <li class="nxl-item"><a class="nxl-link" href="../auth-register-cover.php">Register</a></li>
                        </ul>
                    </li>
                    
                </ul>
                
            </div>
        </div>
    </nav>
    <!--! ================================================================ !-->
    <!--! [End]  Navigation Manu !-->
<!--! ================================================================ !-->
<?php
require_once __DIR__ . '/../partials/auto-top-path.php';
iv_render_auto_top_path('index.php');
?>
