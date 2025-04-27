#!/usr/bin/env php
<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

$options = getopt("t:o:n:r:z:v", [
    "token:",
    "old-ip:",
    "new-ip:",
    "type:",
    "zone:",
    "dry-run",
    "json",
    "verbose"
]);

function getOption($name, $options, $envVar = null, $default = null) {
    if (isset($options[$name])) {
        return $options[$name];
    } elseif ($envVar !== null && getenv($envVar)) {
        return getenv($envVar);
    } else {
        return $default;
    }
}

$token = getOption("token", $options, "t", getenv('CF_API_TOKEN'));
$old_ip = getOption("old-ip", $options, "o");
$new_ip = getOption("new-ip", $options, "n");
$record_type = getOption("type", $options, "r", "A");
$zones_filter = isset($options["z"]) ? explode(",", $options["z"]) : null;

$dry_run = isset($options["dry-run"]);
$json_output = isset($options["json"]);
$verbose = isset($options["verbose"]);

if (!$token || !$old_ip || !$new_ip) {
    fwrite(STDERR, "Missing required parameters.\n");
    exit(1);
}

$api_base = "https://api.cloudflare.com/client/v4";

function cf_request($method, $endpoint, $data = null, $token, $verbose = false) {
    global $api_base;
    $url = "$api_base$endpoint";

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $token",
        "Content-Type: application/json"
    ]);
    if ($data !== null) {
        $jsonData = json_encode($data);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        if ($verbose) {
            echo "Request to $url with data: $jsonData
";
        }
    }
    $response = curl_exec($ch);
    $err = curl_error($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($err) {
        fwrite(STDERR, "cURL error: $err
");
        return null;
    }
    $decoded = json_decode($response, true);
    if ($verbose) {
        echo "Response: $response
";
    }
    return $decoded;
}

$zones_resp = cf_request("GET", "/zones", null, $token, $verbose);
if (!$zones_resp || !$zones_resp['success']) {
    fwrite(STDERR, "Failed to fetch zones.\n");
    exit(1);
}

$results = [];

foreach ($zones_resp['result'] as $zone) {
    $zone_name = $zone['name'];
    if ($zones_filter !== null && !in_array($zone_name, $zones_filter)) {
        if ($verbose) {
            echo "Skipping zone $zone_name
";
        }
        continue;
    }
    $zone_id = $zone['id'];
    if ($verbose) {
        echo "Processing zone: $zone_name
";
    }

    $dns_resp = cf_request("GET", "/zones/$zone_id/dns_records", null, $token, $verbose);
    if (!$dns_resp || !$dns_resp['success']) {
        fwrite(STDERR, "Failed to fetch DNS records for zone $zone_name.\n");
        continue;
    }

    foreach ($dns_resp['result'] as $rec) {
        if ($rec['type'] !== $record_type || $rec['content'] !== $old_ip) {
            continue;
        }
        if ($verbose) {
            echo "Found {$rec['type']} record {$rec['name']} with content {$rec['content']}\n";
        }

        $action = $dry_run ? 'DRY-RUN' : 'UPDATED';

        if (!$dry_run) {
            $update_data = [
                'type' => $rec['type'],
                'name' => $rec['name'],
                'content' => $new_ip,
                'ttl' => $rec['ttl'],
                'proxied' => $rec['proxied']
            ];
            $update_resp = cf_request("PUT", "/zones/$zone_id/dns_records/{$rec['id']}", $update_data, $token, $verbose);
            if (!$update_resp || !$update_resp['success']) {
                fwrite(STDERR, "Failed to update record {$rec['name']} in zone $zone_name.\n");
                continue;
            }
            if ($verbose) {
                echo "Record {$rec['name']} updated to {$new_ip}\n";
            }
        }

        $results[] = [
            'zone' => $zone_name,
            'record' => $rec['name'],
            'old' => $old_ip,
            'new' => $new_ip,
            'action' => $action
        ];
    }
}

if ($json_output) {
    echo json_encode($results, JSON_PRETTY_PRINT) . "\n";
} else {
    foreach ($results as $res) {
        echo "{$res['action']}: {$res['zone']} {$res['record']}\n";
    }
}
?>
