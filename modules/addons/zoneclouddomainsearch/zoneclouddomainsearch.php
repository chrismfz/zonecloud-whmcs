<?php

use WHMCS\Database\Capsule;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

// Avoid duplicate function definition
if (!function_exists('getZcloudConfig')) {
    function getZcloudConfig($key)
    {
        return Capsule::table("tbladdonmodules")
            ->where("module", "zoneclouddomainsearch")
            ->where("setting", $key)
            ->value("value");
    }
}

function zoneclouddomainsearch_config()
{
    return [
        'name' => 'ZoneCloud Domain Search',
        'description' => 'This module provides ZoneCloud domain results in your admin page and search hook.',
        'author' => 'NixPal OU',
        'language' => 'english',
        'version' => '1.0',
        'fields' => [
            'zcloud_url' => [
                'FriendlyName' => 'ZoneCloud Controller URL',
                'Type' => 'text',
                'Size' => '100',
                'Default' => '',
                'Description' => 'The URL of your ZoneCloud Controller instance (e.g. https://controller.nixpal.com)',
            ],
            'zcloud_token' => [
                'FriendlyName' => 'Token',
                'Type' => 'password',
                'Size' => '25',
                'Default' => '',
                'Description' => 'Enter your API token here.',
            ],
            'zcloud_exact' => [
                'FriendlyName' => 'Exact match',
                'Type' => 'yesno',
                'Description' => 'Disable to match substrings in your zones.',
            ],
        ],
    ];
}

function zoneclouddomainsearch_activate()
{
    return [
        'status' => 'success',
        'description' => 'Module enabled',
    ];
}

function zoneclouddomainsearch_deactivate()
{
    return [
        'status' => 'success',
        'description' => 'Module disabled',
    ];
}

function fetchZonesStats($baseUrl, $token)
{
    $endpoints = [
        'all' => '/zones/list_all',
        'active' => '/zones/list',
        'excluded' => '/zones/list_excluded',
    ];

    $stats = [];
    $headers = ['Authorization: ' . $token];

    foreach ($endpoints as $key => $endpoint) {
        $fullUrl = rtrim($baseUrl, '/') . '/api' . $endpoint;
        echo "<pre><code>Stats URL ({$key}): {$fullUrl}</code></pre>";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $fullUrl);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            $data = json_decode($response, true);
            $stats[$key] = (json_last_error() === JSON_ERROR_NONE && is_array($data)) ? count($data) : 'Error (JSON)';
        } else {
            $stats[$key] = 'Unavailable (HTTP ' . $httpCode . ')';
        }
    }

    return $stats;
}

function zoneclouddomainsearch_output($vars)
{
    $controllerUrl = getZcloudConfig('zcloud_url');
    $apiToken = getZcloudConfig('zcloud_token');
    $exactMatch = getZcloudConfig('zcloud_exact') ? 'exact' : 'contains';

    echo "<pre><code>Token: " . htmlspecialchars($apiToken) . "</code></pre>";

    $baseUrl = rtrim($controllerUrl, '/');
    if (!str_contains($baseUrl, '/api/find-zone-info')) {
        $baseUrl .= '/api/find-zone-info';
    }

    $stats = fetchZonesStats($controllerUrl, $apiToken);

    echo '<h3>Zone Statistics</h3>';
    echo '<div class="row">';
    echo '<div class="col-md-4"><div class="panel panel-info"><div class="panel-heading">Total Zones</div><div class="panel-body text-center"><strong>' . $stats['all'] . '</strong></div></div></div>';
    echo '<div class="col-md-4"><div class="panel panel-success"><div class="panel-heading">Active Zones</div><div class="panel-body text-center"><strong>' . $stats['active'] . '</strong></div></div></div>';
    echo '<div class="col-md-4"><div class="panel panel-warning"><div class="panel-heading">Excluded Zones</div><div class="panel-body text-center"><strong>' . $stats['excluded'] . '</strong></div></div></div>';
    echo '</div><hr>';

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['domain_name'])) {
        $domainName = trim($_POST['domain_name']);
        $apiUrl = $baseUrl . '/' . urlencode($domainName) . '/' . $exactMatch;

        echo "<pre><code>Search URL: {$apiUrl}</code></pre>";

        $headers = ['Authorization: ' . $apiToken];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            $error = 'Request Error: ' . curl_error($ch);
        } else {
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($httpCode !== 200) {
                $error = 'API returned HTTP Code ' . $httpCode;
            } else {
                $data = json_decode($response, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $error = 'Invalid JSON response';
                }
            }
        }

        curl_close($ch);
    }

    echo '<h2>ZoneCloud Domain Search</h2>';
    echo '<form method="post" class="form-inline" style="margin-bottom: 20px;">';
    echo '<div class="form-group">';
    echo '<label for="domain_name">Enter Domain Name:</label> ';
    echo '<input type="text" name="domain_name" id="domain_name" class="form-control" required>';
    echo '</div> ';
    echo '<button type="submit" class="btn btn-primary">Search</button>';
    echo '</form>';

    if (isset($error)) {
        echo '<div class="alert alert-danger">' . htmlspecialchars($error) . '</div>';
    } elseif (isset($data)) {
        if (!empty($data['zones'])) {
            echo '<h3>Domain Information:</h3>';
            echo '<table class="table table-bordered table-striped">';
            echo '<thead><tr><th>Server ID</th><th>Zone</th><th>Owner</th><th>Insert Date</th><th>Server Name</th></tr></thead>';
            echo '<tbody>';
            foreach ($data['zones'] as $zone) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($zone['server_id']) . '</td>';
                echo '<td>' . htmlspecialchars($zone['zone']) . '</td>';
                echo '<td>' . htmlspecialchars($zone['owner']) . '</td>';
                echo '<td>' . htmlspecialchars($zone['insert_date']) . '</td>';
                echo '<td>' . htmlspecialchars($zone['server_name']) . '</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        } else {
            echo '<div class="alert alert-warning">No data found for the specified domain.</div>';
        }
    }
}
