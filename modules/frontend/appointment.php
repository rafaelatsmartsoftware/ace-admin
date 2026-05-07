<?php
$pageTitle = 'Sparlex - Spa Website Template';
$currentPage = 'appointment.php';
require_once __DIR__ . '/includes/head.php';

$frontendBranches = get_frontend_branches();
$frontendServiceCategories = get_frontend_service_categories();
$frontendServicesByCategory = get_frontend_services_grouped_by_category();

$appointmentErrors = [];
$appointmentSuccess = isset($_GET['success']) && $_GET['success'] === '1';
$pdo = ace_admin_db();

$selectedBranchId = isset($_POST['outlet_id']) ? (int) $_POST['outlet_id'] : 0;
$selectedCategoryId = isset($_POST['service_category_id']) ? (int) $_POST['service_category_id'] : 0;
$selectedServiceId = isset($_POST['service_id']) ? (int) $_POST['service_id'] : 0;
$selectedService = null;

if ($selectedServiceId <= 0 && isset($_GET['service_id'])) {
	$selectedServiceId = (int) $_GET['service_id'];
}

if ($selectedServiceId > 0) {
	$selectedService = get_frontend_service_by_id($selectedServiceId);

	if ($selectedService !== null) {
		$selectedServiceId = (int) ($selectedService['id'] ?? 0);

		if ($selectedCategoryId <= 0) {
			$selectedCategoryId = (int) ($selectedService['service_category_id'] ?? 0);
		}
	} else {
		$selectedServiceId = 0;
	}
}

