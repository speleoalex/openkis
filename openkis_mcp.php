<?php
// gemini mcp add --transport http tools https://www.openspeleo.org/mcp_server_php/http_mcp.php
ini_set("display_errors", "on");
error_reporting(E_ALL);
require 'loadfinis.php';
$FINIS = new FINIS();

/**
 * HTTP endpoint for MCP Server with dynamic function loading
 * Standard HTTP request-response implementation for --transport http
 */
define("ENABLE_LOG", true);
global $serverInfo;
$serverInfo = [
    'name' => 'php-dynamic-mcp-server-http',
    'version' => '1.0.0'
];

// Logging function
function log_request($message, $data = null)
{
    if (ENABLE_LOG) {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[$timestamp] $message";

        if ($data !== null) {
            $logEntry .= " - Data: " . json_encode($data, JSON_PRETTY_PRINT);
        }

        $logEntry .= "\n";
        file_put_contents(__DIR__ . '/http_mcp.php.log', $logEntry, FILE_APPEND);
    }
}

// Global variables to store dynamically loaded functions and tools
$dynamicFunctions = [];
$dynamicTools = [];

// Function to load all functions from mcp_functions directory
function loadMCPFunctions()
{
    global $dynamicFunctions, $dynamicTools;

    $functionsDir = __DIR__ . '/mcp_functions/';

    if (!is_dir($functionsDir)) {
        log_request('Functions directory not found', ['dir' => $functionsDir]);

        // Add a default function if no directory exists
        $dynamicTools[] = [
            'name' => 'echo_test',
            'description' => 'Simple echo test function',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'message' => [
                        'type' => 'string',
                        'description' => 'Message to echo back'
                    ]
                ],
                'required' => ['message'],
                'additionalProperties' => false
            ]
        ];
        $dynamicFunctions['echo_test'] = true;
        return;
    }

    $files = glob($functionsDir . '*.php');

    if (empty($files)) {
        log_request('No PHP files found in functions directory', ['dir' => $functionsDir]);

        // Add a default function if no files exist
        $dynamicTools[] = [
            'name' => 'echo_test',
            'description' => 'Simple echo test function',
            'inputSchema' => [
                'type' => 'object',
                'properties' => [
                    'message' => [
                        'type' => 'string',
                        'description' => 'Message to echo back'
                    ]
                ],
                'required' => ['message'],
                'additionalProperties' => false
            ]
        ];
        $dynamicFunctions['echo_test'] = true;
        return;
    }

    foreach ($files as $file) {
        $content = file_get_contents($file);

        // Extract function description from comments
        if (preg_match('/\/\*\s*start function description\s*(.*?)\s*end function description/s', $content, $matches)) {
            $jsonDesc = trim($matches[1]);
            $functionDesc = json_decode($jsonDesc, true);

            if ($functionDesc && isset($functionDesc['function'])) {
                $funcInfo = $functionDesc['function'];
                $functionName = $funcInfo['name'];

                // Include the file to load the function
                include_once $file;

                // Store function info for MCP tools list
                $dynamicTool = [
                    'name' => $functionName,
                    'description' => $funcInfo['description'] ?? 'No description available',
                    'inputSchema' => [
                        'type' => 'object',
                        'properties' => $funcInfo['parameters']['properties'] ?? (object)[],
                        'required' => $funcInfo['parameters']['required'] ?? [],
                        'additionalProperties' => $funcInfo['parameters']['additionalProperties'] ?? false
                    ]
                ];
                if (is_array($dynamicTool['inputSchema']['properties']) && !$dynamicTool['inputSchema']['properties']) {
                    $dynamicTool['inputSchema']['properties'] = json_decode("{}");
                    log_request('Force object:', json_encode($dynamicTool));
                } else {
                    log_request('NO force object:', json_encode($dynamicTool));
                }
                $dynamicTools[] = $dynamicTool;
                $dynamicFunctions[$functionName] = true;
                log_request('Loaded function', ['name' => $functionName, 'file' => basename($file)]);
            }
        }
    }

    log_request('Loaded functions count', ['count' => count($dynamicFunctions)]);
}

// Define default test function
function echo_test($message)
{
    return [
        'success' => true,
        'result' => 'Echo: ' . $message,
        'timestamp' => date('Y-m-d H:i:s')
    ];
}

// Load all functions at startup
loadMCPFunctions();

// Set headers for CORS and JSON response with SSE compatibility
header('Content-Type: application/json');
header('Cache-Control: no-cache');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization, Cache-Control');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Pragma: no-cache');
header('Expires: 0');

