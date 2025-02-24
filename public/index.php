<?php

require_once __DIR__ . '/../src/functions.php';
require_once __DIR__ . '/../app/Controllers/TrackerController.php';
require_once __DIR__ . '/../app/Classes/Helpers.php';

session_start();

\App\Classes\Helpers::initCsrfToken();

// Default index page
router(['GET', 'POST'], '^/$', function() {
    $controller = new \App\Controllers\TrackerController();
    $action = $_GET['action'] ?? null;
    $count = 0;

    if ($action === 'reset') {
        $controller->reset();
    }
    $trackings = $controller->trackings();
    if (empty($trackings['error'])) {
        $count = $trackings['count'];
        $trackings = $trackings['list'];
    }

    $filters = $controller->filters();

    include ('trackings.html');
});

// API route to store data from tracker
router('POST', '^/api/track$', function() {
    header('Content-Type: application/json');
    $json = json_decode(file_get_contents('php://input'), true);

    echo (new \App\Controllers\TrackerController())->track($json);
});

// User Alpha pages
router('GET', '^/alpha/alpha-page-1$', function() {
    echo '
    You arrived at Alpha\'s page 1
    <script src="http://localhost:8080/tracker.js" data-token="69MhtatboUY5ODDcSOgFYZ4tXxym9cQYZAAXZquAEjEFGa6kmVoxYJEjEjsjUlTE"></script>
    ';
});
router('GET', '^/alpha/alpha-page-2$', function() {
    echo '
    You arrived at Alpha\'s page 2
    <script src="http://localhost:8080/tracker.js" data-token="69MhtatboUY5ODDcSOgFYZ4tXxym9cQYZAAXZquAEjEFGa6kmVoxYJEjEjsjUlTE"></script>
    ';
});

// User Beta pages
router('GET', '^/beta/beta-page-1$', function() {
    echo '
    You arrived at Beta\'s page 1
    <script src="http://localhost:8080/tracker.js" data-token="ibQjSlq46rzADIOFtz3zsRTO3qt62CveVAy2mdT0ouUYlbWzfoYkomaa1L4F9cCw"></script>
    ';
});
router('GET', '^/beta/beta-page-2$', function() {
    echo '
    You arrived at Beta\'s page 2
    <script src="http://localhost:8080/tracker.js" data-token="ibQjSlq46rzADIOFtz3zsRTO3qt62CveVAy2mdT0ouUYlbWzfoYkomaa1L4F9cCw"></script>
    ';
});

// Debug enabled
router('GET', '^/debug-enabled', function() {
    echo '
    You arrived at a error page with debug enabled
    <script src="http://localhost:8080/tracker.js" data-debug="1" data-token="wrong-api"></script>
    ';
});

// Debug disabled
router('GET', '^/debug-disabled', function() {
    echo '
    You arrived at a error page with debug disabled
    <script src="http://localhost:8080/tracker.js" data-token="wrong-api"></script>
    ';
});

router('GET', '^/beta/beta-page-cookie$', function() {
    echo '
    You arrived at Beta\'s page with a different cookie name
    <script src="http://localhost:8080/tracker.js" data-token="ibQjSlq46rzADIOFtz3zsRTO3qt62CveVAy2mdT0ouUYlbWzfoYkomaa1L4F9cCw" data-cookie="visitorId"></script>
    ';
});

header("HTTP/1.0 404 Not Found");
echo '404 Not Found';