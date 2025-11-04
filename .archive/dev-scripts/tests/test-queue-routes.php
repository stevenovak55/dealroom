<?php
/**
 * Check if Queue routes are registered
 */

require_once '/var/www/html/wp-load.php';

echo "Checking Queue Routes Registration\n\n";

$server = rest_get_server();
$namespaces = $server->get_namespaces();

echo "Available namespaces:\n";
foreach ($namespaces as $ns) {
	if (strpos($ns, 'deal') !== false) {
		echo "  - $ns\n";
	}
}

echo "\nQueue routes in ma-deal-room/v1:\n";

$routes = $server->get_routes('ma-deal-room/v1');
foreach ($routes as $route => $data) {
	if (strpos($route, 'queue') !== false) {
		echo "  ✓ $route\n";
	}
}

echo "\nDone\n";
