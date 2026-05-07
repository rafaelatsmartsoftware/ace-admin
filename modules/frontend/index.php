<?php
$pageTitle = 'Sparlex - Spa Website Template';
$currentPage = 'index.php';
require_once __DIR__ . '/includes/head.php';

$frontendBranches = get_frontend_branches();
$frontendAllServiceCategories = get_frontend_service_categories();
$frontendHomepageServiceCategories = array_slice($frontendAllServiceCategories, 0, 8);
$frontendServicesByCategory = get_frontend_services_grouped_by_category();
$frontendServiceImages = [
	'img/services-1.jpg',
	'img/services-2.jpg',
	'img/services-3.jpg',
	'img/services-4.jpg',
	'img/services-5.jpg',
	'img/services-6.jpg',
	'img/services-3.jpg',
	'img/services-1.jpg',
];
$frontendServiceFallbackText = "Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy";
$homepageAppointmentErrors = [];
$homepageAppointmentSuccess = isset($_GET['appointment_success']) && $_GET['appointment_success'] === '1';
$homepageAppointmentAnchor = 'homepage-appointment-form';
$homepageGuestName = trim((string) ($_POST['guest_name'] ?? ''));
$homepageGuestPhone = trim((string) ($_POST['guest_phone'] ?? ''));
$homepageGuestEmail = trim((string) ($_POST['guest_email'] ?? ''));
$homepageSelectedBranchId = isset($_POST['outlet_id']) ? (int) $_POST['outlet_id'] : 0;
$homepageSelectedCategoryId = isset($_POST['service_category_id']) ? (int) $_POST['service_category_id'] : 0;
$homepageSelectedServiceId = isset($_POST['service_id']) ? (int) $_POST['service_id'] : 0;
$homepageAppointmentDate = trim((string) ($_POST['appointment_date'] ?? ''));
$homepageAppointmentTime = trim((string) ($_POST['appointment_time'] ?? ''));
$homepageNotes = trim((string) ($_POST['notes'] ?? ''));
$homepageServicesJson = [];
$homepagePdo = ace_admin_db();

