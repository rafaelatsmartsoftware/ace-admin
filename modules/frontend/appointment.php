<?php
$pageTitle = 'Sparlex - Spa Website Template';
$currentPage = 'appointment.php';
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
                <h3 class="text-white display-3 mb-4">Appointment</h1>
                <ol class="breadcrumb justify-content-center mb-0">
                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">Pages</a></li>
                    <li class="breadcrumb-item active text-white">Appointment</li>
                </ol>    
            </div>
        </div>
        <!-- Header End -->

        
        <!-- Appointment Start -->
        <div class="container-fluid appointment py-5" style="background: var(--bs-primary);">
            <div class="container py-5">
                <div class="row g-5 align-items-center">
                    <div class="col-lg-6">
                        <div class="appointment-form p-5">
                            <p class="fs-4 text-uppercase text-primary">Get In Touch</p>
                            <h1 class="display-4 mb-4 text-white">Get Appointment</h1>
                            <form>
                                <div class="row gy-3 gx-4">
                                    <div class="col-lg-6">
                                        <input type="text" class="form-control py-3 border-white bg-transparent text-white" placeholder="First Name">
                                    </div>
                                    <div class="col-lg-6">
                                        <input type="email" class="form-control py-3 border-white bg-transparent text-white" placeholder="Email">
                                    </div>
                                    <div class="col-lg-6">
                                        <select class="form-select py-3 border-white bg-transparent" aria-label="Default select example">
                                            <option selected>Open this select menu</option>
                                            <option value="1">One</option>
                                            <option value="2">Two</option>
                                            <option value="3">Three</option>
                                        </select>
                                    </div>
                                    <div class="col-lg-6">
                                        <input type="date" class="form-control py-3 border-white bg-transparent">
                                    </div>
                                    <div class="col-lg-12">
                                        <textarea class="form-control border-white bg-transparent text-white" name="text" id="area-text" cols="30" rows="5" placeholder="Write Comments"></textarea>
                                    </div>
                                    <div class="col-lg-12">
                                        <button type="button" class="btn btn-primary btn-primary-outline-0 w-100 py-3 px-5">SUBMIT NOW</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="appointment-time p-5">
                            <h1 class="display-5 mb-4">Opening Hours</h1>
                            <div class="d-flex justify-content-between fs-5 text-white">
                                <p>Saturday:</p>
                                <p>09:00 am – 10:00 pm</p>
                            </div>
                            <div class="d-flex justify-content-between fs-5 text-white">
                                <p>Sunday:</p>
                                <p>09:00 am – 10:00 pm</p>
                            </div>
                            <div class="d-flex justify-content-between fs-5 text-white">
                                <p>Monday:</p>
                                <p>09:00 am – 10:00 pm</p>
                            </div>
                            <div class="d-flex justify-content-between fs-5 text-white">
                                <p>Tuesday:</p>
                                <p>09:00 am – 10:00 pm</p>
                            </div>
                            <div class="d-flex justify-content-between fs-5 text-white">
                                <p>Wednes:</p>
                                <p>09:00 am – 08:00 pm</p>
                            </div>
                            <div class="d-flex justify-content-between fs-5 text-white mb-4">
                                <p>Thursday:</p>
                                <p>09:00 am – 05:00 pm</p>
                            </div>
                            <div class="d-flex justify-content-between fs-5 text-white mb-4">
                                <p>Friday:</p>
                                <p>CLOSED</p>
                            </div>
                            <p class="text-dark">Check out seasonal discounts for best offers.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Appointment End -->



<?php require_once __DIR__ . '/includes/footer.php'; ?>

<?php require_once __DIR__ . '/includes/scripts.php'; ?>
    </body>

</html>
