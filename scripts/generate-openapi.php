<?php

/**
 * Manual OpenAPI generator that bypasses Scribe's MarkdownParser bug
 */

require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Symfony\Component\Yaml\Yaml;

$endpointsDir = __DIR__.'/../.scribe/endpoints';
$outputFile = __DIR__.'/../public/docs/openapi.yaml';
$collectionFile = __DIR__.'/../public/docs/collection.json';

// Ensure output directory exists
@mkdir(dirname($outputFile), 0755, true);

// Load all endpoint YAML files
$allEndpoints = [];
$groups = [];

foreach (glob("$endpointsDir/*.yaml") as $file) {
    if (basename($file) === 'custom.0.yaml') {
        continue;
    }

    $data = Yaml::parseFile($file);
    if (isset($data['endpoints'])) {
        $groupName = $data['name'] ?? 'Endpoints';
        $groupDesc = $data['description'] ?? '';

        $groups[$groupName] = $groupDesc;

        foreach ($data['endpoints'] as $endpoint) {
            $allEndpoints[] = $endpoint;
        }
    }
}

// Build OpenAPI spec
$config = require __DIR__.'/../config/scribe.php';

$spec = [
    'openapi' => '3.0.3',
    'info' => [
        'title' => $config['title'] ?? 'Laravel API Documentation',
        'description' => $config['description'] ?? '',
        'version' => '1.0.0',
    ],
    'servers' => [
        ['url' => $config['base_url'] ?? 'http://localhost'],
    ],
    'tags' => [],
    'components' => [
        'securitySchemes' => [
            'default' => [
                'type' => 'http',
                'scheme' => 'bearer',
                'description' => $config['auth']['extra_info'] ?? '',
            ],
        ],
    ],
    'security' => [
        ['default' => []],
    ],
    'paths' => [],
];

// Add tags
foreach ($groups as $name => $desc) {
    $spec['tags'][] = [
        'name' => $name,
        'description' => trim($desc),
    ];
}

// Add endpoints
foreach ($allEndpoints as $endpoint) {
    $uri = '/'.ltrim($endpoint['uri'], '/');
    $methods = $endpoint['httpMethods'] ?? ['GET'];
    $metadata = $endpoint['metadata'] ?? [];

    foreach ($methods as $method) {
        $method = strtolower($method);

        if (! isset($spec['paths'][$uri])) {
            $spec['paths'][$uri] = [];
        }

        $operation = [
            'summary' => $metadata['title'] ?? '',
            'operationId' => $metadata['title'] ? str_replace([' ', '-'], '_', strtolower($metadata['title'])) : null,
            'description' => $metadata['description'] ?? '',
            'parameters' => [],
            'responses' => [],
            'tags' => [$metadata['groupName'] ?? 'Endpoints'],
        ];

        // Add URL parameters
        foreach ($endpoint['urlParameters'] ?? [] as $param) {
            $operation['parameters'][] = [
                'name' => $param['name'],
                'in' => 'path',
                'description' => $param['description'] ?? '',
                'required' => $param['required'] ?? true,
                'schema' => [
                    'type' => $param['type'] ?? 'string',
                    'example' => $param['example'] ?? null,
                ],
            ];
        }

        // Add query parameters
        foreach ($endpoint['queryParameters'] ?? [] as $param) {
            $operation['parameters'][] = [
                'name' => $param['name'],
                'in' => 'query',
                'description' => $param['description'] ?? '',
                'required' => $param['required'] ?? false,
                'schema' => [
                    'type' => $param['type'] ?? 'string',
                    'example' => $param['example'] ?? null,
                ],
            ];
        }

        // Add request body
        if (! empty($endpoint['bodyParameters'])) {
            $properties = [];
            $required = [];

            foreach ($endpoint['bodyParameters'] as $param) {
                $properties[$param['name']] = [
                    'type' => $param['type'] ?? 'string',
                    'description' => $param['description'] ?? '',
                    'example' => $param['example'] ?? null,
                ];

                if ($param['required'] ?? false) {
                    $required[] = $param['name'];
                }
            }

            $operation['requestBody'] = [
                'required' => ! empty($required),
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => $properties,
                            'required' => $required,
                        ],
                    ],
                ],
            ];
        }

        // Add responses
        foreach ($endpoint['responses'] ?? [] as $response) {
            $status = $response['status'] ?? 200;
            $content = $response['content'] ?? '';

            $operation['responses'][$status] = [
                'description' => $response['description'] ?? '',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'example' => is_string($content) ? json_decode($content, true) : $content,
                        ],
                    ],
                ],
            ];
        }

        $spec['paths'][$uri][$method] = $operation;
    }
}

// Write OpenAPI spec
file_put_contents($outputFile, Yaml::dump($spec, 10, 2));
echo "✓ Generated OpenAPI spec: $outputFile\n";

// Generate basic Postman collection
$collection = [
    'info' => [
        'name' => $config['title'] ?? 'Laravel API',
        'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
    ],
    'auth' => [
        'type' => 'bearer',
        'bearer' => [
            ['key' => 'token', 'value' => '{{token}}', 'type' => 'string'],
        ],
    ],
    'item' => [],
];

foreach ($groups as $groupName => $groupDesc) {
    $folder = [
        'name' => $groupName,
        'item' => [],
    ];

    foreach ($allEndpoints as $endpoint) {
        $metadata = $endpoint['metadata'] ?? [];
        if (($metadata['groupName'] ?? '') !== $groupName) {
            continue;
        }

        foreach ($endpoint['httpMethods'] ?? ['GET'] as $method) {
            $folder['item'][] = [
                'name' => $metadata['title'] ?? $endpoint['uri'],
                'request' => [
                    'method' => $method,
                    'url' => [
                        'raw' => '{{baseUrl}}/'.$endpoint['uri'],
                        'host' => ['{{baseUrl}}'],
                        'path' => explode('/', $endpoint['uri']),
                    ],
                ],
            ];
        }
    }

    if (! empty($folder['item'])) {
        $collection['item'][] = $folder;
    }
}

file_put_contents($collectionFile, json_encode($collection, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
echo "✓ Generated Postman collection: $collectionFile\n";

echo "\n✓ Documentation generated successfully!\n";
echo "  View at: http://byoe.test/docs\n";