foreach ($frontendServicesByCategory as $categoryId => $services) {
	$categoryKey = (string) ((int) $categoryId);
	$homepageServicesJson[$categoryKey] = [];

	foreach ($services as $service) {
		$homepageServicesJson[$categoryKey][] = [
			'id' => (int) ($service['id'] ?? 0),
			'service_name' => (string) ($service['service_name'] ?? ''),
			'price' => (float) ($service['price'] ?? 0),
		];
	}
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if ($homepageGuestName === '') {
		$homepageAppointmentErrors[] = 'Full Name is required.';
	}

	if ($homepageGuestPhone === '') {
		$homepageAppointmentErrors[] = 'Phone Number is required.';
	}

	if ($homepageSelectedBranchId <= 0) {
		$homepageAppointmentErrors[] = 'Please select a branch.';
	}

	if ($homepageSelectedCategoryId <= 0) {
		$homepageAppointmentErrors[] = 'Please select a service category.';
	}

	if ($homepageSelectedServiceId <= 0) {
		$homepageAppointmentErrors[] = 'Please select a service.';
	}

	if ($homepageAppointmentDate === '') {
		$homepageAppointmentErrors[] = 'Appointment Date is required.';
	}

	if ($homepageAppointmentTime === '') {
		$homepageAppointmentErrors[] = 'Appointment Time is required.';
	}

	if ($homepageGuestEmail !== '' && !filter_var($homepageGuestEmail, FILTER_VALIDATE_EMAIL)) {
		$homepageAppointmentErrors[] = 'Please enter a valid email address.';
	}

	$today = new DateTimeImmutable('today');
	$dateObject = DateTimeImmutable::createFromFormat('Y-m-d', $homepageAppointmentDate);
	$dateErrors = DateTimeImmutable::getLastErrors();
	$dateWarningCount = is_array($dateErrors) ? (int) ($dateErrors['warning_count'] ?? 0) : 0;
	$dateErrorCount = is_array($dateErrors) ? (int) ($dateErrors['error_count'] ?? 0) : 0;

	if ($homepageAppointmentDate !== '' && (!$dateObject || $dateWarningCount > 0 || $dateErrorCount > 0)) {
		$homepageAppointmentErrors[] = 'Please select a valid appointment date.';
	} elseif ($dateObject instanceof DateTimeImmutable && $dateObject < $today) {
		$homepageAppointmentErrors[] = 'Appointment date cannot be in the past.';
	}

	$timeObject = DateTimeImmutable::createFromFormat('H:i', $homepageAppointmentTime);
	$timeErrors = DateTimeImmutable::getLastErrors();
	$timeWarningCount = is_array($timeErrors) ? (int) ($timeErrors['warning_count'] ?? 0) : 0;
	$timeErrorCount = is_array($timeErrors) ? (int) ($timeErrors['error_count'] ?? 0) : 0;

	if ($homepageAppointmentTime !== '' && (!$timeObject || $timeWarningCount > 0 || $timeErrorCount > 0)) {
		$homepageAppointmentErrors[] = 'Please select a valid appointment time.';
	}

	if (!$homepagePdo instanceof PDO) {
		$homepageAppointmentErrors[] = 'Booking is temporarily unavailable. Please try again later.';
	}

	$validatedHomepageService = null;

	if (empty($homepageAppointmentErrors) && $homepagePdo instanceof PDO) {
		try {
			$branchStatement = $homepagePdo->prepare('SELECT id FROM branches WHERE id = :id LIMIT 1');
			$branchStatement->execute(['id' => $homepageSelectedBranchId]);

			if (!$branchStatement->fetch()) {
				$homepageAppointmentErrors[] = 'Selected branch is not available.';
			}

			$categoryStatement = $homepagePdo->prepare('SELECT id FROM service_categories WHERE id = :id LIMIT 1');
			$categoryStatement->execute(['id' => $homepageSelectedCategoryId]);

			if (!$categoryStatement->fetch()) {
				$homepageAppointmentErrors[] = 'Selected service category is not available.';
			}

			$serviceStatement = $homepagePdo->prepare(
				'SELECT id, service_category_id
				FROM services
				WHERE id = :id
				LIMIT 1'
			);
			$serviceStatement->execute(['id' => $homepageSelectedServiceId]);
			$validatedHomepageService = $serviceStatement->fetch();

			if (!$validatedHomepageService) {
				$homepageAppointmentErrors[] = 'Selected service is not available.';
			} elseif ((int) ($validatedHomepageService['service_category_id'] ?? 0) !== $homepageSelectedCategoryId) {
				$homepageAppointmentErrors[] = 'Selected service does not belong to the chosen category.';
			}
		} catch (PDOException $exception) {
			error_log('Homepage appointment validation failed: ' . $exception->getMessage());
			$homepageAppointmentErrors[] = 'We could not validate your booking right now. Please try again later.';
		}
	}

	if (empty($homepageAppointmentErrors) && $homepagePdo instanceof PDO && is_array($validatedHomepageService)) {
		try {
			$insertStatement = $homepagePdo->prepare(
				'INSERT INTO bookings
				(booking_type, customer_id, guest_name, guest_phone, guest_email, outlet_id, service_id, employee_id, appointment_date, appointment_time, booking_status, payment_method, notes)
				VALUES
				(:booking_type, :customer_id, :guest_name, :guest_phone, :guest_email, :outlet_id, :service_id, :employee_id, :appointment_date, :appointment_time, :booking_status, :payment_method, :notes)'
			);
			$insertStatement->execute([
				'booking_type' => 'guest',
				'customer_id' => null,
				'guest_name' => $homepageGuestName,
				'guest_phone' => $homepageGuestPhone,
				'guest_email' => $homepageGuestEmail !== '' ? $homepageGuestEmail : null,
				'outlet_id' => $homepageSelectedBranchId,
				'service_id' => $homepageSelectedServiceId,
				'employee_id' => null,
				'appointment_date' => $homepageAppointmentDate,
				'appointment_time' => $homepageAppointmentTime,
				'booking_status' => 'pending',
				'payment_method' => 'pay_at_salon',
				'notes' => $homepageNotes !== '' ? $homepageNotes : null,
			]);

			header('Location: index.php?appointment_success=1#' . $homepageAppointmentAnchor);
			exit;
		} catch (PDOException $exception) {
			error_log('Homepage appointment insert failed: ' . $exception->getMessage());
			$homepageAppointmentErrors[] = 'We could not submit your appointment right now. Please try again later.';
		}
	}
}
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




        <!-- Carousel Start -->
        <div class="container-fluid carousel-header px-0">
            <div id="carouselId" class="carousel slide" data-bs-ride="carousel">
                <ol class="carousel-indicators">
                    <li data-bs-target="#carouselId" data-bs-slide-to="0" class="active"></li>
                    <li data-bs-target="#carouselId" data-bs-slide-to="1"></li>
                    <li data-bs-target="#carouselId" data-bs-slide-to="2"></li>
                </ol>
                <div class="carousel-inner" role="listbox">
                    <div class="carousel-item active">
                        <img src="img/carousel-3.jpg" class="img-fluid" alt="Image">
                        <div class="carousel-caption">
                            <div class="p-3" style="max-width: 900px;">
                                <h4 class="text-primary text-uppercase mb-3">Spa & Beauty Center</h4>
                                <h1 class="display-1 text-capitalize text-dark mb-3">Massage Treatment</h1>
                                <p class="mx-md-5 fs-4 px-4 mb-5 text-dark">Lorem rebum magna dolore amet lorem eirmod magna erat diam stet. Sadips duo stet amet amet ndiam elitr ipsum</p>
                                <div class="d-flex align-items-center justify-content-center">
                                    <a class="btn btn-light btn-light-outline-0 rounded-pill py-3 px-5 me-4" href="#">Get Start</a>
                                    <a class="btn btn-primary btn-primary-outline-0 rounded-pill py-3 px-5" href="#">Book Now</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="carousel-item">
                        <img src="img/carousel-2.jpg" class="img-fluid" alt="Image">
                        <div class="carousel-caption">
                            <div class="p-3" style="max-width: 900px;">
                                <h4 class="text-primary text-uppercase mb-3" style="letter-spacing: 3px;">Spa & Beauty Center</h4>
                                <h1 class="display-1 text-capitalize text-dark mb-3">Facial Treatment</h1>
                                <p class="mx-md-5 fs-4 px-5 mb-5 text-dark">Lorem rebum magna dolore amet lorem eirmod magna erat diam stet. Sadips duo stet amet amet ndiam elitr ipsum</p>
                                <div class="d-flex align-items-center justify-content-center">
                                    <a class="btn btn-light btn-light-outline-0 rounded-pill py-3 px-5 me-4" href="#">Get Start</a>
                                    <a class="btn btn-primary btn-primary-outline-0 rounded-pill py-3 px-5" href="#">Book Now</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="carousel-item">
                        <img src="img/carousel-1.jpg" class="img-fluid" alt="Image">
                        <div class="carousel-caption">
                            <div class="p-3" style="max-width: 900px;">
                                <h4 class="text-primary text-uppercase mb-3" style="letter-spacing: 3px;">Spa & Beauty Center</h4>
                                <h1 class="display-1 text-capitalize text-dark">Cellulite Treatment</h1>
                                <p class="mx-md-5 fs-4 px-5 mb-5 text-dark">Lorem rebum magna dolore amet lorem eirmod magna erat diam stet. Sadips duo stet amet amet ndiam elitr ipsum</p>
                                <div class="d-flex align-items-center justify-content-center">
                                    <a class="btn btn-light btn-light-outline-0 rounded-pill py-3 px-5 me-4" href="#">Get Start</a>
                                    <a class="btn btn-primary btn-primary-outline-0 rounded-pill py-3 px-5" href="#">Book Now</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <button class="carousel-control-prev" type="button" data-bs-target="#carouselId" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Previous</span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#carouselId" data-bs-slide="next">
                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Next</span>
                </button>
            </div>
        </div>
        <!-- Carousel End -->


        <!-- Services Start -->
        <div class="container-fluid services py-5">
            <div class="container py-5">
                <div class="mx-auto text-center mb-5" style="max-width: 800px;">
                    <p class="fs-4 text-uppercase text-center text-primary">Our Service</p>
                    <h1 class="display-3">Spa & Beauty Services</h1>
                </div>
                <div class="row g-4">
<?php foreach ($frontendHomepageServiceCategories as $categoryIndex => $category): ?>
<?php
	$categoryId = isset($category['id']) ? (int) ($category['id'] ?? 0) : 0;
	$isEvenTile = $categoryIndex % 2 === 0;
	$imagePath = $frontendServiceImages[$categoryIndex % count($frontendServiceImages)];
	$categoryDescription = trim((string) ($category['description'] ?? ''));
	$categoryDescription = $categoryDescription !== '' ? $categoryDescription : $frontendServiceFallbackText;
	$categoryModalId = 'homepage-service-category-modal-' . ($categoryId > 0 ? $categoryId : $categoryIndex + 1);
?>
                    <div class="col-lg-6">
                        <div class="services-item bg-light border-4 <?php echo $isEvenTile ? 'border-end' : 'border-start'; ?> border-primary rounded p-4">
                            <div class="row align-items-center">
<?php if ($isEvenTile): ?>
                                <div class="col-8">
                                    <div class="services-content text-end">
                                        <h3><?php echo frontend_escape((string) ($category['category_name'] ?? '')); ?></h3>
                                        <p><?php echo frontend_escape($categoryDescription); ?></p>
                                        <button type="button" class="btn btn-primary btn-primary-outline-0 rounded-pill py-2 px-4" data-bs-toggle="modal" data-bs-target="#<?php echo frontend_escape($categoryModalId); ?>">View Details</button>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="services-img d-flex align-items-center justify-content-center rounded">
                                        <img src="<?php echo frontend_escape($imagePath); ?>" class="img-fluid rounded" alt="">
                                    </div>
                                </div>
