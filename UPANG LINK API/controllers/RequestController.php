<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Request.php';

class RequestController {
    private $db;
    private $request;

    public function __construct() {
        try {
            error_log("Initializing RequestController");
            $database = new Database();
            $this->db = $database->getConnection();
            $this->request = new Request($this->db);
            error_log("RequestController initialized successfully");
        } catch (Exception $e) {
            error_log("Error initializing RequestController: " . $e->getMessage());
            throw $e;
        }
    }

    public function handleRequest($method, $endpoint = '') {
        error_log("Handling request: Method=$method, Endpoint=" . (is_array($endpoint) ? implode('/', $endpoint) : $endpoint));
        
        try {
            // Convert endpoint to string if it's an array
            $endpointStr = is_array($endpoint) ? end($endpoint) : $endpoint;
            error_log("Normalized endpoint: " . $endpointStr);

            // Check if the endpoint is a tracking number
            if (preg_match('/^REQ-\d{8}-\d{4}$/', $endpointStr) || preg_match('/^REQ-\d{4}-\d{3}$/', $endpointStr)) {
                error_log("Endpoint is a tracking number: " . $endpointStr);
                if ($method === 'GET') {
                    error_log("Getting request by tracking number: " . $endpointStr);
                    $result = $this->getRequestByTrackingNumber($endpointStr);
                    echo json_encode($result);
                    return;
                }
            }

            switch ($method) {
                case 'GET':
                    switch ($endpointStr) {
                        case 'types':
                            error_log("Getting request types");
                            $result = $this->getRequestTypes();
                            echo json_encode($result);
                            return;
                        case 'requirements':
                            error_log("Getting type requirements");
                            $type_id = isset($endpoint[2]) ? $endpoint[2] : null;
                            $result = $this->getTypeRequirements($type_id);
                            echo json_encode($result);
                            return;
                        case 'statistics':
                            error_log("Getting request statistics");
                            $result = $this->getRequestStatistics();
                            echo json_encode($result);
                            return;
                        default:
                            if (isset($_GET['id'])) {
                                error_log("Getting request by ID: " . $_GET['id']);
                                $result = $this->getRequest($_GET['id']);
                                echo json_encode($result);
                                return;
                            }
                            error_log("Getting all requests");
                            $result = $this->getAllRequests();
                            echo json_encode($result);
                            return;
                    }

                case 'POST':
                    error_log("Creating new request");
                    return $this->createRequest();

                case 'PUT':
                    if (!isset($_GET['id'])) {
                        error_log("PUT request missing ID");
                        http_response_code(400);
                        return ['status' => 'error', 'message' => 'Request ID is required'];
                    }
                    error_log("Updating request: " . $_GET['id']);
                    return $this->updateRequest($_GET['id']);

                case 'DELETE':
                    if (!isset($_GET['id'])) {
                        error_log("DELETE request missing ID");
                        http_response_code(400);
                        return ['status' => 'error', 'message' => 'Request ID is required'];
                    }
                    error_log("Deleting request: " . $_GET['id']);
                    return $this->deleteRequest($_GET['id']);

                default:
                    error_log("Method not allowed: $method");
                    http_response_code(405);
                    return ['status' => 'error', 'message' => 'Method not allowed'];
            }
        } catch (Exception $e) {
            error_log("Error handling request: " . $e->getMessage());
            http_response_code(500);
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    private function getAllRequests() {
        try {
            // Extract user ID from JWT token
            $user_id = $this->getUserIdFromToken();
            if (!$user_id) {
                http_response_code(401);
                return ['status' => 'error', 'message' => 'Unauthorized: Invalid token'];
            }
            
            error_log("Using user ID from token: " . $user_id);
            
            $result = $this->request->getAll($user_id);
            return ['status' => 'success', 'data' => $result];
        } catch (Exception $e) {
            http_response_code(500);
            return ['status' => 'error', 'message' => 'Failed to fetch requests'];
        }
    }

    private function getRequest($id) {
        try {
            // Extract user ID from JWT token
            $user_id = $this->getUserIdFromToken();
            if (!$user_id) {
                http_response_code(401);
                return ['status' => 'error', 'message' => 'Unauthorized: Invalid token'];
            }
            
            error_log("Using user ID from token: " . $user_id);
            
            // Try to get request by tracking number first
            if (preg_match('/^REQ-\d{8}-\d{4}$/', $id) || preg_match('/^REQ-\d{4}-\d{3}$/', $id)) {
                error_log("Getting request by tracking number: " . $id);
                $result = $this->request->getDetailsByTrackingNumber($id, $user_id);
            } else {
                error_log("Getting request by ID: " . $id);
                $result = $this->request->getById($id);
            }
            
            if (!$result) {
                http_response_code(404);
                return ['status' => 'error', 'message' => 'Request not found'];
            }
            return ['status' => 'success', 'data' => $result];
        } catch (Exception $e) {
            http_response_code(500);
            return ['status' => 'error', 'message' => 'Failed to fetch request: ' . $e->getMessage()];
        }
    }

    private function getUniformRequirements($type_name) {
        // Base structure for all uniform types
        $requirements = [
            'fields' => [
                [
                    'name' => 'uniform_size',
                    'label' => 'Uniform Size',
                    'type' => 'select',
                    'required' => true,
                    'description' => 'Select your uniform size',
                    'options' => ['XS', 'S', 'M', 'L', 'XL', 'XXL']
                ]
            ],
            'instructions' => 'Please select your uniform size. Student information will be pulled from your profile.'
        ];

        // Add type-specific variations to force UI refresh
        if (strpos($type_name, 'PE') !== false) {
            $requirements['fields'][0]['label'] = 'PE Uniform Size';
            $requirements['instructions'] = 'Please select your PE Uniform size. Your student information will be automatically included.';
        } else {
            $requirements['fields'][0]['label'] = 'School Uniform Size';
            $requirements['instructions'] = 'Please select your School Uniform size. Your student information will be automatically included.';
        }

        return $requirements;
    }

    private function getRequestTypes() {
        try {
            $types = $this->request->getTypes();
            if (empty($types)) {
                error_log("No request types found");
                http_response_code(404);
                return [
                    'status' => 'error',
                    'message' => 'No request types found',
                    'code' => 404
                ];
            }

            // Process each type to ensure requirements are properly formatted
            foreach ($types as &$type) {
                error_log("Processing type: " . $type['name'] . ", Requirements: " . print_r($type['requirements'], true));
                
                // For uniform requests, always use the default structure
                if (strpos($type['name'], 'Uniform') !== false) {
                    $requirements = $this->getUniformRequirements($type['name']);
                    // Add unique identifier for each uniform type
                    $requirements['key'] = $type['name'] . '_' . $type['type_id'] . '_' . time();
                    $requirements['force_refresh'] = true;
                } 
                // For special types that don't need additional fields, return empty requirements
                else if (stripos($type['name'], 'Course Module') !== false || 
                        stripos($type['name'], 'Enrollment Certificate') !== false) {
                    $requirements = [
                        'fields' => [],
                        'instructions' => 'No additional information needed for this request.',
                        'key' => $type['name'] . '_' . $type['type_id'] . '_' . time(),
                        'force_refresh' => true
                    ];
                } 
                // For ID Replacement, return specific requirements
                else if (stripos($type['name'], 'ID Replacement') !== false) {
                    $requirements = [
                        'fields' => [
                            [
                                'name' => 'affidavit_of_loss',
                                'label' => 'Affidavit of Loss',
                                'type' => 'file',
                                'required' => true,
                                'description' => 'Upload a scanned copy of your Affidavit of Loss',
                                'allowed_types' => 'pdf,jpg,jpeg,png'
                            ],
                            [
                                'name' => 'payment_receipt',
                                'label' => 'Payment Receipt',
                                'type' => 'file',
                                'required' => true,
                                'description' => 'Upload a scanned copy of your Payment Receipt',
                                'allowed_types' => 'pdf,jpg,jpeg,png'
                            ]
                        ],
                        'instructions' => 'Please upload the required documents for ID Replacement.',
                        'key' => $type['name'] . '_' . $type['type_id'] . '_' . time(),
                        'force_refresh' => true
                    ];
                }
                // For New Student ID, return specific requirements
                else if (stripos($type['name'], 'New Student ID') !== false) {
                    $requirements = [
                        'fields' => [
                            [
                                'name' => 'id_photo',
                                'label' => '1x1 ID Photo',
                                'type' => 'file',
                                'required' => true,
                                'description' => 'Upload a 1x1 ID photo with white background',
                                'allowed_types' => 'jpg,jpeg,png'
                            ],
                            [
                                'name' => 'signature',
                                'label' => 'Signature',
                                'type' => 'file',
                                'required' => true,
                                'description' => 'Upload a clear image of your signature on white paper',
                                'allowed_types' => 'jpg,jpeg,png,pdf'
                            ]
                        ],
                        'instructions' => 'Please upload your 1x1 ID photo and signature for your new student ID.',
                        'key' => $type['name'] . '_' . $type['type_id'] . '_' . time(),
                        'force_refresh' => true
                    ];
                }
                // For Transcript of Records, return specific requirements
                else if (stripos($type['name'], 'Transcript of Records') !== false) {
                    $requirements = [
                        'fields' => [
                            [
                                'name' => 'request_form',
                                'label' => 'Request Form',
                                'type' => 'file',
                                'required' => true,
                                'description' => 'Upload a scanned copy of the completed Request Form',
                                'allowed_types' => 'pdf,jpg,jpeg,png'
                            ],
                            [
                                'name' => 'clearance',
                                'label' => 'Clearance',
                                'type' => 'file',
                                'required' => true,
                                'description' => 'Upload a scanned copy of your Clearance',
                                'allowed_types' => 'pdf,jpg,jpeg,png'
                            ]
                        ],
                        'instructions' => 'Please upload the required documents for Transcript of Records. Your student information will be automatically included.',
                        'key' => $type['name'] . '_' . $type['type_id'] . '_' . time(),
                        'force_refresh' => true
                    ];
                }
                else {
                    // Format the requirements for other request types
                    $requirements = $this->formatRequirements($type['requirements']);
                    error_log("Formatted requirements: " . print_r($requirements, true));
                }

                // Add unique identifier and timestamp
                $uniqueKey = $type['name'] . '_' . $type['type_id'] . '_' . time();
                $requirements['key'] = $uniqueKey;
                $type['requirements'] = $requirements;
                $type['key'] = $uniqueKey;
            }

            error_log("Successfully fetched " . count($types) . " request types");
            return [
                'status' => 'success',
                'data' => array_values($types),
                'code' => 200,
                'timestamp' => time()
            ];
        } catch(PDOException $e) {
            error_log("Database error in getRequestTypes: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            http_response_code(500);
            return [
                'status' => 'error',
                'message' => 'Failed to fetch request types: ' . $e->getMessage(),
                'code' => 500
            ];
        } catch(Exception $e) {
            error_log("General error in getRequestTypes: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            http_response_code(500);
            return [
                'status' => 'error',
                'message' => 'An unexpected error occurred: ' . $e->getMessage(),
                'code' => 500
            ];
        }
    }

    private function getRequestStatistics() {
        try {
            $user_id = $_SESSION['user_id'] ?? null;
            if (!$user_id) {
                http_response_code(401);
                return ['status' => 'error', 'message' => 'Unauthorized'];
            }
            
            $result = $this->request->getStatistics($user_id);
            return ['status' => 'success', 'data' => $result];
        } catch (Exception $e) {
            http_response_code(500);
            return ['status' => 'error', 'message' => 'Failed to fetch request statistics'];
        }
    }

    private function createRequest() {
        try {
            $user_id = $_SESSION['user_id'] ?? null;
            if (!$user_id) {
                http_response_code(401);
                return ['status' => 'error', 'message' => 'Unauthorized'];
            }

            $data = json_decode(file_get_contents("php://input"));
            $data->user_id = $user_id; // Set user_id from session

            if (!isset($data->type_id) || !isset($data->description)) {
                http_response_code(400);
                return ['status' => 'error', 'message' => 'Missing required fields'];
            }

            $result = $this->request->create($data);
            if (!$result) {
                http_response_code(500);
                return ['status' => 'error', 'message' => 'Failed to create request'];
            }

            http_response_code(201);
            return ['status' => 'success', 'data' => $result];
        } catch (Exception $e) {
            http_response_code(500);
            return ['status' => 'error', 'message' => 'Failed to create request'];
        }
    }

    private function updateRequest($id) {
        try {
        $data = json_decode(file_get_contents("php://input"));
        
            if (!$this->request->getById($id)) {
                http_response_code(404);
                return ['status' => 'error', 'message' => 'Request not found'];
            }

            if ($this->request->update($id, $data)) {
                return ['status' => 'success', 'message' => 'Request updated successfully'];
            }

            http_response_code(500);
            return ['status' => 'error', 'message' => 'Failed to update request'];
        } catch (Exception $e) {
            http_response_code(500);
            return ['status' => 'error', 'message' => 'Failed to update request'];
        }
    }

    private function deleteRequest($id) {
        try {
            if (!$this->request->getById($id)) {
                http_response_code(404);
                return ['status' => 'error', 'message' => 'Request not found'];
            }

            if ($this->request->delete($id)) {
                return ['status' => 'success', 'message' => 'Request deleted successfully'];
            }

            http_response_code(500);
            return ['status' => 'error', 'message' => 'Failed to delete request'];
        } catch (Exception $e) {
            http_response_code(500);
            return ['status' => 'error', 'message' => 'Failed to delete request'];
        }
    }

    private function formatRequirements($requirements) {
        try {
            error_log("Formatting requirements: " . print_r($requirements, true));
            
            // Default structure
            $defaultStructure = [
                'fields' => [],
                'instructions' => 'Please fill out all required fields.'
            ];

            // If requirements is empty, return default structure
            if (empty($requirements)) {
                error_log("Empty requirements, returning default structure");
                return $defaultStructure;
            }

            // If requirements is a string, try to decode it
            if (is_string($requirements)) {
                error_log("Requirements is a string, attempting to decode: " . $requirements);
                $decoded = json_decode($requirements, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    error_log("Successfully decoded JSON: " . print_r($decoded, true));
                    
                    // Handle case where requirements has required_docs array
                    if (isset($decoded['required_docs']) && is_array($decoded['required_docs'])) {
                        return [
                            'fields' => array_map(function($doc) {
                                $fieldName = strtolower(str_replace(' ', '_', $doc));
                                return [
                                    'name' => $fieldName,
                                    'label' => $doc,
                                    'type' => 'file',
                                    'required' => true,
                                    'description' => 'Please upload ' . $doc,
                                    'allowed_types' => 'pdf,jpg,jpeg,png'
                                ];
                            }, $decoded['required_docs']),
                            'instructions' => 'Please upload all required documents.'
                        ];
                    }
                    
                    // If the decoded result is already in the correct format
                    if (isset($decoded['fields'])) {
                        // Ensure all fields have the required properties
                        $decoded['fields'] = array_map(function($field) {
                            if (!isset($field['name'])) {
                                $field['name'] = strtolower(str_replace(' ', '_', $field['label'] ?? 'field'));
                            }
                            if (!isset($field['label'])) {
                                $field['label'] = ucwords(str_replace('_', ' ', $field['name']));
                            }
                            if (!isset($field['type'])) {
                                $field['type'] = 'text';
                            }
                            if (!isset($field['required'])) {
                                $field['required'] = true;
                            }
                            if (!isset($field['description'])) {
                                $field['description'] = 'Please ' . ($field['type'] === 'file' ? 'upload' : 'enter') . ' ' . strtolower($field['label']);
                            }
                            if ($field['type'] === 'file' && !isset($field['allowed_types'])) {
                                $field['allowed_types'] = 'pdf,jpg,jpeg,png';
                            }
                            return $field;
                        }, $decoded['fields']);
                        
                        if (!isset($decoded['instructions'])) {
                            $decoded['instructions'] = 'Please fill out all required fields.';
                        }
                        
                        return $decoded;
                    }
                    
                    // If it's a simple array, convert to fields
                    if (is_array($decoded)) {
                        return [
                            'fields' => array_map(function($field) {
                                if (is_string($field)) {
                                    return [
                                        'name' => strtolower(str_replace(' ', '_', $field)),
                                        'label' => $field,
                                        'type' => 'text',
                                        'required' => true,
                                        'description' => 'Please enter ' . strtolower($field)
                                    ];
                                }
                                return $field;
                            }, $decoded),
                            'instructions' => 'Please fill out all required fields.'
                        ];
                    }
                } else {
                    error_log("JSON decode failed: " . json_last_error_msg());
                    // Try to parse as comma-separated list
                    $fields = array_filter(array_map('trim', explode(',', $requirements)));
                    if (!empty($fields)) {
                        return [
                            'fields' => array_map(function($field) {
                                return [
                                    'name' => strtolower(str_replace(' ', '_', $field)),
                                    'label' => $field,
                                    'type' => 'text',
                                    'required' => true,
                                    'description' => 'Please enter ' . strtolower($field)
                                ];
                            }, $fields),
                            'instructions' => 'Please fill out all required fields.'
                        ];
                    }
                }
            }

            // If requirements is already an array
            if (is_array($requirements)) {
                error_log("Requirements is an array");
                // If it's already in the correct format
                if (isset($requirements['fields'])) {
                    error_log("Requirements already has fields key");
                    return $requirements;
                }
                
                // If it's a simple array of fields
                if (array_values($requirements) === $requirements) {
                    error_log("Requirements is a simple array");
                    return [
                        'fields' => array_map(function($field) {
                            if (is_string($field)) {
                                return [
                                    'name' => strtolower(str_replace(' ', '_', $field)),
                                    'label' => $field,
                                    'type' => 'text',
                                    'required' => true,
                                    'description' => 'Please enter ' . strtolower($field)
                                ];
                            }
                            return $field;
                        }, $requirements),
                        'instructions' => 'Please fill out all required fields.'
                    ];
                }
            }

            error_log("Could not format requirements, returning default structure");
            return $defaultStructure;
        } catch (Exception $e) {
            error_log("Error formatting requirements: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return [
                'fields' => [],
                'instructions' => 'Please fill out all required fields.'
            ];
        }
    }

    private function getTypeRequirements($type_id) {
        try {
            if (!$type_id) {
                http_response_code(400);
                return [
                    'status' => 'error',
                    'message' => 'Type ID is required',
                    'code' => 400
                ];
            }

            $query = "SELECT type_id, name, description, requirements, processing_time FROM request_types WHERE type_id = ? AND is_active = 1";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$type_id]);
            $type = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$type) {
                http_response_code(404);
                return [
                    'status' => 'error',
                    'message' => 'Request type not found',
                    'code' => 404
                ];
            }

            // For uniform requests, always use the default structure
            if (strpos($type['name'], 'Uniform') !== false) {
                $requirements = $this->getUniformRequirements($type['name']);
                // Add unique identifier for each uniform type
                $requirements['key'] = $type['name'] . '_' . $type_id . '_' . time();
                $requirements['force_refresh'] = true;
            } 
            // For special types that don't need additional fields, return empty requirements
            else if (stripos($type['name'], 'Course Module') !== false || 
                    stripos($type['name'], 'Enrollment Certificate') !== false ||
                    stripos($type['name'], 'ID Replacement') !== false) {
                $requirements = [
                    'fields' => [],
                    'instructions' => 'No additional information needed for this request.',
                    'key' => $type['name'] . '_' . $type_id . '_' . time(),
                    'force_refresh' => true
                ];
            }
            // For ID Replacement, return specific requirements
            else if (stripos($type['name'], 'ID Replacement') !== false) {
                $requirements = [
                    'fields' => [
                        [
                            'name' => 'affidavit_of_loss',
                            'label' => 'Affidavit of Loss',
                            'type' => 'file',
                            'required' => true,
                            'description' => 'Upload a scanned copy of your Affidavit of Loss',
                            'allowed_types' => 'pdf,jpg,jpeg,png'
                        ],
                        [
                            'name' => 'payment_receipt',
                            'label' => 'Payment Receipt',
                            'type' => 'file',
                            'required' => true,
                            'description' => 'Upload a scanned copy of your Payment Receipt',
                            'allowed_types' => 'pdf,jpg,jpeg,png'
                        ]
                    ],
                    'instructions' => 'Please upload the required documents for ID Replacement.',
                    'key' => $type['name'] . '_' . $type_id . '_' . time(),
                    'force_refresh' => true
                ];
            }
            // For New Student ID, return specific requirements
            else if (stripos($type['name'], 'New Student ID') !== false) {
                $requirements = [
                    'fields' => [
                        [
                            'name' => 'id_photo',
                            'label' => '1x1 ID Photo',
                            'type' => 'file',
                            'required' => true,
                            'description' => 'Upload a 1x1 ID photo with white background',
                            'allowed_types' => 'jpg,jpeg,png'
                        ],
                        [
                            'name' => 'signature',
                            'label' => 'Signature',
                            'type' => 'file',
                            'required' => true,
                            'description' => 'Upload a clear image of your signature on white paper',
                            'allowed_types' => 'jpg,jpeg,png,pdf'
                        ]
                    ],
                    'instructions' => 'Please upload your 1x1 ID photo and signature for your new student ID.',
                    'key' => $type['name'] . '_' . $type_id . '_' . time(),
                    'force_refresh' => true
                ];
            }
            // For Transcript of Records, return specific requirements
            else if (stripos($type['name'], 'Transcript of Records') !== false) {
                $requirements = [
                    'fields' => [
                        [
                            'name' => 'request_form',
                            'label' => 'Request Form',
                            'type' => 'file',
                            'required' => true,
                            'description' => 'Upload a scanned copy of the completed Request Form',
                            'allowed_types' => 'pdf,jpg,jpeg,png'
                        ],
                        [
                            'name' => 'clearance',
                            'label' => 'Clearance',
                            'type' => 'file',
                            'required' => true,
                            'description' => 'Upload a scanned copy of your Clearance',
                            'allowed_types' => 'pdf,jpg,jpeg,png'
                        ]
                    ],
                    'instructions' => 'Please upload the required documents for Transcript of Records. Your student information will be automatically included.',
                    'key' => $type['name'] . '_' . $type_id . '_' . time(),
                    'force_refresh' => true
                ];
            }
            else {
                // Format the requirements for other request types
                $requirements = $this->formatRequirements($type['requirements']);
            }

            // Add unique identifier in the response
            $response = [
                'status' => 'success',
                'message' => 'Requirements retrieved successfully',
                'data' => [
                    'type_id' => intval($type['type_id']),
                    'name' => $type['name'],
                    'description' => $type['description'],
                    'requirements' => $requirements,
                    'processing_time' => $type['processing_time'],
                    'key' => $type['name'] . '_' . $type_id . '_' . time(), // Add unique key
                    'force_refresh' => true
                ],
                'code' => 200,
                'timestamp' => time()
            ];

            // Log the response for debugging
            error_log("Requirements response for {$type['name']}: " . json_encode($response));

            return $response;
        } catch(PDOException $e) {
            error_log("Database error in getTypeRequirements: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            http_response_code(500);
            return [
                'status' => 'error',
                'message' => 'Failed to fetch requirements',
                'code' => 500
            ];
        } catch(Exception $e) {
            error_log("General error in getTypeRequirements: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            http_response_code(500);
            return [
                'status' => 'error',
                'message' => 'An unexpected error occurred',
                'code' => 500
            ];
        }
    }

    private function getRequestByTrackingNumber($tracking_number) {
        try {
            // Extract user ID from JWT token
            $user_id = $this->getUserIdFromToken();
            if (!$user_id) {
                http_response_code(401);
                return ['status' => 'error', 'message' => 'Unauthorized: Invalid token'];
            }
            
            error_log("Using user ID from token: " . $user_id);
            
            error_log("Getting request by tracking number: " . $tracking_number);
            $result = $this->request->getDetailsByTrackingNumber($tracking_number, $user_id);
            
            if (!$result) {
                http_response_code(404);
                return ['status' => 'error', 'message' => 'Request not found'];
            }
            return ['status' => 'success', 'data' => $result];
        } catch (Exception $e) {
            error_log("Error getting request by tracking number: " . $e->getMessage());
            http_response_code(500);
            return ['status' => 'error', 'message' => 'Failed to fetch request: ' . $e->getMessage()];
        }
    }

    private function getUserIdFromToken() {
        $headers = getallheaders();
        $authorization = isset($headers['Authorization']) ? $headers['Authorization'] : '';

        if (strpos($authorization, 'Bearer ') === 0) {
            $token = substr($authorization, 7);
            
            // Include JWT helper if not already included
            $jwt_helper_path = __DIR__ . '/../helpers/jwt_helper.php';
            error_log("Looking for JWT helper at: " . $jwt_helper_path);
            
            if (!file_exists($jwt_helper_path)) {
                error_log("JWT helper file not found at: " . $jwt_helper_path);
                $absolute_path = realpath(dirname(__FILE__) . '/../helpers/jwt_helper.php');
                error_log("Absolute path: " . $absolute_path);
                if ($absolute_path && file_exists($absolute_path)) {
                    require_once $absolute_path;
                } else {
                    error_log("Could not find JWT helper with absolute path either.");
                    return null;
                }
            } else {
                require_once $jwt_helper_path;
            }
            
            try {
                // Decode the token and extract user ID
                $decoded = JWT::decode($token);
                
                if (isset($decoded->user_id)) {
                    return $decoded->user_id;
                }
                
                error_log("Token does not contain user_id");
                return null;
            } catch (Exception $e) {
                error_log("Token validation error: " . $e->getMessage());
                return null;
            }
        }
        
        error_log("No valid authorization token provided");
        return null;
    }
} 