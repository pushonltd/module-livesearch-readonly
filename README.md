# LiveSearch Read-Only for Adobe Commerce

Query any SaaS data space (production, staging, dev) from your local environment without risking data corruption.

## The Problem

Local development with LiveSearch requires SaaS credentials, but having them configured means cron and reindexing will sync your local catalog to that data space—corrupting it with incomplete or test data.

## The Solution

This module configures **separate read-only credentials** for LiveSearch queries only. Your local environment can search against any data space but never syncs anything back.

```
Local Environment
├── Main SaaS Credentials: EMPTY (no sync possible)
└── LiveSearch Credentials: Any data space (read-only queries)
```

The module overrides DI for `Magento\LiveSearch\Api\ServiceClient` only—other SaaS services remain disconnected.

## Requirements

- Adobe Commerce 2.4.4+
- PHP 8.1+
- `magento/module-live-search`
- `magento/module-services-connector`
- `magento/module-services-id`

## Installation

This module is intended for local development only. Install as a dev dependency:

```bash
composer require --dev pushon/module-livesearch-readonly
bin/magento module:enable PushON_LiveSearchReadOnly
bin/magento setup:upgrade
```

## Setup

### 1. Remove existing SaaS credentials locally

Ensure your local environment cannot sync data to any SaaS data space:

```sql
DELETE FROM core_config_data WHERE path LIKE 'services_connector%';
```

Then flush cache: `bin/magento cache:flush`

### 2. Get credentials from target environment

SSH into the environment you want to query (production, staging, or dev) and run:

```bash
bin/magento config:show services_connector/services_connector_integration/production_api_key
bin/magento config:show services_connector/services_connector_integration/production_private_key
bin/magento config:show services_connector/services_id/environment_id
```

### 3. Configure locally

Go to `Stores > Configuration > Services > LiveSearch Read-Only Credentials` and paste the credentials.

### 4. Verify

```bash
bin/magento pushon:livesearch-readonly:health
```

## Options

| Option | Description | Default |
|--------|-------------|---------|
| `enabled` | Use custom credentials | `0` |
| `fallback_enabled` | Fall back to main SaaS if custom missing | `0` |
| `api_key` | API Key | - |
| `private_key` | Private Key | - |
| `environment_id` | Environment ID | - |

## Troubleshooting

**No search results?**
- Run `bin/magento pushon:livesearch-readonly:health`
- Check credentials match the target environment
- Flush cache

**JWT signature failed?**
- Re-copy the private key from the target environment
- Paste into admin panel (newlines are handled automatically)

## License

MIT
