<?php
/**
 * api_conn.php
 * Lightweight DB connection for public-facing APIs.
 * Does NOT start sessions or check admin login — safe for mobile API calls.
 */

$dbhost = "145.223.33.118";
$dbuser = "qoon_Qoon";
$dbpass = ";)xo6b(RE}K%";
$dbname = "qoon_Qoon";

// Disable mysqli exception mode so connect errors are handled via connect_error
mysqli_report(MYSQLI_REPORT_OFF);

$con = new mysqli($dbhost, $dbuser, $dbpass, $dbname);

if ($con->connect_error) {
    http_response_code(503);
    echo json_encode(['success' => false, 'error' => 'DB connection failed: ' . $con->connect_error]);
    exit;
}

$con->set_charset("utf8mb4");

$DomainNamee = "https://qoon.app/dash/";