<?php else: ?>
                                <div class="col-4">
                                    <div class="services-img d-flex align-items-center justify-content-center rounded">
                                        <img src="<?php echo frontend_escape($imagePath); ?>" class="img-fluid rounded" alt="">
                                    </div>
                                </div>
                                <div class="col-8">
                                    <div class="services-content text-start">
                                        <h3><?php echo frontend_escape((string) ($category['category_name'] ?? '')); ?></h3>
                                        <p><?php echo frontend_escape($categoryDescription); ?></p>
                                        <button type="button" class="btn btn-primary btn-primary-outline-0 rounded-pill py-2 px-4" data-bs-toggle="modal" data-bs-target="#<?php echo frontend_escape($categoryModalId); ?>">View Details</button>
                                    </div>
                                </div>
<?php endif; ?>
                            </div>
                        </div>
                    </div>
<?php endforeach; ?>
                    <div class="col-12">
                        <div class="services-btn text-center">
                            <a href="services.php" class="btn btn-primary btn-primary-outline-0 rounded-pill py-3 px-5">View All Services</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Services End -->

<?php foreach ($frontendHomepageServiceCategories as $categoryIndex => $category): ?>
<?php
	$categoryId = isset($category['id']) ? (int) ($category['id'] ?? 0) : 0;
	$categoryModalId = 'homepage-service-category-modal-' . ($categoryId > 0 ? $categoryId : $categoryIndex + 1);
	$categoryServices = $categoryId > 0 ? ($frontendServicesByCategory[$categoryId] ?? []) : [];
?>
        <div class="modal fade" id="<?php echo frontend_escape($categoryModalId); ?>" tabindex="-1" aria-labelledby="<?php echo frontend_escape($categoryModalId); ?>-label" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-xl">
                <div class="modal-content border-0 rounded-4 overflow-hidden">
                    <div class="modal-header bg-primary text-white border-0 px-4 py-3">
                        <h5 class="modal-title text-white mb-0" id="<?php echo frontend_escape($categoryModalId); ?>-label"><?php echo frontend_escape((string) ($category['category_name'] ?? '')); ?></h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4 bg-light">
<?php if (!empty($categoryServices)): ?>
                        <div class="row g-4">
<?php foreach ($categoryServices as $service): ?>
                            <div class="col-sm-6 col-lg-4">
                                <div class="bg-white border-top border-4 border-primary rounded p-4 h-100 shadow-sm">
                                    <h5 class="mb-4"><?php echo frontend_escape((string) ($service['service_name'] ?? '')); ?></h5>
                                    <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap">
                                        <span class="fw-bold text-primary fs-5">BDT <?php echo frontend_escape(number_format((float) ($service['price'] ?? 0), 2)); ?></span>
                                        <a href="appointment.php?service_id=<?php echo frontend_escape((string) ((int) ($service['id'] ?? 0))); ?>" class="btn btn-primary btn-primary-outline-0 rounded-pill py-2 px-4">Book Now</a>
                                    </div>
                                </div>
                            </div>
<?php endforeach; ?>
                        </div>
<?php else: ?>
                        <div class="text-center py-4">
                            <p class="mb-0">No services available for this category.</p>
                        </div>
<?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
<?php endforeach; ?>

        
        <!-- About Start -->
        <div class="container-fluid about py-5">
            <div class="container py-5">
                <div class="row g-5 align-items-center">
                    <div class="col-lg-5">
                        <div class="video">
                            <img src="img/about-1.jpg" class="img-fluid rounded" alt="">
                            <div class="position-absolute rounded border-5 border-top border-start border-white" style="bottom: 0; right: 0;;">
                                <img src="img/about-2.jpg" class="img-fluid rounded" alt="">
                            </div>
                            <button type="button" class="btn btn-play" data-bs-toggle="modal" data-src="https://www.youtube.com/embed/DWRcNpR6Kdc" data-bs-target="#videoModal">
                                <span></span>
                            </button>
                        </div>
                    </div>
                    <div class="col-lg-7">
                        <p class="fs-4 text-uppercase text-primary">About Us</p>
                        <h1 class="display-4 mb-4">Your Best Spa, Beauty & Skin Care Center</h1>
                        <p class="mb-4">Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled
                        </p>
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="d-flex align-items-center">
                                    <i class="fab fa-gitkraken fa-3x text-primary"></i>
                                    <div class="ms-4">
                                        <h5 class="mb-2">Special Offers</h5>
                                        <p class="mb-0">Lorem Ipsum is simply dummy text of the printing and typesetting industry.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-gift fa-3x text-primary"></i>
                                    <div class="ms-4">
                                        <h5 class="mb-2">Special Offers</h5>
                                        <p class="mb-0">Lorem Ipsum is simply dummy text of the printing and typesetting industry.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <p class="my-4">Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s,
                        </p>
                        <p class="mb-4">Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book.
                        </p>
                        <a href="#" class="btn btn-primary btn-primary-outline-0 rounded-pill py-3 px-5">Explore More</a>
                    </div> 
                </div>
            </div>
        </div>
        <!-- Modal Video -->
        <div class="modal fade" id="videoModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content rounded-0">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">Youtube Video</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <!-- 16:9 aspect ratio -->
                        <div class="ratio ratio-16x9">
                            <iframe class="embed-responsive-item" src="" id="video" allowfullscreen allowscriptaccess="always"
                                allow="autoplay"></iframe>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- About End -->


        <!-- Appointment Start -->
        <div class="container-fluid appointment py-5">
            <div class="container py-5">
                <div class="row g-5 align-items-center">
                    <div class="col-lg-6">
                        <div class="appointment-form p-5" id="<?php echo frontend_escape($homepageAppointmentAnchor); ?>">
                            <p class="fs-4 text-uppercase text-primary">Get In Touch</p>
                            <h1 class="display-4 mb-4 text-white">Get Appointment</h1>
<?php if ($homepageAppointmentSuccess): ?>
                            <div class="alert alert-success mb-4" role="alert">
                                Your appointment request has been submitted successfully. We will contact you soon.
                            </div>
<?php endif; ?>
<?php if (!empty($homepageAppointmentErrors)): ?>
                            <div class="alert alert-danger mb-4" role="alert">
                                <ul class="mb-0 ps-3">
<?php foreach ($homepageAppointmentErrors as $homepageAppointmentError): ?>
                                    <li><?php echo frontend_escape($homepageAppointmentError); ?></li>
<?php endforeach; ?>
                                </ul>
                            </div>
