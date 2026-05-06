CREATE DATABASE IF NOT EXISTS ace_admin;
USE ace_admin;

CREATE TABLE IF NOT EXISTS users (
	id INT AUTO_INCREMENT PRIMARY KEY,
	name VARCHAR(100) NOT NULL,
	email VARCHAR(150) NOT NULL UNIQUE,
	password VARCHAR(255) NOT NULL,
	role VARCHAR(50) DEFAULT 'admin',
	status ENUM('active','inactive') DEFAULT 'active',
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS company_settings (
	id INT AUTO_INCREMENT PRIMARY KEY,
	business_name VARCHAR(150) NULL,
	logo VARCHAR(255) NULL,
	phone VARCHAR(50) NULL,
	email VARCHAR(150) NULL,
	website VARCHAR(150) NULL,
	main_address TEXT NULL,
	description TEXT NULL,
	facebook_url VARCHAR(255) NULL,
	instagram_url VARCHAR(255) NULL,
	opening_note TEXT NULL,
	status ENUM('active','inactive') DEFAULT 'active',
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS branches (
	id INT AUTO_INCREMENT PRIMARY KEY,
	branch_name VARCHAR(150) NOT NULL,
	branch_code VARCHAR(50) NULL,
	full_address TEXT NOT NULL,
	area_city VARCHAR(150) NULL,
	phone VARCHAR(50) NULL,
	email VARCHAR(150) NULL,
	google_maps_link VARCHAR(500) NULL,
	opening_time TIME NULL,
	closing_time TIME NULL,
	weekly_off_day VARCHAR(50) NULL,
	branch_manager VARCHAR(150) NULL,
	status ENUM('active','inactive') DEFAULT 'active',
	notes TEXT NULL,
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS service_categories (
	id INT AUTO_INCREMENT PRIMARY KEY,
	category_name VARCHAR(150) NOT NULL,
	category_slug VARCHAR(180) NOT NULL UNIQUE,
	description TEXT NULL,
	display_order INT DEFAULT 0,
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS services (
	id INT AUTO_INCREMENT PRIMARY KEY,
	service_category_id INT NOT NULL,
	outlet_id INT NOT NULL,
	service_name VARCHAR(150) NOT NULL,
	service_slug VARCHAR(180) NOT NULL UNIQUE,
	description TEXT NULL,
	duration_minutes INT NOT NULL,
	price DECIMAL(10,2) NOT NULL,
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	INDEX idx_services_category (service_category_id),
	INDEX idx_services_outlet (outlet_id)
);

CREATE TABLE IF NOT EXISTS employees (
	id INT AUTO_INCREMENT PRIMARY KEY,
	outlet_id INT NOT NULL,
	employee_name VARCHAR(150) NOT NULL,
	phone VARCHAR(50) NULL,
	email VARCHAR(150) NULL,
	job_title VARCHAR(100) NULL,
	specialties TEXT NULL,
	joining_date DATE NULL,
	notes TEXT NULL,
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	INDEX idx_employees_outlet (outlet_id)
);

CREATE TABLE IF NOT EXISTS customers (
	id INT AUTO_INCREMENT PRIMARY KEY,
	customer_name VARCHAR(150) NOT NULL,
	phone VARCHAR(50) NOT NULL,
	email VARCHAR(150) NULL,
	gender VARCHAR(30) NULL,
	date_of_birth DATE NULL,
	address TEXT NULL,
	notes TEXT NULL,
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS bookings (
	id INT AUTO_INCREMENT PRIMARY KEY,
	booking_type ENUM('registered','guest') NOT NULL DEFAULT 'guest',
	customer_id INT NULL,
	guest_name VARCHAR(150) NULL,
	guest_phone VARCHAR(50) NULL,
	guest_email VARCHAR(150) NULL,
	outlet_id INT NOT NULL,
	service_id INT NOT NULL,
	employee_id INT NULL,
	appointment_date DATE NOT NULL,
	appointment_time TIME NOT NULL,
	booking_status ENUM('pending','confirmed','completed','cancelled') DEFAULT 'pending',
	payment_method VARCHAR(50) DEFAULT 'pay_at_salon',
	notes TEXT NULL,
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	INDEX idx_bookings_customer (customer_id),
	INDEX idx_bookings_outlet (outlet_id),
	INDEX idx_bookings_service (service_id),
	INDEX idx_bookings_employee (employee_id),
	INDEX idx_bookings_appointment_date (appointment_date)
);

CREATE TABLE IF NOT EXISTS payments (
	id INT AUTO_INCREMENT PRIMARY KEY,
	booking_id INT NOT NULL,
	invoice_number VARCHAR(100) NOT NULL UNIQUE,
	total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
	discount_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
	paid_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
	due_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
	payment_status ENUM('unpaid','partial','paid') DEFAULT 'unpaid',
	payment_method VARCHAR(50) DEFAULT 'pay_at_salon',
	payment_date DATE NULL,
	notes TEXT NULL,
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	INDEX idx_payments_booking (booking_id)
);

CREATE TABLE IF NOT EXISTS inventory_items (
	id INT AUTO_INCREMENT PRIMARY KEY,
	outlet_id INT NOT NULL,
	item_name VARCHAR(150) NOT NULL,
	item_category VARCHAR(100) NULL,
	quantity INT NOT NULL DEFAULT 0,
	unit VARCHAR(50) NULL,
	item_condition VARCHAR(100) NULL,
	purchase_date DATE NULL,
	notes TEXT NULL,
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	INDEX idx_inventory_items_outlet (outlet_id),
	INDEX idx_inventory_items_name (item_name)
);
