# Cloudflare DNS IP Migrator (PHP)

A simple CLI tool to migrate DNS A records from an old IP to a new IP across multiple Cloudflare zones.

## Features

- Supports filtering zones by name.
- Can perform dry runs without making actual changes.
- Provides verbose logging.
- Outputs results in plain text or JSON format.

## Requirements

- PHP 7.0 or higher
- cURL extension enabled

## Usage
 
```
php cloudflare_dns_migrator.php --token=YOUR_API_TOKEN --old-ip=OLD_IP --new-ip=NEW_IP [options]
```

## Options

| Short | Long | Description | Default |
|--------|---------------|------------------------------------------------|--------------|
| `-t` | --token | Cloudflare API Token | (required) |
| `-o` | --old-ip | The IP address to replace | (required) |
| `-n` | --new-ip | The new IP address to set | (required) |
| `-r` | --type | DNS record type (default: A) | A |
| `-z` | --zone | Comma-separated list of zones to process | All zones |
| `--dry-run` | | Show what would be changed without applying | false |
| `--json` | | Output results in JSON format | false |
| `--verbose` | | Enable verbose logging | false |

### Example
 
```
php cloudflare_dns_migrator.php --token=abc123 --old-ip=192.168.1.1 --new-ip=203.0.113.5 --verbose
```

```
php cloudflare_dns_migrator.php --token=abc123 --old-ip=192.168.1.1 --new-ip=203.0.113.5 --zone=example.com,subdomain.com -dry-run --verbose
```

```
php cloudflare_dns_migrator.php --token=abc123 --old-ip=192.168.1.1 --new-ip=203.0.113.5 --zone=example.com,subdomain.com --verbose
```

### License

This project is licensed under the MIT License.

### Copyright

2025 Max Base
