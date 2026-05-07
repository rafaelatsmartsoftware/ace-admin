<?php
$pageTitle = 'Sparlex - Spa Website Template';
$currentPage = 'price.php';
require_once __DIR__ . '/includes/head.php';
?>

        <!-- Spinner Start -->
        <div id="spinner" class="show w-100 vh-100 bg-white position-fixed translate-middle top-50 start-50  d-flex align-items-center justify-content-center">
            <div class="spinner-grow text-primary" role="status"></div>
        </div>
        <!-- Spinner End -->


        <!-- Navbar start -->
        <div class="container-fluid sticky-top px-0">
<?php require_once __DIR__ . '/includes/topbar.php'; ?>
<?php require_once __DIR__ . '/includes/navbar.php'; ?>

<?php require_once __DIR__ . '/includes/search_modal.php'; ?>




        <!-- Header Start -->
        <div class="container-fluid bg-breadcrumb py-5">
            <div class="container text-center py-5">
                <h3 class="text-white display-3 mb-4">Our Price Plan</h1>
                <ol class="breadcrumb justify-content-center mb-0">
                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">Pages</a></li>
                    <li class="breadcrumb-item active text-white">price Page</li>
                </ol>    
            </div>
        </div>
        <!-- Header End -->

        
        <!-- Pricing Start -->
        <div class="container-fluid pricing py-5" style="background: var(--bs-primary);">
            <div class="container py-5">
                <div class="owl-carousel pricing-carousel">
                    <div class="pricing-item">
                        <div class="rounded pricing-content">
                            <div class="d-flex align-items-center justify-content-between bg-light rounded-top border-3 border-bottom border-primary p-4">
                                <h1 class="display-4 mb-0">
                                    <small class="align-top text-muted" style="font-size: 22px; line-height: 45px;">$</small>49<small class="text-muted" style="font-size: 16px; line-height: 40px;">/Mo</small>
                                </h1>
                                <h5 class="text-primary text-uppercase m-0">Basic Plan</h5>
                            </div>
                            <div class="p-4">
                                <p><i class="fa fa-check text-primary me-2"></i>Full Body Massage</p>
                                <p><i class="fa fa-check text-primary me-2"></i>Deep Tissue Massage</p>
                                <p><i class="fa fa-check text-primary me-2"></i>Hot Stone Massage</p>
                                <p><i class="fa fa-check text-primary me-2"></i>Tissue Body Polish</p>
                                <p><i class="fa fa-check text-primary me-2"></i>Foot & Nail Care</p>
                                <a href="" class="btn btn-primary btn-primary-outline-0 rounded-pill my-2 px-4">Order Now</a>
                            </div>
                        </div>
                    </div>
                    <div class="pricing-item">
                        <div class="pricing-content rounded">
                            <div class="d-flex align-items-center justify-content-between bg-light rounded-top border-3 border-bottom border-primary p-4">
                                <h1 class="display-4 mb-0">
                                    <small class="align-top text-muted" style="font-size: 22px; line-height: 45px;">$</small>99<small class="text-muted" style="font-size: 16px; line-height: 40px;">/Mo</small>
                                </h1>
                                <h5 class="text-primary text-uppercase m-0">Family Plan</h5>
                            </div>
                            <div class="p-4">
                                <p><i class="fa fa-check text-primary me-2"></i>Full Body Massage</p>
                                <p><i class="fa fa-check text-primary me-2"></i>Deep Tissue Massage</p>
                                <p><i class="fa fa-check text-primary me-2"></i>Hot Stone Massage</p>
                                <p><i class="fa fa-check text-primary me-2"></i>Tissue Body Polish</p>
                                <p><i class="fa fa-check text-primary me-2"></i>Foot & Nail Care</p>
                                <a href="" class="btn btn-primary btn-primary-outline-0 rounded-pill my-2 px-4">Order Now</a>
                            </div>
                        </div>
                    </div>
                    <div class="pricing-item">
                        <div class="pricing-content rounded">
                            <div class="d-flex align-items-center justify-content-between bg-light rounded-top border-3 border-bottom border-primary p-4">
                                <h1 class="display-4 mb-0">
                                    <small class="align-top text-muted" style="font-size: 22px; line-height: 45px;">$</small>149<small class="text-muted" style="font-size: 16px; line-height: 40px;">/Mo</small>
                                </h1>
                                <h5 class="text-primary text-uppercase m-0">VIP Plan</h5>
                            </div>
                            <div class="p-4">
                                <p><i class="fa fa-check text-primary me-2"></i>Full Body Massage</p>
                                <p><i class="fa fa-check text-primary me-2"></i>Deep Tissue Massage</p>
                                <p><i class="fa fa-check text-primary me-2"></i>Hot Stone Massage</p>
                                <p><i class="fa fa-check text-primary me-2"></i>Tissue Body Polish</p>
                                <p><i class="fa fa-check text-primary me-2"></i>Foot & Nail Care</p>
                                <a href="" class="btn btn-primary btn-primary-outline-0 rounded-pill my-2 px-4">Order Now</a>
                            </div>
                        </div>
                    </div>
                    <div class="pricing-item">
                        <div class="pricing-content rounded">
                            <div class="d-flex align-items-center justify-content-between bg-light rounded-top border-3 border-bottom border-primary p-4">
                                <h1 class="display-4 mb-0">
                                    <small class="align-top text-muted" style="font-size: 22px; line-height: 45px;">$</small>199<small class="text-muted" style="font-size: 16px; line-height: 40px;">/Mo</small>
                                </h1>
                                <h5 class="text-primary text-uppercase m-0">Most Plan</h5>
                            </div>
                            <div class="p-4">
                                <p><i class="fa fa-check text-primary me-2"></i>Full Body Massage</p>
                                <p><i class="fa fa-check text-primary me-2"></i>Deep Tissue Massage</p>
                                <p><i class="fa fa-check text-primary me-2"></i>Hot Stone Massage</p>
                                <p><i class="fa fa-check text-primary me-2"></i>Tissue Body Polish</p>
                                <p><i class="fa fa-check text-primary me-2"></i>Foot & Nail Care</p>
                                <a href="" class="btn btn-primary btn-primary-outline-0 rounded-pill my-2 px-4">Order Now</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Pricing End -->



<?php require_once __DIR__ . '/includes/footer.php'; ?>

<?php require_once __DIR__ . '/includes/scripts.php'; ?>
    </body>

</html>