<?php endif; ?>
                            <form method="post" action="">
                                <input type="hidden" name="booking_type" value="guest">
                                <input type="hidden" name="booking_status" value="pending">
                                <input type="hidden" name="payment_method" value="pay_at_salon">
                                <div class="row gy-3 gx-4">
                                    <div class="col-lg-6">
                                        <input type="text" name="guest_name" class="form-control py-3 border-white bg-transparent text-white" placeholder="Full Name" value="<?php echo frontend_escape($homepageGuestName); ?>" required>
                                    </div>
                                    <div class="col-lg-6">
                                        <input type="tel" name="guest_phone" class="form-control py-3 border-white bg-transparent text-white" placeholder="Phone Number" value="<?php echo frontend_escape($homepageGuestPhone); ?>" required>
                                    </div>
                                    <div class="col-lg-6">
                                        <input type="email" name="guest_email" class="form-control py-3 border-white bg-transparent text-white" placeholder="Email" value="<?php echo frontend_escape($homepageGuestEmail); ?>">
                                    </div>
                                    <div class="col-lg-6">
                                        <select name="outlet_id" class="form-select py-3 border-white bg-transparent" aria-label="Select Branch" required>
                                            <option value="" disabled<?php echo $homepageSelectedBranchId <= 0 ? ' selected' : ''; ?>>Select Branch</option>
<?php foreach ($frontendBranches as $branch): ?>
<?php
	$branchId = (int) ($branch['id'] ?? 0);
	$branchName = trim((string) ($branch['branch_name'] ?? ''));
	$areaCity = trim((string) ($branch['area_city'] ?? ''));
	$branchLabel = $branchName !== '' ? $branchName : 'Branch';

	if ($areaCity !== '') {
		$branchLabel .= ' - ' . $areaCity;
	}
?>
                                            <option value="<?php echo frontend_escape((string) $branchId); ?>"<?php echo $homepageSelectedBranchId === $branchId ? ' selected' : ''; ?>><?php echo frontend_escape($branchLabel); ?></option>
<?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-lg-6">
                                        <select id="homepage-service-category-id" name="service_category_id" class="form-select py-3 border-white bg-transparent" aria-label="Select Service Category" required>
                                            <option value="" disabled<?php echo $homepageSelectedCategoryId <= 0 ? ' selected' : ''; ?>>Select Service Category</option>
<?php foreach ($frontendAllServiceCategories as $category): ?>
<?php $categoryId = (int) ($category['id'] ?? 0); ?>
                                            <option value="<?php echo frontend_escape((string) $categoryId); ?>"<?php echo $homepageSelectedCategoryId === $categoryId ? ' selected' : ''; ?>><?php echo frontend_escape((string) ($category['category_name'] ?? '')); ?></option>
