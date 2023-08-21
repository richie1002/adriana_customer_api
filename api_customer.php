<?php
// Define an interface that outlines the required database methods
interface DatabaseInterface {
    public function listCustomers(); // Retrieve a list of customers
    public function saveCustomer($customerData); // Save a new customer
    public function updateCustomer($customerId, $customerData); // Update an existing customer
}

// Implementation of the DatabaseInterface using in-memory storage
class SimpleDatabase implements DatabaseInterface {
    private $data = []; // Data storage for customers

    // Retrieve a list of customers
    public function listCustomers() {
        return $this->data;
    }

    // Save a new customer
    public function saveCustomer($customerData) {
        if ($this->validateCustomerData($customerData)) {
            $this->data[] = $customerData;
            return true;
        }
        return false;
    }

    // Update an existing customer
    public function updateCustomer($customerId, $customerData) {
        if (isset($this->data[$customerId]) && $this->validateCustomerData($customerData)) {
            $this->data[$customerId] = $customerData;
            return true;
        }
        return false;
    }

    // Validate customer data
    private function validateCustomerData($data) {
        return isset($data['name'], $data['address']);
    }
}

// Utility class for handling API responses
class ApiResponse {
    // Send a successful response
    public static function success($data = null, $statusCode = 200) {
        // Set the HTTP status code and response header
        http_response_code($statusCode);
        header('Content-Type: application/json');
        // Encode the data as JSON and send it as the response
        echo json_encode($data);
    }

    // Send an error response
    public static function error($message, $statusCode = 400) {
        // Set the HTTP status code and response header
        http_response_code($statusCode);
        header('Content-Type: application/json');
        // Encode the error message as JSON and send it as the response
        echo json_encode(['error' => $message]);
    }
}

// Class for handling incoming API requests
class ApiHandler {
    private $databaseManager;

    // Initialize with a DatabaseInterface implementation
    public function __construct(DatabaseInterface $database) {
        $this->databaseManager = $database;
    }

    // Handle the incoming API request based on its HTTP method
    public function handleRequest() {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $this->handleGetRequest();
        } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handlePostRequest();
        } elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
            $this->handlePutRequest();
        } else {
            // Handle unsupported HTTP methods
            ApiResponse::error("Method not allowed", 405);
        }
    }

    // Handle GET to list customers
    private function handleGetRequest() {
        $customers = $this->databaseManager->listCustomers();
        ApiResponse::success($customers);
    }

    // Handle POST to save a new customer
    private function handlePostRequest() {
        $postData = json_decode(file_get_contents('php://input'), true);
        if ($this->databaseManager->saveCustomer($postData)) {
            ApiResponse::success(null, 201);
        } else {
            ApiResponse::error("Invalid customer data", 400);
        }
    }

    // Handle PUT to update an existing customer
    private function handlePutRequest() {
        parse_str(file_get_contents('php://input'), $putData);
        $customerId = $putData['id'];
        unset($putData['id']);
        if ($this->databaseManager->updateCustomer($customerId, $putData)) {
            ApiResponse::success();
        } else {
            ApiResponse::error("Invalid customer data or customer ID not found", 400);
        }
    }
}


$database = new SimpleDatabase();
$apiHandler = new ApiHandler($database);

// Handle the incoming request based on its HTTP method
$apiHandler->handleRequest();

?>