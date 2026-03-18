<?php

$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'creaty_db';

$adminAccountsTable = 'admin_accounts';
$customerAccountsTable = 'customer_accounts';

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
	die('Database connection failed: ' . $conn->connect_error);
}

$conn->set_charset('utf8mb4');

$conn->query(
	"CREATE TABLE IF NOT EXISTS {$adminAccountsTable} (
		id INT AUTO_INCREMENT PRIMARY KEY,
		username VARCHAR(50) NOT NULL UNIQUE,
		employee_number VARCHAR(50) DEFAULT NULL,
		password VARCHAR(255) NOT NULL,
		created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
	)"
);

$conn->query(
	"CREATE TABLE IF NOT EXISTS {$customerAccountsTable} (
		id INT AUTO_INCREMENT PRIMARY KEY,
		first_name VARCHAR(100) NOT NULL,
		last_name VARCHAR(100) NOT NULL,
		email VARCHAR(190) NOT NULL UNIQUE,
		password VARCHAR(255) NOT NULL,
		email_verified_at TIMESTAMP NULL DEFAULT NULL,
		privacy_policy_accepted_at TIMESTAMP NULL DEFAULT NULL,
		created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
	)"
);

$legacyUsersTableResult = $conn->query("SHOW TABLES LIKE 'users'");
$legacyCustomersTableResult = $conn->query("SHOW TABLES LIKE 'customers'");

if ($legacyUsersTableResult && $legacyUsersTableResult->num_rows > 0) {
	$conn->query(
		"INSERT INTO {$adminAccountsTable} (id, username, employee_number, password, created_at)
		 SELECT users.id, users.username, users.employee_number, users.password, users.created_at
		 FROM users
		 LEFT JOIN {$adminAccountsTable} ON {$adminAccountsTable}.id = users.id
		 WHERE {$adminAccountsTable}.id IS NULL"
	);
}

if ($legacyCustomersTableResult && $legacyCustomersTableResult->num_rows > 0) {
	$conn->query(
		"INSERT INTO {$customerAccountsTable} (id, first_name, last_name, email, password, email_verified_at, privacy_policy_accepted_at, created_at)
		 SELECT customers.id, customers.first_name, customers.last_name, customers.email, customers.password, NULL, customers.privacy_policy_accepted_at, customers.created_at
		 FROM customers
		 LEFT JOIN {$customerAccountsTable} ON {$customerAccountsTable}.id = customers.id
		 WHERE {$customerAccountsTable}.id IS NULL"
	);
}

$emailVerifiedColumnResult = $conn->query("SHOW COLUMNS FROM {$customerAccountsTable} LIKE 'email_verified_at'");

if ($emailVerifiedColumnResult && $emailVerifiedColumnResult->num_rows === 0) {
	$conn->query("ALTER TABLE {$customerAccountsTable} ADD COLUMN email_verified_at TIMESTAMP NULL DEFAULT NULL AFTER password");
}

$employeeColumnResult = $conn->query("SHOW COLUMNS FROM {$adminAccountsTable} LIKE 'employee_number'");

if ($employeeColumnResult && $employeeColumnResult->num_rows === 0) {
	$conn->query("ALTER TABLE {$adminAccountsTable} ADD COLUMN employee_number VARCHAR(50) DEFAULT NULL AFTER username");
}

$employeeIndexResult = $conn->query("SHOW INDEX FROM {$adminAccountsTable} WHERE Key_name = 'employee_number_unique'");

if ($employeeIndexResult && $employeeIndexResult->num_rows === 0) {
	$conn->query("ALTER TABLE {$adminAccountsTable} ADD UNIQUE KEY employee_number_unique (employee_number)");
}

$adminUsername = 'admin';
$adminPassword = 'admin';

$checkUserStmt = $conn->prepare("SELECT id FROM {$adminAccountsTable} WHERE username = ? LIMIT 1");
$checkUserStmt->bind_param('s', $adminUsername);
$checkUserStmt->execute();
$adminResult = $checkUserStmt->get_result();

if ($adminResult->num_rows === 0) {
	$hashedPassword = password_hash($adminPassword, PASSWORD_DEFAULT);
	$insertUserStmt = $conn->prepare("INSERT INTO {$adminAccountsTable} (username, password) VALUES (?, ?)");
	$insertUserStmt->bind_param('ss', $adminUsername, $hashedPassword);
	$insertUserStmt->execute();
	$insertUserStmt->close();
}

$checkUserStmt->close();

// echo 'Connected successfully!';
