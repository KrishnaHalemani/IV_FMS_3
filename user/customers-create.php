<!DOCTYPE html>
<html lang="zxx">

<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="keyword" content="">
    <meta name="author" content="theme_ocean">
    <!--! The above 6 meta tags *must* come first in the head; any other head content must come *after* these tags !-->
    <!--! BEGIN: Apps Title-->
    <title>IV || Customers Create</title>
    <!--! END:  Apps Title-->
    <!--! BEGIN: Favicon-->
    <link rel="shortcut icon" type="image/x-icon" href="assets/images/favicon.ico">
    <!--! END: Favicon-->
    <!--! BEGIN: Bootstrap CSS-->
    <link rel="stylesheet" type="text/css" href="assets/css/bootstrap.min.css">
    <!--! END: Bootstrap CSS-->
    <!--! BEGIN: Vendors CSS-->
    <link rel="stylesheet" type="text/css" href="assets/vendors/css/vendors.min.css">
    <link rel="stylesheet" type="text/css" href="assets/vendors/css/select2.min.css">
    <link rel="stylesheet" type="text/css" href="assets/vendors/css/select2-theme.min.css">
    <link rel="stylesheet" type="text/css" href="assets/vendors/css/datepicker.min.css">
    <!--! END: Vendors CSS-->
    <!--! BEGIN: Custom CSS-->
    <link rel="stylesheet" type="text/css" href="assets/css/theme.min.css">
    <!--! END: Custom CSS-->
    <!--! HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries !-->
    <!--! WARNING: Respond.js doesn"t work if you view the page via file: !-->
    <!--[if lt IE 9]>
			<script src="https:oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
			<script src="https:oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
		<![endif]-->
</head>

<body>
   
    <?php
    include 'sidebar.php';
    ?>
    
    <main class="nxl-container">
        <div class="nxl-content">
            <!-- [ page-header ] start -->
            <!-- <div class="page-header">
                <div class="page-header-left d-flex align-items-center">
                    <div class="page-header-title">
                        <h5 class="m-b-10">Clients</h5>
                    </div>
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                        <li class="breadcrumb-item">Create</li>
                    </ul>
                </div>
                <div class="page-header-right ms-auto">
                    <div class="page-header-right-items">
                        <div class="d-flex d-md-none">
                            <a href="javascript:void(0)" class="page-header-right-close-toggle">
                                <i class="feather-arrow-left me-2"></i>
                                <span>Back</span>
                            </a>
                        </div>
                        <div class="d-flex align-items-center gap-2 page-header-right-items-wrapper">
                            
                            <a href="javascript:void(0);" class="btn btn-primary successAlertMessage">
                                <i class="feather-user-plus me-2"></i>
                                <span>Create Client</span>
                            </a>
                        </div>
                    </div>
                    <div class="d-md-none d-flex align-items-center">
                        <a href="javascript:void(0)" class="page-header-right-open-toggle">
                            <i class="feather-align-right fs-20"></i>
                        </a>
                    </div>
                </div>
            </div> -->
            <!-- [ page-header ] end -->
            <!-- [ Main Content ] start -->
            <div class="nxl-content">
    <!-- Page Header -->
    <div class="page-header">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Clients</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item">Create</li>
            </ul>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="row">
            <div class="col-lg-12">
                <div class="card border-top-0">

                    <form id="clientForm" method="POST" action="save-customer.php">

    <!-- Tabs -->
    <div class="card-header p-0">
        <ul class="nav nav-tabs w-100 text-center">
            <li class="nav-item flex-fill">
                <a class="nav-link active">Profile</a>
            </li>
        </ul>
    </div>

    <div class="tab-content">
        <div class="tab-pane fade show active">

            <!-- Personal Info -->
            <div class="card-body">
                <h5 class="fw-bold mb-4">Personal Information</h5>

                <div class="row mb-3">
                    <div class="col-lg-4">
                        <label>Name</label>
                    </div>
                    <div class="col-lg-8">
                        <!-- 🔴 FIXED HERE -->
                        <input
                            type="text"
                            name="customer_name"
                            class="form-control"
                            placeholder="Customer Name"
                            required
                        >
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
                    <div class="col-lg-4"><label>Company</label></div>
                    <div class="col-lg-8">
                        <input type="text" name="company_name" class="form-control" placeholder="Company">
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

            <!-- Additional Info -->
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
                        <!-- 🔴 FIXED ENUM -->
                        <select name="status" class="form-control">
                            <option value="Active" selected>Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Submit -->
            <div class="card-body text-end">
                <button type="submit" class="btn btn-primary">
                    <i class="feather-user-plus me-2"></i>
                    Create Customer
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

            <!-- [ Main Content ] end -->
        </div>
        <!-- [ Footer ] start -->
        <?php
        include 'footer.php';
        ?>
    
    <!--!