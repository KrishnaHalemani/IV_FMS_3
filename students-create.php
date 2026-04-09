<?php
require_once __DIR__ . '/config/access_control.php';

iv_require_role_session(['master', 'super', 'admin'], 'login.php');
?>
<!DOCTYPE html>
<html lang="zxx">
<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>IV || Students Create</title>
    <link rel="shortcut icon" type="image/x-icon" href="assets/images/favicon.ico">
    <link rel="stylesheet" type="text/css" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="assets/vendors/css/vendors.min.css">
    <link rel="stylesheet" type="text/css" href="assets/vendors/css/select2.min.css">
    <link rel="stylesheet" type="text/css" href="assets/vendors/css/select2-theme.min.css">
    <link rel="stylesheet" type="text/css" href="assets/vendors/css/datepicker.min.css">
    <link rel="stylesheet" type="text/css" href="assets/css/theme.min.css">
</head>
<body>
<?php include 'sidebar.php'; ?>
<main class="nxl-container">
    <div class="nxl-content">
        <div class="page-header">
            <div class="page-header-left d-flex align-items-center">
                <div class="page-header-title">
                    <h5 class="m-b-10">Students</h5>
                </div>
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                    <li class="breadcrumb-item">Create</li>
                </ul>
            </div>
        </div>

        <div class="main-content">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card border-top-0">
                        <form method="POST" action="save-student.php">
                            <div class="card-header p-0">
                                <ul class="nav nav-tabs w-100 text-center">
                                    <li class="nav-item flex-fill">
                                        <a class="nav-link active">Profile</a>
                                    </li>
                                </ul>
                            </div>

                            <div class="tab-content">
                                <div class="tab-pane fade show active">
                                    <div class="card-body">
                                        <h5 class="fw-bold mb-4">Personal Information</h5>

                                        <div class="row mb-3">
                                            <div class="col-lg-4"><label>Name</label></div>
                                            <div class="col-lg-8">
                                                <input type="text" name="student_name" class="form-control" placeholder="Student Name" required>
                                            </div>
                                        </div>

                                        <div class="row mb-3">
                                            <div class="col-lg-4"><label>Email</label></div>
                                            <div class="col-lg-8">
                                                <input type="email" name="email" class="form-control" placeholder="Email">
                                            </div>
                                        </div>

                                        <div class="row mb-3">
                                            <div class="col-lg-4"><label>Phone</label></div>
                                            <div class="col-lg-8">
                                                <input type="text" name="phone" class="form-control" placeholder="Phone">
                                            </div>
                                        </div>

                                        <div class="row mb-3">
                                            <div class="col-lg-4"><label>Course</label></div>
                                            <div class="col-lg-8">
                                                <input type="text" name="course" class="form-control" placeholder="Course">
                                            </div>
                                        </div>

                                        <div class="row mb-3">
                                            <div class="col-lg-4"><label>Address</label></div>
                                            <div class="col-lg-8">
                                                <textarea name="address" class="form-control" rows="3"></textarea>
                                            </div>
                                        </div>

                                        <div class="row mb-3">
                                            <div class="col-lg-4"><label>About</label></div>
                                            <div class="col-lg-8">
                                                <textarea name="about" class="form-control" rows="4"></textarea>
                                            </div>
                                        </div>
                                    </div>

                                    <hr>

                                    <div class="card-body">
                                        <h5 class="fw-bold mb-4">Additional Information</h5>
                                        <div class="row mb-3">
                                            <div class="col-lg-4"><label>Date of Birth</label></div>
                                            <div class="col-lg-8">
                                                <input type="date" name="dob" class="form-control">
                                            </div>
                                        </div>

                                        <div class="row mb-4">
                                            <div class="col-lg-4"><label>Status</label></div>
                                            <div class="col-lg-8">
                                                <select name="status" class="form-control">
                                                    <option value="Active" selected>Active</option>
                                                    <option value="Inactive">Inactive</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="card-body text-end">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="feather-user-plus me-2"></i>
                                            Create Student
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include 'footer.php'; ?>
</main>
</body>
</html>