<?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-lg-6">
                                        <select id="homepage-service-id" name="service_id" class="form-select py-3 border-white bg-transparent" aria-label="Select Service" required data-selected-service-id="<?php echo frontend_escape((string) $homepageSelectedServiceId); ?>">
                                            <option value="" selected disabled><?php echo $homepageSelectedCategoryId > 0 ? 'Select Service' : 'Select category first'; ?></option>
                                        </select>
                                    </div>
                                    <div class="col-lg-6">
                                        <input type="date" name="appointment_date" class="form-control py-3 border-white bg-transparent text-white" value="<?php echo frontend_escape($homepageAppointmentDate); ?>" required>
                                    </div>
                                    <div class="col-lg-6">
                                        <input type="time" name="appointment_time" class="form-control py-3 border-white bg-transparent text-white" value="<?php echo frontend_escape($homepageAppointmentTime); ?>" required>
                                    </div>
                                    <div class="col-lg-12">
                                        <textarea class="form-control border-white bg-transparent text-white" name="notes" id="homepage-area-text" cols="30" rows="5" placeholder="Special Request / Notes"><?php echo frontend_escape($homepageNotes); ?></textarea>
                                    </div>
                                    <div class="col-lg-12">
                                        <button type="submit" class="btn btn-primary btn-primary-outline-0 w-100 py-3 px-5">BOOK APPOINTMENT</button>
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
            <!-- Counter Start -->
            <div class="container-fluid counter-section">
                <div class="container py-5">
                    <div class="row g-5 justify-content-center">
                        <div class="col-md-6 col-lg-4 col-xl-4">
                            <div class="counter-item p-5">
                                <div class="counter-content bg-white p-4">
                                    <i class="fas fa-globe fa-5x text-primary mb-3"></i>
                                    <h5 class="text-primary">Worldwide Clients</h5>
                                    <div class="svg-img">
                                        <svg width="100" height="50">
                                            <polygon points="55, 10 85, 55 25, 55 25," style="fill: #DCCAF2;"/>
                                        </svg>
                                    </div>
                                </div>
                                <div class="counter-quantity">
                                    <span class="text-white fs-2 fw-bold" data-toggle="counter-up">379</span>
                                    <span class="h1 fw-bold text-white">+</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-4 col-xl-4">
                            <div class="counter-item p-5">
                                <div class="counter-content bg-white p-4">
                                    <i class="fas fa-spa fa-5x text-primary mb-3"></i>
                                    <h5 class="text-primary">Wellness & Spa</h5>
                                    <div class="svg-img">
                                        <svg width="100" height="50">
                                            <polygon points="55, 10 85, 55 25, 55 25," style="fill: #DCCAF2;"/>
                                        </svg>
                                    </div>
                                </div>
                                <div class="counter-quantity">
                                    <span class="text-white fs-2 fw-bold" data-toggle="counter-up">829</span>
                                    <span class="h1 fw-bold text-white">+</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-4 col-xl-4">
                            <div class="counter-item p-5">
                                <div class="counter-content bg-white p-4">
                                    <i class="fas fa-users fa-5x text-primary mb-3"></i>
                                    <h5 class="text-primary">Happy Customers</h5>
                                    <div class="svg-img">
                                        <svg width="100" height="50">
                                            <polygon points="55, 10 85, 55 25, 55 25," style="fill: #DCCAF2;"/>
                                        </svg>
                                    </div>
                                </div>
                                <div class="counter-quantity">
                                    <span class="text-white fs-2 fw-bold" data-toggle="counter-up">713</span>
                                    <span class="h1 fw-bold text-white">+</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Counter End -->
        </div>
        <!-- Appointment End -->

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var categorySelect = document.getElementById('homepage-service-category-id');
                var serviceSelect = document.getElementById('homepage-service-id');

                if (!categorySelect || !serviceSelect) {
                    return;
                }

                var servicesByCategory = <?php echo json_encode($homepageServicesJson, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
                var initialServiceId = serviceSelect.getAttribute('data-selected-service-id') || '';

                function formatServiceLabel(service) {
                    var price = Number(service.price || 0).toFixed(2);
                    return service.service_name + ' - BDT ' + price;
                }

                function renderServiceOptions(categoryId, selectedServiceId) {
                    serviceSelect.innerHTML = '';

                    if (!categoryId) {
                        serviceSelect.appendChild(new Option('Select category first', '', true, false));
                        return;
                    }

                    var services = servicesByCategory[String(categoryId)] || [];

                    if (!services.length) {
                        serviceSelect.appendChild(new Option('No services available', '', true, false));
                        return;
                    }

                    serviceSelect.appendChild(new Option('Select Service', '', selectedServiceId === '', false));

                    services.forEach(function (service) {
                        var serviceId = String(service.id);
                        var isSelected = selectedServiceId === serviceId;
                        serviceSelect.appendChild(new Option(formatServiceLabel(service), serviceId, isSelected, isSelected));
                    });

                    if (selectedServiceId !== '' && !services.some(function (service) { return String(service.id) === selectedServiceId; })) {
                        serviceSelect.value = '';
                    }
                }

                renderServiceOptions(categorySelect.value, initialServiceId);

                categorySelect.addEventListener('change', function () {
                    renderServiceOptions(categorySelect.value, '');
                });
            });
        </script>


        <!-- Gallery Start -->
        <div class="container-fluid gallery py-5">
            <div class="container py-5">
                <div class="text-center mx-auto mb-5" style="max-width: 800px;">
                    <p class="fs-4 text-uppercase text-primary">Our Gallery</p>
                    <h1 class="display-4 mb-4">Let's See Our Gallery</h1>
                </div>
                <div class="tab-class text-center">
                    <ul class="nav nav-pills d-inline-flex justify-content-center mb-5">
                        <li class="nav-item">
                            <a class="d-flex mx-3 py-2 border border-primary bg-light rounded-pill active" data-bs-toggle="pill" href="#tab-1">
                                <span class="text-dark" style="width: 150px;">All Gallery</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="d-flex py-2 mx-3 border border-primary bg-light rounded-pill" data-bs-toggle="pill" href="#tab-2">
                                <span class="text-dark" style="width: 150px;">Skin Care</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="d-flex mx-3 py-2 border border-primary bg-light rounded-pill" data-bs-toggle="pill" href="#tab-3">
                                <span class="text-dark" style="width: 150px;">Stream Bath</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="d-flex mx-3 py-2 border border-primary bg-light rounded-pill" data-bs-toggle="pill" href="#tab-4">
                                <span class="text-dark" style="width: 150px;">Stone Therapy</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="d-flex mx-3 py-2 border border-primary bg-light rounded-pill" data-bs-toggle="pill" href="#tab-5">
                                <span class="text-dark" style="width: 150px;">Face Massage</span>
                            </a>
                        </li>
                    </ul>
                    <div class="tab-content">
                        <div id="tab-1" class="tab-pane fade show p-0 active">
                            <div class="row g-4">
                                <div class="col-lg-12">
                                    <div class="row g-4">
                                        <div class="col-lg-3">
                                            <div class="gallery-img">
                                                <img class="img-fluid rounded w-100" src="img/gallery-1.jpg" alt="">
                                                <div class="gallery-overlay p-4">
                                                    <h4 class="text-secondary">Skin Care</h4>
                                                </div>
                                                <div class="search-icon">
                                                    <a href="img/gallery-1.jpg" data-lightbox="Gallery-1" class="my-auto"><i class="fas fa-search-plus btn-primary btn-primary-outline-0 rounded-circle p-3"></i></a>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-3">
                                            <div class="gallery-img">
                                                <img class="img-fluid rounded w-100" src="img/gallery-2.jpg" alt="">
                                                <div class="gallery-overlay p-4">
                                                    <h4 class="text-secondary">Stream Bath</h4>
                                                </div>
                                                <div class="search-icon">
                                                    <a href="img/gallery-2.jpg" data-lightbox="Gallery-2" class="my-auto"><i class="fas fa-search-plus btn-primary btn-primary-outline-0 rounded-circle p-3"></i></a>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-3">
                                            <div class="gallery-img">
                                                <img class="img-fluid rounded w-100" src="img/gallery-3.jpg" alt="">
                                                <div class="gallery-overlay p-4">
                                                    <h4 class="text-secondary">Stone Therapy</h4>
                                                </div>
                                                <div class="search-icon">
                                                    <a href="img/gallery-3.jpg" data-lightbox="Gallery-3" class="my-auto"><i class="fas fa-search-plus btn-primary btn-primary-outline-0 rounded-circle p-3"></i></a>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-3">
                                            <div class="gallery-img">
                                                <img class="img-fluid rounded w-100" src="img/gallery-4.jpg" alt="">
                                                <div class="gallery-overlay p-4">
                                                    <h4 class="text-secondary">Face Massage</h4>
                                                </div>
                                                <div class="search-icon">
                                                    <a href="img/gallery-4.jpg" data-lightbox="Gallery-4" class="my-auto"><i class="fas fa-search-plus btn-primary btn-primary-outline-0 rounded-circle p-3"></i></a>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-3">
                                            <div class="gallery-img">
                                                <img class="img-fluid rounded w-100" src="img/gallery-5.jpg" alt="">
                                                <div class="gallery-overlay p-4">
                                                    <h4 class="text-secondary">Skin Care</h4>
                                                </div>
                                                <div class="search-icon">
                                                    <a href="img/gallery-5.jpg" data-lightbox="Gallery-5" class="my-auto"><i class="fas fa-search-plus btn-primary btn-primary-outline-0 rounded-circle p-3"></i></a>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-3">
                                            <div class="gallery-img">
                                                <img class="img-fluid rounded w-100" src="img/gallery-6.jpg" alt="">
                                                <div class="gallery-overlay p-4">
                                                    <h4 class="text-secondary">Stream Bath</h4>
                                                </div>
                                                <div class="search-icon">
                                                    <a href="img/gallery-6.jpg" data-lightbox="Gallery-6" class="my-auto"><i class="fas fa-search-plus btn-primary btn-primary-outline-0 rounded-circle p-3"></i></a>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-3">
                                            <div class="gallery-img">
                                                <img class="img-fluid rounded w-100" src="img/gallery-7.jpg" alt="">
                                                <div class="gallery-overlay p-4">
                                                    <h4 class="text-secondary">Stone Therapy</h4>
                                                </div>
                                                <div class="search-icon">
                                                    <a href="img/gallery-7.jpg" data-lightbox="Gallery-7" class="my-auto"><i class="fas fa-search-plus btn-primary btn-primary-outline-0 rounded-circle p-3"></i></a>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-3">
                                            <div class="gallery-img">
                                                <img class="img-fluid rounded w-100" src="img/gallery-8.jpg" alt="">
                                                <div class="gallery-overlay p-4">
                                                    <h4 class="text-secondary">Face Massage</h4>
                                                </div>
                                                <div class="search-icon">
                                                    <a href="img/gallery-8.jpg" data-lightbox="Gallery-8" class="my-auto"><i class="fas fa-search-plus btn-primary btn-primary-outline-0 rounded-circle p-3"></i></a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div id="tab-2" class="tab-pane fade show p-0">
                            <div class="row g-4">
                                <div class="col-lg-12">
                                    <div class="row g-4">
                                        <div class="col-lg-3">
                                            <div class="gallery-img">
                                                <img class="img-fluid rounded w-100" src="img/gallery-9.jpg" alt="">
                                                <div class="gallery-overlay p-4">
                                                    <h4 class="text-secondary">Skin Care</h4>
                                                </div>
                                                <div class="search-icon">
                                                    <a href="img/gallery-9.jpg" data-lightbox="Gallery-9" class="my-auto"><i class="fas fa-search-plus btn-primary btn-primary-outline-0 rounded-circle p-3"></i></a>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-3">
                                            <div class="gallery-img">
                                                <img class="img-fluid rounded w-100" src="img/gallery-10.jpg" alt="">
                                                <div class="gallery-overlay p-4">
                                                    <h4 class="text-secondary">Skin Care</h4>
                                                </div>
                                                <div class="search-icon">
                                                    <a href="img/gallery-10.jpg" data-lightbox="Gallery-10" class="my-auto"><i class="fas fa-search-plus btn-primary btn-primary-outline-0 rounded-circle p-3"></i></a>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-3">
                                            <div class="gallery-img">
                                                <img class="img-fluid rounded w-100" src="img/gallery-5.jpg" alt="">
                                                <div class="gallery-overlay p-4">
                                                    <h4 class="text-secondary">Skin Care</h4>
                                                </div>
                                                <div class="search-icon">
                                                    <a href="img/gallery-5.jpg" data-lightbox="Gallery-11" class="my-auto"><i class="fas fa-search-plus btn-primary btn-primary-outline-0 rounded-circle p-3"></i></a>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-3">
                                            <div class="gallery-img">
                                                <img class="img-fluid rounded w-100" src="img/gallery-1.jpg" alt="">
                                                <div class="gallery-overlay p-4">
                                                    <h4 class="text-secondary">Skin Care</h4>
                                                </div>
                                                <div class="search-icon">
                                                    <a href="img/gallery-1.jpg" data-lightbox="Gallery-12" class="my-auto"><i class="fas fa-search-plus btn-primary btn-primary-outline-0 rounded-circle p-3"></i></a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div id="tab-3" class="tab-pane fade show p-0">
                            <div class="row g-4">
                                <div class="col-lg-12">
                                    <div class="row g-4">
                                        <div class="col-lg-3">
                                            <div class="gallery-img">
                                                <img class="img-fluid rounded w-100" src="img/gallery-11.jpg" alt="">
                                                <div class="gallery-overlay p-4">
                                                    <h4 class="text-secondary">Stream Bath</h4>
                                                </div>
                                                <div class="search-icon">
                                                    <a href="img/gallery-11.jpg" data-lightbox="Gallery-13" class="my-auto"><i class="fas fa-search-plus btn-primary btn-primary-outline-0 rounded-circle p-3"></i></a>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-3">
                                            <div class="gallery-img">
                                                <img class="img-fluid rounded w-100" src="img/gallery-12.jpg" alt="">
                                                <div class="gallery-overlay p-4">
                                                    <h4 class="text-secondary">Stream Bath</h4>
                                                </div>
                                                <div class="search-icon">
                                                    <a href="img/gallery-12.jpg" data-lightbox="Gallery-14" class="my-auto"><i class="fas fa-search-plus btn-primary btn-primary-outline-0 rounded-circle p-3"></i></a>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-3">
                                            <div class="gallery-img">
                                                <img class="img-fluid rounded w-100" src="img/gallery-2.jpg" alt="">
                                                <div class="gallery-overlay p-4">
                                                    <h4 class="text-secondary">Stream Bath</h4>
                                                </div>
                                                <div class="search-icon">
                                                    <a href="img/gallery-2.jpg" data-lightbox="Gallery-15" class="my-auto"><i class="fas fa-search-plus btn-primary btn-primary-outline-0 rounded-circle p-3"></i></a>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-3">
                                            <div class="gallery-img">
                                                <img class="img-fluid rounded w-100" src="img/gallery-6.jpg" alt="">
                                                <div class="gallery-overlay p-4">
                                                    <h4 class="text-secondary">Stream Bath</h4>
                                                </div>
                                                <div class="search-icon">
                                                    <a href="img/gallery-6.jpg" data-lightbox="Gallery-16" class="my-auto"><i class="fas fa-search-plus btn-primary btn-primary-outline-0 rounded-circle p-3"></i></a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div id="tab-4" class="tab-pane fade show p-0">
                            <div class="row g-4">
                                <div class="col-lg-12">
                                    <div class="row g-4">
                                        <div class="col-lg-3">
                                            <div class="gallery-img">
                                                <img class="img-fluid rounded w-100" src="img/gallery-13.jpg" alt="">
                                                <div class="gallery-overlay p-4">
                                                    <h4 class="text-secondary">Stone Therapy</h4>
                                                </div>
                                                <div class="search-icon">
                                                    <a href="img/gallery-13.jpg" data-lightbox="Gallery-17" class="my-auto"><i class="fas fa-search-plus btn-primary btn-primary-outline-0 rounded-circle p-3"></i></a>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-3">
                                            <div class="gallery-img">
                                                <img class="img-fluid rounded w-100" src="img/gallery-2.jpg" alt="">
                                                <div class="gallery-overlay p-4">
                                                    <h4 class="text-secondary">Stone Therapy</h4>
                                                </div>
                                                <div class="search-icon">
                                                    <a href="img/gallery-2.jpg" data-lightbox="Gallery-18" class="my-auto"><i class="fas fa-search-plus btn-primary btn-primary-outline-0 rounded-circle p-3"></i></a>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-3">
                                            <div class="gallery-img">
                                                <img class="img-fluid rounded w-100" src="img/gallery-3.jpg" alt="">
                                                <div class="gallery-overlay p-4">
                                                    <h4 class="text-secondary">Stone Therapy</h4>
                                                </div>
                                                <div class="search-icon">
                                                    <a href="img/gallery-3.jpg" data-lightbox="Gallery-19" class="my-auto"><i class="fas fa-search-plus btn-primary btn-primary-outline-0 rounded-circle p-3"></i></a>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-3">
                                            <div class="gallery-img">
                                                <img class="img-fluid rounded w-100" src="img/gallery-7.jpg" alt="">
                                                <div class="gallery-overlay p-4">
                                                    <h4 class="text-secondary">Stone Therapy</h4>
                                                </div>
                                                <div class="search-icon">
                                                    <a href="img/gallery-7.jpg" data-lightbox="Gallery-20" class="my-auto"><i class="fas fa-search-plus btn-primary btn-primary-outline-0 rounded-circle p-3"></i></a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div id="tab-5" class="tab-pane fade show p-0">
                            <div class="row g-4">
                                <div class="col-lg-12">
                                    <div class="row g-4">
                                        <div class="col-lg-3">
                                            <div class="gallery-img">
                                                <img class="img-fluid rounded w-100" src="img/gallery-4.jpg" alt="">
                                                <div class="gallery-overlay p-4">
                                                    <h4 class="text-secondary">Face Massage</h4>
                                                </div>
                                                <div class="search-icon">
                                                    <a href="img/gallery-4.jpg" data-lightbox="Gallery-21" class="my-auto"><i class="fas fa-search-plus btn-primary btn-primary-outline-0 rounded-circle p-3"></i></a>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-3">
                                            <div class="gallery-img">
                                                <img class="img-fluid rounded w-100" src="img/gallery-6.jpg" alt="">
                                                <div class="gallery-overlay p-4">
                                                    <h4 class="text-secondary">Face Massage</h4>
                                                </div>
                                                <div class="search-icon">
                                                    <a href="img/gallery-6.jpg" data-lightbox="Gallery-22" class="my-auto"><i class="fas fa-search-plus btn-primary btn-primary-outline-0 rounded-circle p-3"></i></a>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-3">
                                            <div class="gallery-img">
                                                <img class="img-fluid rounded w-100" src="img/gallery-8.jpg" alt="">
                                                <div class="gallery-overlay p-4">
                                                    <h4 class="text-secondary">Face Massage</h4>
                                                </div>
                                                <div class="search-icon">
                                                    <a href="img/gallery-8.jpg" data-lightbox="Gallery-23" class="my-auto"><i class="fas fa-search-plus btn-primary btn-primary-outline-0 rounded-circle p-3"></i></a>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-3">
                                            <div class="gallery-img">
                                                <img class="img-fluid rounded w-100" src="img/gallery-14.jpg" alt="">
                                                <div class="gallery-overlay p-4">
                                                    <h4 class="text-secondary">Face Massage</h4>
                                                </div>
                                                <div class="search-icon">
                                                    <a href="img/gallery-14.jpg" data-lightbox="Gallery-24" class="my-auto"><i class="fas fa-search-plus btn-primary btn-primary-outline-0 rounded-circle p-3"></i></a>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-3">
                                            <div class="gallery-img">
                                                <img class="img-fluid rounded w-100" src="img/gallery-4.jpg" alt="">
                                                <div class="gallery-overlay p-4">
                                                    <h4 class="text-secondary">Face Massage</h4>
                                                </div>
                                                <div class="search-icon">
                                                    <a href="img/gallery-4.jpg" data-lightbox="Gallery-25" class="my-auto"><i class="fas fa-search-plus btn-primary btn-primary-outline-0 rounded-circle p-3"></i></a>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-3">
                                            <div class="gallery-img">
                                                <img class="img-fluid rounded w-100" src="img/gallery-8.jpg" alt="">
                                                <div class="gallery-overlay p-4">
                                                    <h4 class="text-secondary">Face Massage</h4>
                                                </div>
                                                <div class="search-icon">
                                                    <a href="img/gallery-8.jpg" data-lightbox="Gallery-26" class="my-auto"><i class="fas fa-search-plus btn-primary btn-primary-outline-0 rounded-circle p-3"></i></a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- gallery End -->


        <!-- Pricing Start -->
        <div class="container-fluid pricing py-5">
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


        <!-- Team Start -->
        <div class="container-fluid team py-5">
            <div class="container py-5">
                <div class="text-center mx-auto mb-5" style="max-width: 800px;">
                    <p class="fs-4 text-uppercase text-primary">Spa Specialist</p>
                    <h1 class="display-4 mb-4">Spa & Beauty Specialist</h1>
                </div>
                <div class="row g-4">
                    <div class="col-md-6 col-lg-6 col-xl-3">
                        <div class="team-item">
                            <div class="team-img rounded-top">
                                <img src="img/team-1.png" class="img-fluid w-100 rounded-top bg-light" alt="">
                            </div>
                            <div class="team-text rounded-bottom text-center p-4">
                                <h3 class="text-white">Oliva Mia</h3>
                                <p class="mb-0 text-white">Spa & Beauty Expert</p>
                            </div>
                            <div class="team-social">
                                <a class="btn btn-light btn-light-outline-0 btn-square rounded-circle mb-2" href="#"><i class="fab fa-twitter"></i></a>
                                <a class="btn btn-light btn-light-outline-0 btn-square rounded-circle mb-2" href="#"><i class="fab fa-facebook-f"></i></a>
                                <a class="btn btn-light btn-light-outline-0 btn-square rounded-circle mb-2" href="#"><i class="fab fa-linkedin-in"></i></a>
                                <a class="btn btn-light btn-light-outline-0 btn-square rounded-circle" href="#"><i class="fab fa-instagram"></i></a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-6 col-xl-3">
                        <div class="team-item">
                            <div class="team-img rounded-top">
                                <img src="img/team-2.png" class="img-fluid w-100 rounded-top bg-light" alt="">
                            </div>
                            <div class="team-text rounded-bottom text-center p-4">
                                <h3 class="text-white">Charlotte Ross</h3>
                                <p class="mb-0 text-white">Spa & Beauty Expert</p>
                            </div>
                            <div class="team-social">
                                <a class="btn btn-light btn-light-outline-0 btn-square rounded-circle mb-2" href="#"><i class="fab fa-twitter"></i></a>
                                <a class="btn btn-light btn-light-outline-0 btn-square rounded-circle mb-2" href="#"><i class="fab fa-facebook-f"></i></a>
                                <a class="btn btn-light btn-light-outline-0 btn-square rounded-circle mb-2" href="#"><i class="fab fa-linkedin-in"></i></a>
                                <a class="btn btn-light btn-light-outline-0 btn-square rounded-circle" href="#"><i class="fab fa-instagram"></i></a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-6 col-xl-3">
                        <div class="team-item">
                            <div class="team-img rounded-top">
                                <img src="img/team-3.png" class="img-fluid w-100 rounded-top bg-light" alt="">
                            </div>
                            <div class="team-text rounded-bottom text-center p-4">
                                <h3 class="text-white">Amelia Luna</h3>
                                <p class="mb-0 text-white">Spa & Beauty Expert</p>
                            </div>
                            <div class="team-social">
                                <a class="btn btn-light btn-light-outline-0 btn-square rounded-circle mb-2" href="#"><i class="fab fa-twitter"></i></a>
                                <a class="btn btn-light btn-light-outline-0 btn-square rounded-circle mb-2" href="#"><i class="fab fa-facebook-f"></i></a>
                                <a class="btn btn-light btn-light-outline-0 btn-square rounded-circle mb-2" href="#"><i class="fab fa-linkedin-in"></i></a>
                                <a class="btn btn-light btn-light-outline-0 btn-square rounded-circle" href="#"><i class="fab fa-instagram"></i></a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-6 col-xl-3">
                        <div class="team-item">
                            <div class="team-img rounded-top">
                                <img src="img/team-4.png" class="img-fluid w-100 rounded-top bg-light" alt="">
                            </div>
                            <div class="team-text rounded-bottom text-center p-4">
                                <h3 class="text-white">Isabella Evelyn</h3>
                                <p class="mb-0 text-white">Spa & Beauty Expert</p>
                            </div>
                            <div class="team-social">
                                <a class="btn btn-light btn-light-outline-0 btn-square rounded-circle mb-2" href="#"><i class="fab fa-twitter"></i></a>
                                <a class="btn btn-light btn-light-outline-0 btn-square rounded-circle mb-2" href="#"><i class="fab fa-facebook-f"></i></a>
                                <a class="btn btn-light btn-light-outline-0 btn-square rounded-circle mb-2" href="#"><i class="fab fa-linkedin-in"></i></a>
                                <a class="btn btn-light btn-light-outline-0 btn-square rounded-circle" href="#"><i class="fab fa-instagram"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Team End -->


        <!-- Testimonial Start -->
        <div class="container-fluid testimonial py-5">
            <div class="container py-5">
                <div class="text-center mx-auto mb-5" style="max-width: 800px;">
                    <p class="fs-4 text-uppercase text-primary">Testimonial</p>
                    <h1 class="display-4 mb-4 text-white">What Our Clients Say!</h1>
                </div>
                <div class="owl-carousel testimonial-carousel">
                    <div class="testimonial-item rounded p-4">
                        <div class="row">
                            <div class="col-4">
                                <div class="d-flex flex-column mx-auto">
                                    <div class="rounded-circle mb-4" style="border: dashed; border-color: var(--bs-white);">
                                        <img src="img/testimonial-1.jpg" class="img-fluid rounded-circle" alt="">
                                    </div>
                                    <div class="text-center">
                                        <h4 class="mb-2 text-primary">Person Name</h4>
                                        <p class="m-0 text-white">Profession</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-8">
                                <div class="position-absolute" style="top: 20px; right: 25px;">
                                    <i class="fa fa-quote-right fa-2x text-secondary"></i>
                                </div>
                                <div class="testimonial-content">
                                    <div class="d-flex mb-4">
                                        <i class="fas fa-star text-primary"></i>
                                        <i class="fas fa-star text-primary"></i>
                                        <i class="fas fa-star text-primary"></i>
                                        <i class="fas fa-star text-primary"></i>
                                        <i class="fas fa-star"></i>
                                    </div>
                                    <p class="fs-5 mb-0 text-white">Lorem ipsum dolor sit amet elit, sed do eiusmod tempor ut labore et dolore magna aliqua is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="testimonial-item rounded p-4">
                        <div class="row">
                            <div class="col-4">
                                <div class="d-flex flex-column mx-auto">
                                    <div class="rounded-circle mb-4" style="border: dashed; border-color: var(--bs-white);">
                                        <img src="img/testimonial-2.jpg" class="img-fluid rounded-circle" alt="">
                                    </div>
                                    <div class="text-center">
                                        <h4 class="mb-2 text-primary">Person Name</h4>
                                        <p class="m-0 text-white">Profession</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-8">
                                <div class="position-absolute" style="top: 20px; right: 25px;">
                                    <i class="fa fa-quote-right fa-2x text-secondary"></i>
                                </div>
                                <div class="testimonial-content">
                                    <div class="d-flex mb-4">
                                        <i class="fas fa-star text-primary"></i>
                                        <i class="fas fa-star text-primary"></i>
                                        <i class="fas fa-star text-primary"></i>
                                        <i class="fas fa-star text-primary"></i>
                                        <i class="fas fa-star"></i>
                                    </div>
                                    <p class="fs-5 mb-0 text-white">Lorem ipsum dolor sit amet elit, sed do eiusmod tempor ut labore et dolore magna aliqua is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="testimonial-item rounded p-4">
                        <div class="row">
                            <div class="col-4">
                                <div class="d-flex flex-column mx-auto">
                                    <div class="rounded-circle mb-4" style="border: dashed; border-color: var(--bs-white);">
                                        <img src="img/testimonial-3.jpg" class="img-fluid rounded-circle" alt="">
                                    </div>
                                    <div class="text-center">
                                        <h4 class="mb-2 text-primary">Person Name</h4>
                                        <p class="m-0 text-white">Profession</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-8">
                                <div class="position-absolute" style="top: 20px; right: 25px;">
                                    <i class="fa fa-quote-right fa-2x text-secondary"></i>
                                </div>
                                <div class="testimonial-content">
                                    <div class="d-flex mb-4">
                                        <i class="fas fa-star text-primary"></i>
                                        <i class="fas fa-star text-primary"></i>
                                        <i class="fas fa-star text-primary"></i>
                                        <i class="fas fa-star text-primary"></i>
                                        <i class="fas fa-star"></i>
                                    </div>
                                    <p class="fs-5 mb-0 text-white">Lorem ipsum dolor sit amet elit, sed do eiusmod tempor ut labore et dolore magna aliqua is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Testimonial End -->


        <!-- Contact Start -->
        <div class="container-fluid py-5">
            <div class="container py-5">
                <div class="row g-4 align-items-center">
                    <div class="col-12">
                        <div class="row g-4">
                            <div class="col-lg-4">
                                <div class="d-inline-flex bg-light w-100 border border-primary p-4 rounded">
                                    <i class="fas fa-map-marker-alt fa-2x text-primary me-4"></i>
                                    <div>
                                        <h4>Address</h4>
                                        <p class="mb-0">123 North tower New York, USA</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="d-inline-flex bg-light w-100 border border-primary p-4 rounded">
                                    <i class="fas fa-envelope fa-2x text-primary me-4"></i>
                                    <div>
                                        <h4>Mail Us</h4>
                                        <p class="mb-0">info@example.com</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="d-inline-flex bg-light w-100 border border-primary p-4 rounded">
                                    <i class="fa fa-phone-alt fa-2x text-primary me-4"></i>
                                    <div>
                                        <h4>Telephone</h4>
                                        <p class="mb-0">(+012) 3456 7890 123</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="rounded">
                            <iframe class="rounded-top w-100" 
                            style="height: 450px; margin-bottom: -6px;" src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d387191.33750346623!2d-73.97968099999999!3d40.6974881!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x89c24fa5d33f083b%3A0xc80b8f06e177fe62!2sNew%20York%2C%20NY%2C%20USA!5e0!3m2!1sen!2sbd!4v1694259649153!5m2!1sen!2sbd" 
                            loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                        </div>
                        <div class=" text-center p-4 rounded-bottom bg-primary">
                            <h4 class="text-white fw-bold">Follow Us</h4>
                            <div class="d-flex align-items-center justify-content-center">
                                <a href="#" class="btn btn-light btn-light-outline-0 btn-square rounded-circle me-3"><i class="fab fa-facebook-f"></i></a>
                                <a href="#" class="btn btn-light btn-light-outline-0 btn-square rounded-circle me-3"><i class="fab fa-twitter"></i></a>
                                <a href="#" class="btn btn-light btn-light-outline-0 btn-square rounded-circle me-3"><i class="fab fa-instagram"></i></a>
                                <a href="#" class="btn btn-light btn-light-outline-0 btn-square rounded-circle"><i class="fab fa-linkedin-in"></i></a>
                            </div>   
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Contact End -->



<?php require_once __DIR__ . '/includes/footer.php'; ?>

<?php require_once __DIR__ . '/includes/scripts.php'; ?>
    </body>

</html>
