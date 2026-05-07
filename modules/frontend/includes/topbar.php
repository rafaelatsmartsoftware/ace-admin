<?php require_once __DIR__ . '/frontend_data.php'; ?>
            <div class="container-fluid topbar d-none d-lg-block">
                <div class="container px-0">
                    <div class="row align-items-center">
                        <div class="col-lg-8">
                            <div class="d-flex flex-wrap">
                                <a href="#" class="me-4 text-light"><i class="fas fa-map-marker-alt text-primary me-2"></i><?php echo frontend_company_field('main_address'); ?></a>
                                <a href="tel:<?php echo frontend_company_field('phone'); ?>" class="me-4 text-light"><i class="fas fa-phone-alt text-primary me-2"></i><?php echo frontend_company_field('phone'); ?></a>
                                <a href="mailto:<?php echo frontend_company_field('email'); ?>" class="text-light"><i class="fas fa-envelope text-primary me-2"></i><?php echo frontend_company_field('email'); ?></a>
                            </div>

                        </div>
                        <div class="col-lg-4">
                            <div class="d-flex align-items-center justify-content-end">
                                <a href="<?php echo frontend_company_url('facebook_url'); ?>" class="me-3 btn-square border rounded-circle nav-fill"><i class="fab fa-facebook-f"></i></a>
                                <a href="#" class="me-3 btn-square border rounded-circle nav-fill"><i class="fab fa-twitter"></i></a>
                                <a href="<?php echo frontend_company_url('instagram_url'); ?>" class="me-3 btn-square border rounded-circle nav-fill"><i class="fab fa-instagram"></i></a>
                                <a href="#" class="btn-square border rounded-circle nav-fill"><i class="fab fa-linkedin-in"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
