<?php
$pageTitle = 'Sparlex - Spa Website Template';
$currentPage = 'services.php';
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

<?php
$frontendServiceCategories = get_frontend_service_categories();
$frontendServicesByCategory = get_frontend_services_by_category();
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
?>



        <!-- Header Start -->
        <div class="container-fluid bg-breadcrumb py-5">
            <div class="container text-center py-5">
                <h3 class="text-white display-3 mb-4">Our Services</h1>
                <ol class="breadcrumb justify-content-center mb-0">
                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">Pages</a></li>
                    <li class="breadcrumb-item active text-white">Service Page</li>
                </ol>    
            </div>
        </div>
        <!-- Header End -->

        
        <!-- Services Start -->
        <div class="container-fluid services py-5">
            <div class="container py-5">
                <div class="mx-auto text-center mb-5" style="max-width: 800px;">
                    <p class="fs-4 text-uppercase text-center text-primary">Our Service</p>
                    <h1 class="display-3">Spa & Beauty Services</h1>
                </div>
                <div class="row g-4">
<?php foreach ($frontendServiceCategories as $categoryIndex => $category): ?>
<?php
	$categoryId = isset($category['id']) ? (int) $category['id'] : 0;
	$isEvenTile = $categoryIndex % 2 === 0;
	$isHiddenTile = $categoryIndex >= 8;
	$imagePath = $frontendServiceImages[$categoryIndex % count($frontendServiceImages)];
	$categoryDescription = trim((string) ($category['description'] ?? ''));
	$categoryDescription = $categoryDescription !== '' ? $categoryDescription : $frontendServiceFallbackText;
	$categoryModalId = 'frontend-service-category-modal-' . ($categoryId > 0 ? $categoryId : $categoryIndex + 1);
?>
                    <div class="col-lg-6<?php echo $isHiddenTile ? ' frontend-extra-service-category' : ''; ?>"<?php echo $isHiddenTile ? ' style="display: none;"' : ''; ?>>
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
<?php if (count($frontendServiceCategories) > 8): ?>
                    <div class="col-12">
                        <div class="services-btn text-center">
                            <button type="button" id="frontend-more-services" class="btn btn-primary btn-primary-outline-0 rounded-pill py-3 px-5">More Services</button>
                        </div>
                    </div>
<?php endif; ?>
                </div>
            </div>
        </div>
        <!-- Services End -->

<?php foreach ($frontendServiceCategories as $categoryIndex => $category): ?>
<?php
	$categoryId = isset($category['id']) ? (int) $category['id'] : 0;
	$categoryModalId = 'frontend-service-category-modal-' . ($categoryId > 0 ? $categoryId : $categoryIndex + 1);
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

<?php if (count($frontendServiceCategories) > 8): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var moreServicesButton = document.getElementById('frontend-more-services');

                if (!moreServicesButton) {
                    return;
                }

                moreServicesButton.addEventListener('click', function () {
                    document.querySelectorAll('.frontend-extra-service-category').forEach(function (categoryTile) {
                        categoryTile.style.display = '';
                    });
                    moreServicesButton.style.display = 'none';
                });
            });
        </script>
<?php endif; ?>



<?php require_once __DIR__ . '/includes/footer.php'; ?>

<?php require_once __DIR__ . '/includes/scripts.php'; ?>
    </body>

</html>