$guestName = trim((string) ($_POST['guest_name'] ?? ''));
$guestPhone = trim((string) ($_POST['guest_phone'] ?? ''));
$guestEmail = trim((string) ($_POST['guest_email'] ?? ''));
$appointmentDate = trim((string) ($_POST['appointment_date'] ?? ''));
$appointmentTime = trim((string) ($_POST['appointment_time'] ?? ''));
$notes = trim((string) ($_POST['notes'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if ($guestName === '') {
		$appointmentErrors[] = 'Full Name is required.';
	}

	if ($guestPhone === '') {
		$appointmentErrors[] = 'Phone Number is required.';
	}

	if ($selectedBranchId <= 0) {
		$appointmentErrors[] = 'Please select a branch.';
	}

	if ($selectedCategoryId <= 0) {
		$appointmentErrors[] = 'Please select a service category.';
	}

	if ($selectedServiceId <= 0) {
		$appointmentErrors[] = 'Please select a service.';
	}

	if ($appointmentDate === '') {
		$appointmentErrors[] = 'Appointment Date is required.';
	}

	if ($appointmentTime === '') {
		$appointmentErrors[] = 'Appointment Time is required.';
	}

	if ($guestEmail !== '' && !filter_var($guestEmail, FILTER_VALIDATE_EMAIL)) {
		$appointmentErrors[] = 'Please enter a valid email address.';
	}

	$today = new DateTimeImmutable('today');
	$dateObject = DateTimeImmutable::createFromFormat('Y-m-d', $appointmentDate);
	$dateErrors = DateTimeImmutable::getLastErrors();
	$dateWarningCount = is_array($dateErrors) ? (int) ($dateErrors['warning_count'] ?? 0) : 0;
	$dateErrorCount = is_array($dateErrors) ? (int) ($dateErrors['error_count'] ?? 0) : 0;

	if ($appointmentDate !== '' && (!$dateObject || $dateWarningCount > 0 || $dateErrorCount > 0)) {
		$appointmentErrors[] = 'Please select a valid appointment date.';
	} elseif ($dateObject instanceof DateTimeImmutable && $dateObject < $today) {
		$appointmentErrors[] = 'Appointment date cannot be in the past.';
	}

	$timeObject = DateTimeImmutable::createFromFormat('H:i', $appointmentTime);
	$timeErrors = DateTimeImmutable::getLastErrors();
	$timeWarningCount = is_array($timeErrors) ? (int) ($timeErrors['warning_count'] ?? 0) : 0;
	$timeErrorCount = is_array($timeErrors) ? (int) ($timeErrors['error_count'] ?? 0) : 0;

	if ($appointmentTime !== '' && (!$timeObject || $timeWarningCount > 0 || $timeErrorCount > 0)) {
		$appointmentErrors[] = 'Please select a valid appointment time.';
	}

	if (!$pdo instanceof PDO) {
		$appointmentErrors[] = 'Booking is temporarily unavailable. Please try again later.';
	}

	$validatedService = null;

	if (empty($appointmentErrors) && $pdo instanceof PDO) {
		try {
			$branchStatement = $pdo->prepare('SELECT id FROM branches WHERE id = :id LIMIT 1');
			$branchStatement->execute(['id' => $selectedBranchId]);

			if (!$branchStatement->fetch()) {
				$appointmentErrors[] = 'Selected branch is not available.';
			}

			$categoryStatement = $pdo->prepare('SELECT id FROM service_categories WHERE id = :id LIMIT 1');
			$categoryStatement->execute(['id' => $selectedCategoryId]);

			if (!$categoryStatement->fetch()) {
				$appointmentErrors[] = 'Selected service category is not available.';
			}

			$serviceStatement = $pdo->prepare(
				'SELECT id, service_category_id
				FROM services
				WHERE id = :id
				LIMIT 1'
			);
			$serviceStatement->execute(['id' => $selectedServiceId]);
			$validatedService = $serviceStatement->fetch();

			if (!$validatedService) {
				$appointmentErrors[] = 'Selected service is not available.';
			} elseif ((int) ($validatedService['service_category_id'] ?? 0) !== $selectedCategoryId) {
				$appointmentErrors[] = 'Selected service does not belong to the chosen category.';
			}
		} catch (PDOException $exception) {
			error_log('Frontend appointment validation failed: ' . $exception->getMessage());
			$appointmentErrors[] = 'We could not validate your booking right now. Please try again later.';
		}
	}

	if (empty($appointmentErrors) && $pdo instanceof PDO && is_array($validatedService)) {
		try {
			$insertStatement = $pdo->prepare(
				'INSERT INTO bookings
				(booking_type, customer_id, guest_name, guest_phone, guest_email, outlet_id, service_id, employee_id, appointment_date, appointment_time, booking_status, payment_method, notes)
				VALUES
				(:booking_type, :customer_id, :guest_name, :guest_phone, :guest_email, :outlet_id, :service_id, :employee_id, :appointment_date, :appointment_time, :booking_status, :payment_method, :notes)'
			);
			$insertStatement->execute([
				'booking_type' => 'guest',
				'customer_id' => null,
				'guest_name' => $guestName,
				'guest_phone' => $guestPhone,
				'guest_email' => $guestEmail !== '' ? $guestEmail : null,
				'outlet_id' => $selectedBranchId,
				'service_id' => $selectedServiceId,
				'employee_id' => null,
				'appointment_date' => $appointmentDate,
				'appointment_time' => $appointmentTime,
				'booking_status' => 'pending',
				'payment_method' => 'pay_at_salon',
				'notes' => $notes !== '' ? $notes : null,
			]);

			header('Location: appointment.php?success=1');
			exit;
		} catch (PDOException $exception) {
			error_log('Frontend appointment insert failed: ' . $exception->getMessage());
			$appointmentErrors[] = 'We could not submit your appointment right now. Please try again later.';
		}
	}
}

$frontendServicesJson = [];

foreach ($frontendServicesByCategory as $categoryId => $services) {
	$categoryKey = (string) ((int) $categoryId);
	$frontendServicesJson[$categoryKey] = [];

	foreach ($services as $service) {
		$frontendServicesJson[$categoryKey][] = [
			'id' => (int) ($service['id'] ?? 0),
			'service_name' => (string) ($service['service_name'] ?? ''),
			'price' => (float) ($service['price'] ?? 0),
		];
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
<?php if ($appointmentSuccess): ?>
                            <div class="alert alert-success mb-4" role="alert">
                                Your appointment request has been submitted successfully. We will contact you soon.
                            </div>
<?php endif; ?>
<?php if (!empty($appointmentErrors)): ?>
                            <div class="alert alert-danger mb-4" role="alert">
                                <ul class="mb-0 ps-3">
<?php foreach ($appointmentErrors as $appointmentError): ?>
                                    <li><?php echo frontend_escape($appointmentError); ?></li>
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
                                        <input type="text" name="guest_name" class="form-control py-3 border-white bg-transparent text-white" placeholder="Full Name" value="<?php echo frontend_escape($guestName); ?>" required>
                                    </div>
                                    <div class="col-lg-6">
                                        <input type="tel" name="guest_phone" class="form-control py-3 border-white bg-transparent text-white" placeholder="Phone Number" value="<?php echo frontend_escape($guestPhone); ?>" required>
                                    </div>
                                    <div class="col-lg-6">
                                        <input type="email" name="guest_email" class="form-control py-3 border-white bg-transparent text-white" placeholder="Email" value="<?php echo frontend_escape($guestEmail); ?>">
                                    </div>
                                    <div class="col-lg-6">
                                        <select name="outlet_id" class="form-select py-3 border-white bg-transparent" aria-label="Select Branch" required>
                                            <option value="" disabled<?php echo $selectedBranchId <= 0 ? ' selected' : ''; ?>>Select Branch</option>
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
                                            <option value="<?php echo frontend_escape((string) $branchId); ?>"<?php echo $selectedBranchId === $branchId ? ' selected' : ''; ?>><?php echo frontend_escape($branchLabel); ?></option>
<?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-lg-6">
                                        <select id="service_category_id" name="service_category_id" class="form-select py-3 border-white bg-transparent" aria-label="Select Service Category" required>
                                            <option value="" disabled<?php echo $selectedCategoryId <= 0 ? ' selected' : ''; ?>>Select Service Category</option>
<?php foreach ($frontendServiceCategories as $category): ?>
<?php $categoryId = (int) ($category['id'] ?? 0); ?>
                                            <option value="<?php echo frontend_escape((string) $categoryId); ?>"<?php echo $selectedCategoryId === $categoryId ? ' selected' : ''; ?>><?php echo frontend_escape((string) ($category['category_name'] ?? '')); ?></option>
<?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-lg-6">
                                        <select id="service_id" name="service_id" class="form-select py-3 border-white bg-transparent" aria-label="Select Service" required data-selected-service-id="<?php echo frontend_escape((string) $selectedServiceId); ?>">
                                            <option value="" selected disabled><?php echo $selectedCategoryId > 0 ? 'Select Service' : 'Select category first'; ?></option>
                                        </select>
                                    </div>
                                    <div class="col-lg-6">
                                        <input type="date" name="appointment_date" class="form-control py-3 border-white bg-transparent text-white" value="<?php echo frontend_escape($appointmentDate); ?>" required>
                                    </div>
                                    <div class="col-lg-6">
                                        <input type="time" name="appointment_time" class="form-control py-3 border-white bg-transparent text-white" value="<?php echo frontend_escape($appointmentTime); ?>" required>
                                    </div>
                                    <div class="col-lg-12">
                                        <textarea class="form-control border-white bg-transparent text-white" name="notes" id="area-text" cols="30" rows="5" placeholder="Special Request / Notes"><?php echo frontend_escape($notes); ?></textarea>
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
        </div>
        <!-- Appointment End -->

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var categorySelect = document.getElementById('service_category_id');
                var serviceSelect = document.getElementById('service_id');

                if (!categorySelect || !serviceSelect) {
                    return;
                }

                var servicesByCategory = <?php echo json_encode($frontendServicesJson, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
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



<?php require_once __DIR__ . '/includes/footer.php'; ?>

<?php require_once __DIR__ . '/includes/scripts.php'; ?>
    </body>

</html>
