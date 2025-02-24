<?php namespace App\Controllers;

use App\Classes\Helpers;

class TrackerController
{
    /*
     * Get client data by api token
     */
    private function getClientByApiToken($apiToken): ?array
    {
        $result = null;

        $db = Helpers::getDbConnection();
        $query = $db->prepare("SELECT id from clients WHERE api_token = ?");
        $query->bind_param("s", $apiToken);
        $query->execute();
        $query = $query->get_result();
        if ($query->num_rows > 0) {
            $result = $query->fetch_assoc();
        }

        $query->close();
        $db->close();

        return $result;
    }

    /*
     * Get all clients from database
     */
    private function getClients(): ?array
    {
        $result = null;

        $db = Helpers::getDbConnection();
        $query = $db->prepare("SELECT id, name from clients");
        $query->execute();
        $query = $query->get_result();
        if ($query->num_rows > 0) {
            $result = $query->fetch_all(MYSQLI_ASSOC);
        }

        $query->close();
        $db->close();

        return $result;
    }

    /*
     * Insert tracked data in database
     */
    private function addTracking($data): bool
    {
        $success = True;
        $db = Helpers::getDbConnection();
        $query = $db->prepare("SELECT * from trackings WHERE tracked_id = ? AND url = ?");
        $query->bind_param('ss', $data['trackedId'], $data['pageUrl']);
        $query->execute();
        $query = $query->get_result();

        if ($query->num_rows === 0) {
            $query = $db->prepare("INSERT INTO trackings (client_id, url, tracked_id, created_at) values (?,?,?,?)");
            $query->bind_param("isss",$data['clientId'], $data['pageUrl'], $data['trackedId'], $data['timestamp']);
            if (!$query->execute()) {
                error_log("Database error: ", $query->error);
                $success = False;
            }
        }
        $query->close();
        $db->close();

        return $success;
    }

    private function getTrackings($filters = null)
    {
        $result = ['list' => [], 'count' => 0];

        $db = Helpers::getDbConnection();
        $stmt = "SELECT trackings.*, clients.name AS client_name 
                from trackings 
                LEFT JOIN clients ON trackings.client_id = clients.id";
        $bindTypes = "";
        $bindings = [];

        if (!empty($filters)) {
            $stmt .= " WHERE";
            foreach ($filters as $filter) {
                $stmt .= " " . $filter['query'] . " AND";
                $bindings[] = $filter['value'];
                $bindTypes .= $filter['type'];
            }
            $stmt = substr($stmt, 0, -4);
        }

        $query = $db->prepare($stmt);
        if (!empty($bindTypes)) {
            $query->bind_param($bindTypes,...$bindings);
        }
        $query->execute();
        $query = $query->get_result();
        if ($query->num_rows > 0) {
            $result['list'] = $query->fetch_all(MYSQLI_ASSOC);
            $result['count'] = $query->num_rows;
        }

        $query->close();
        $db->close();

        return $result;
    }

    /*
     * Function that validates tracking data and queues the worker
     */
    public function track($data): string
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return json_encode(['error' => 'Invalid request']);
        }

        $apiToken = $_SERVER['HTTP_X_API_TOKEN'] ?? null;
        if (empty($apiToken)) {
            http_response_code(403);
            return json_encode(['error' => 'The API Authentication failed.']);
        }

        $client = $this->getClientByApiToken($apiToken);
        if (empty(($client = $this->getClientByApiToken($apiToken)))) {
            http_response_code(403);
            return json_encode(['error' => 'The API Authentication failed.']);
        }

        if (empty($data)) {
            http_response_code(400);
            return json_encode(['error' => 'Invalid payload']);
        }

        if (empty($data['pageUrl'])) {
            http_response_code(400);
            return json_encode(['error' => 'The pageUrl field is invalid.']);
        }

        $data['pageUrl'] = filter_var($data['pageUrl'], FILTER_SANITIZE_URL);
        if (!filter_var($data['pageUrl'], FILTER_VALIDATE_URL)) {
            http_response_code(400);
            return json_encode(['error' => 'The pageUrl field is invalid.']);
        }

        $data['trackedId'] = filter_var($data['trackedId'], FILTER_SANITIZE_STRING);
        if (empty($data['trackedId'])) {
            http_response_code(400);
            return json_encode(['error' => 'The trackedId field is invalid.']);
        }

        $data['timestamp'] = filter_var($data['timestamp'], FILTER_SANITIZE_STRING);
        if (empty($data['timestamp']) || !preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{3}Z$/', $data['timestamp'])) {
            http_response_code(400);
            return json_encode(['error' => 'The timestamp field is invalid.']);
        }

        $data['clientId'] = $client['id'];

        if (!$this->addTracking($data)) {
            http_response_code(500);
            return json_encode(['error' => 'API error']);
        }

        return json_encode(['success' => true]);
    }

    /*
     * Function to fetch trackings from the database with optional filters
     */
    public function trackings(): array
    {
        $filters = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $emptyResult = ['list' => null, 'count' => 0];
            if (empty($_POST['csrf_token']) || $_SESSION['csrf_token'] !== $_POST['csrf_token']) {
                return $emptyResult;
            }

            $startDate = $_POST['start_date'] ?? null;
            $endDate = $_POST['end_date'] ?? null;
            $client = $_POST['client'] ?? null;
            $_SESSION['filters'] = [];

            $timestampRegex = '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/';

            if (!empty($startDate)) {
                $startDate = filter_var($startDate, FILTER_SANITIZE_STRING);

                if (!preg_match($timestampRegex, $startDate)) {
                    http_response_code(400);
                    return ['field' => 'start_date', 'error' => 'The start date field is invalid.'];
                }

                $filters[] = ['value' => $startDate, 'type' => 's', 'query' => 'trackings.created_at >= ?'];
                $_SESSION['filters']['startDate'] = $startDate;
            }

            if (!empty($endDate)) {
                $endDate = filter_var($endDate, FILTER_SANITIZE_STRING);

                if (!preg_match($timestampRegex, $endDate)) {
                    http_response_code(400);
                    return ['field' => 'end_date', 'error' => 'The end date field is invalid.'];
                }
                $filters[] = ['value' => $endDate, 'type' => 's', 'query' => 'trackings.created_at <= ?'];
                $_SESSION['filters']['endDate'] = $endDate;
            }

            if (!empty($client)) {
                $client = filter_var($client, FILTER_SANITIZE_NUMBER_INT);

                if (!filter_var($client, FILTER_VALIDATE_INT)) {
                    http_response_code(400);
                    return ['field' => 'client', 'error' => 'The client field is invalid.'];
                }
                $filters[] = ['value' => intval($client), 'type' => 'i', 'query' => 'clients.id = ?'];
                $_SESSION['filters']['client'] = $client;
            }
        } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $this->reset();
        }

        return $this->getTrackings($filters);
    }

    public function filters(): array
    {
        $filters['clients'] = $this->getClients();

        return $filters;
    }

    public function reset()
    {
        session_unset();
        session_destroy();
        session_start();
        Helpers::initCsrfToken();
    }
}