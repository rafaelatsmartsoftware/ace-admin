<?php
$currentPage = $currentPage ?? basename($_SERVER['PHP_SELF'] ?? 'index.php');
$pagesDropdown = ['team.php', 'testimonial.php', 'gallery.php', 'appointment.php', '404.php'];
$isPagesActive = in_array($currentPage, $pagesDropdown, true);
function frontend_nav_active(string $page, string $currentPage): string
{
    return $currentPage === $page ? ' active' : '';
}
?>
            <div class="container-fluid bg-light">
                <div class="container px-0">
                    <nav class="navbar navbar-light navbar-expand-xl">
                        <a href="index.php" class="navbar-brand">
                            <h1 class="text-primary display-4">Sparlex</h1>
                        </a>
                        <button class="navbar-toggler py-2 px-3" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
                            <span class="fa fa-bars text-primary"></span>
                        </button>
                        <div class="collapse navbar-collapse bg-light py-3" id="navbarCollapse">
                            <div class="navbar-nav mx-auto border-top">
                                <a href="index.php" class="nav-item nav-link<?php echo frontend_nav_active('index.php', $currentPage); ?>">Home</a>
                                <a href="about.php" class="nav-item nav-link<?php echo frontend_nav_active('about.php', $currentPage); ?>">About</a>
                                <a href="services.php" class="nav-item nav-link<?php echo frontend_nav_active('services.php', $currentPage); ?>">Services</a>
                                <a href="price.php" class="nav-item nav-link<?php echo frontend_nav_active('price.php', $currentPage); ?>">Price</a>
                                <div class="nav-item dropdown">
                                    <a href="#" class="nav-link dropdown-toggle<?php echo $isPagesActive ? ' active' : ''; ?>" data-bs-toggle="dropdown">Pages</a>
                                    <div class="dropdown-menu m-0 bg-secondary rounded-0">
                                        <a href="team.php" class="dropdown-item<?php echo frontend_nav_active('team.php', $currentPage); ?>">Team</a>
                                        <a href="testimonial.php" class="dropdown-item<?php echo frontend_nav_active('testimonial.php', $currentPage); ?>">Testimonial</a>
                                        <a href="gallery.php" class="dropdown-item<?php echo frontend_nav_active('gallery.php', $currentPage); ?>">Gallery</a>
                                        <a href="appointment.php" class="dropdown-item<?php echo frontend_nav_active('appointment.php', $currentPage); ?>">Appointment</a>
                                        <a href="404.php" class="dropdown-item<?php echo frontend_nav_active('404.php', $currentPage); ?>">404 page</a>
                                    </div>
                                </div>
                                <a href="contact.php" class="nav-item nav-link<?php echo frontend_nav_active('contact.php', $currentPage); ?>">Contact Us</a>
                            </div>
                            <div class="d-flex align-items-center flex-nowrap pt-xl-0">
                                <button class="btn-search btn btn-primary btn-primary-outline-0 rounded-circle btn-lg-square" data-bs-toggle="modal" data-bs-target="#searchModal"><i class="fas fa-search"></i></button>
                                <a href="appointment.php" class="btn btn-primary btn-primary-outline-0 rounded-pill py-3 px-4 ms-4">Book Appointment</a>
                            </div>
                        </div>
                    </nav>
                </div>
            </div>
        </div>
        <!-- Navbar End -->
