<?php require_once __DIR__ . '/frontend_data.php'; ?>
        <!-- Footer Start -->
        <div class="container-fluid footer py-5">
            <div class="container py-5">
                <div class="row g-5">
                    <div class="col-md-6 col-lg-6 col-xl-3">
                        <div class="footer-item">
                            <h4 class="mb-4 text-white">Newsletter</h4>
                            <p class="text-white"><?php echo frontend_company_field('description'); ?></p>
                            <div class="position-relative mx-auto rounded-pill">
                                <input class="form-control rounded-pill border-0 w-100 py-3 ps-4 pe-5" type="text" placeholder="Enter your email">
                                <button type="button" class="btn btn-primary btn-primary-outline-0 rounded-pill position-absolute top-0 end-0 py-2 mt-2 me-2">SignUp</button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-6 col-xl-3">
                        <div class="footer-item d-flex flex-column">
                            <h4 class="mb-4 text-white">Our Services</h4>
                            <a href=""><i class="fas fa-angle-right me-2"></i> Facials</a>
                            <a href=""><i class="fas fa-angle-right me-2"></i> Waxing</a>
                            <a href=""><i class="fas fa-angle-right me-2"></i> Message</a>
                            <a href=""><i class="fas fa-angle-right me-2"></i> Minarel baths</a>
                            <a href=""><i class="fas fa-angle-right me-2"></i> Body treatments</a>
                            <a href=""><i class="fas fa-angle-right me-2"></i> Aroma Therapy</a>
                            <a href=""><i class="fas fa-angle-right me-2"></i> Stone Spa</a>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-6 col-xl-3">
                        <div class="footer-item d-flex flex-column">
                            <h4 class="mb-4 text-white">Schedule</h4>
                            <p class="text-muted mb-0"><span class="text-white"><?php echo frontend_company_field('opening_note'); ?></span></p>
                            <p class="text-muted mb-0">Saturday: <span class="text-white"> 09:00 am – 08:00 pm</span></p>
                            <p class="text-muted mb-0">Sunday: <span class="text-white"> 09:00 am – 05:00 pm</span></p>
                            <h4 class="my-4 text-white">Address</h4>
                            <p class="mb-0"><i class="fas fa-map-marker-alt text-secondary me-2"></i> <?php echo frontend_company_field('main_address'); ?></p>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-6 col-xl-3">
                        <div class="footer-item d-flex flex-column">
                            <h4 class="mb-4 text-white">Follow Us</h4>
                            <a href="<?php echo frontend_company_url('facebook_url'); ?>"><i class="fas fa-angle-right me-2"></i> Faceboock</a>
                            <a href="<?php echo frontend_company_url('instagram_url'); ?>"><i class="fas fa-angle-right me-2"></i> Instagram</a>
                            <a href=""><i class="fas fa-angle-right me-2"></i> Twitter</a>
                            <h4 class="my-4 text-white">Contact Us</h4>
                            <p class="mb-0"><i class="fas fa-envelope text-secondary me-2"></i> <?php echo frontend_company_field('email'); ?></p>
                            <p class="mb-0"><i class="fas fa-phone text-secondary me-2"></i> <?php echo frontend_company_field('phone'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Footer End -->



        <!-- Copyright Start -->
        <div class="container-fluid copyright py-4">
            <div class="container">
                <div class="row g-4 align-items-center">
                    <div class="col-md-4 text-center text-md-start mb-md-0">
                        <span class="text-light"><a href="<?php echo frontend_company_url('website'); ?>"><i class="fas fa-copyright text-light me-2"></i><?php echo frontend_company_field('business_name'); ?></a>, All right reserved.</span>
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex justify-content-center">
                            <a href="<?php echo frontend_company_url('facebook_url'); ?>" class="btn btn-light btn-light-outline-0 btn-sm-square rounded-circle me-2"><i class="fab fa-facebook-f"></i></a>
                            <a href="" class="btn btn-light btn-light-outline-0 btn-sm-square rounded-circle me-2"><i class="fab fa-twitter"></i></a>
                            <a href="<?php echo frontend_company_url('instagram_url'); ?>" class="btn btn-light btn-light-outline-0 btn-sm-square rounded-circle me-2"><i class="fab fa-instagram"></i></a>
                            <a href="" class="btn btn-light btn-light-outline-0 btn-sm-square rounded-circle me-0"><i class="fab fa-linkedin-in"></i></a>
                        </div>
                    </div>
                    <div class="col-md-4 text-center text-md-end text-white">
                        <!--/*** This template is free as long as you keep the below author’s credit link/attribution link/backlink. ***/-->
                        <!--/*** If you'd like to use the template without the below author’s credit link/attribution link/backlink, ***/-->
                        <!--/*** you can purchase the Credit Removal License from "https://htmlcodex.com/credit-removal". ***/-->
                        Designed By <a class="border-bottom" href="https://htmlcodex.com">HTML Codex</a>
                    </div>
                </div>
            </div>
        </div>
        <!-- Copyright End -->



        <!-- Back to Top -->
        <a href="#" class="btn btn-primary btn-primary-outline-0 btn-md-square rounded-circle back-to-top"><i class="fa fa-arrow-up"></i></a>
