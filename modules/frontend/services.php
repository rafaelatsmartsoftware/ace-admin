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
	$isEvenTile = $categoryIndex % 2 === 0;
	$isHiddenTile = $categoryIndex >= 8;
	$imagePath = $frontendServiceImages[$categoryIndex % count($frontendServiceImages)];
	$categoryDescription = trim((string) ($category['description'] ?? ''));
	$categoryDescription = $categoryDescription !== '' ? $categoryDescription : $frontendServiceFallbackText;
?>
                    <div class="col-lg-6<?php echo $isHiddenTile ? ' frontend-extra-service-category' : ''; ?>"<?php echo $isHiddenTile ? ' style="display: none;"' : ''; ?>>
                        <div class="services-item bg-light border-4 <?php echo $isEvenTile ? 'border-end' : 'border-start'; ?> border-primary rounded p-4">
                            <div class="row align-items-center">
<?php if ($isEvenTile): ?>
                                <div class="col-8">
                                    <div class="services-content text-end">
                                        <h3><?php echo frontend_escape((string) ($category['category_name'] ?? '')); ?></h3>
                                        <p><?php echo frontend_escape($categoryDescription); ?></p>
                                        <a href="#" class="btn btn-primary btn-primary-outline-0 rounded-pill py-2 px-4">Make Order</a>
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
                                        <a href="#" class="btn btn-primary btn-primary-outline-0 rounded-pill py-2 px-4">Make Order</a>
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