// Handle preflight and HEAD requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    log_request('OPTIONS request received');
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'HEAD') {
    log_request('HEAD request received - sending headers only');
    http_response_code(200);
    exit;
}

// Get request method and body
$method = $_SERVER['REQUEST_METHOD'];
$postBody = null;

if ($method === 'POST') {
    $postBody = file_get_contents('php://input');
}

// Log request details
log_request('HTTP Request', [
    'method' => $method,
    'uri' => $_SERVER['REQUEST_URI'],
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
    'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
    'post_body' => $postBody ? json_decode($postBody, true) : null
]);

// Handle POST requests with MCP commands
if ($method === 'POST' && $postBody) {
    $mcpRequest = json_decode($postBody, true);

    if ($mcpRequest && isset($mcpRequest['jsonrpc']) && isset($mcpRequest['method'])) {
        log_request('Processing MCP command', $mcpRequest);

        $response = handleMCPCommand($mcpRequest, $dynamicTools, $serverInfo);

        if ($response !== null) {
            log_request('Sending response', $response);
            echo json_encode($response);
        } else {
            log_request('No response needed for this command');
            echo json_encode(['jsonrpc' => '2.0', 'result' => 'ok']);
        }
        exit();
    } else {
        log_request('Invalid MCP request format');
        http_response_code(400);
        echo json_encode([
            'jsonrpc' => '2.0',
            'error' => [
                'code' => -32700,
                'message' => 'Parse error - Invalid JSON-RPC format'
            ]
        ]);
        exit();
    }
}

// Handle GET requests - return server info
if ($method === 'GET') {
    log_request('GET request - returning server info');
    echo json_encode([
        'server' => $serverInfo,
        'status' => 'running',
        'transport' => 'http',
        'available_tools' => count($dynamicTools),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit();
}

// Function to handle MCP commands
function handleMCPCommand($request, $tools, $serverInfo)
{
    $method = $request['method'];
    $params = $request['params'] ?? [];
    $id = $request['id'] ?? null;

    switch ($method) {
        case 'initialize':
            return [
                'jsonrpc' => '2.0',
                'id' => $id,
                'result' => [
                    'protocolVersion' => '2025-06-18',
                    'capabilities' => [
                        'tools' => (object)[],
                        'resources' => (object)[],
                        'prompts' => (object)[]
                    ],
                    'serverInfo' => $serverInfo
                ]
            ];

        case 'notifications/initialized':
            // Notification doesn't need a response, just acknowledge
            log_request('Received notifications/initialized - no response needed');
            return null;

        case 'tools/list':
            $ret = [
                'jsonrpc' => '2.0',
                'id' => $id,
                'result' => [
                    'tools' => $tools
                ]
            ];
            log_request('Received tools/list ' . json_encode($ret));
            return $ret;

        case 'ping':
            return [
                'jsonrpc' => '2.0',
                'id' => $id,
                'result' => 'pong'
            ];

        case 'tools/call':
            global $dynamicFunctions;
            $toolName = $params['name'] ?? '';
            $arguments = $params['arguments'] ?? [];

            // Check if the requested tool exists in our dynamically loaded functions
            if (isset($dynamicFunctions[$toolName]) && function_exists($toolName)) {
                try {
                    // Call the function with the provided arguments
                    $result = call_user_func_array($toolName, array_values($arguments));

                    return [
                        'jsonrpc' => '2.0',
                        'id' => $id,
                        'result' => [
                            'content' => [
                                [
                                    'type' => 'text',
                                    'text' => json_encode($result, JSON_PRETTY_PRINT)
                                ]
                            ]
                        ]
                    ];
                } catch (Exception $e) {
                    return [
                        'jsonrpc' => '2.0',
                        'id' => $id,
                        'error' => [
                            'code' => -32603,
                            'message' => 'Function execution error: ' . $e->getMessage()
                        ]
                    ];
                }
            }

            return [
                'jsonrpc' => '2.0',
                'id' => $id,
                'error' => [
                    'code' => -32602,
                    'message' => "Tool '$toolName' not found"
                ]
            ];

        default:
            return [
                'jsonrpc' => '2.0',
                'id' => $id,
                'error' => [
                    'code' => -32601,
                    'message' => 'Method not found'
                ]
            ];
    }
}

// If we reach here, unsupported method
log_request('Unsupported request method', ['method' => $method]);
http_response_code(405);
echo json_encode([
    'error' => 'Method not allowed',
    'supported_methods' => ['GET', 'POST', 'OPTIONS']
]);
exit();