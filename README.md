# LiveSearch Read-Only for Adobe Commerce

Query any SaaS data space (production, staging, dev) from your local environment without risking data corruption.

> **Important Disclaimer**
>
> This module is intended exclusively for local development.
> It must never be installed or enabled in production, staging, or any shared environment, because it overrides SaaS behaviour in a way that is only safe for isolated developer machines.
>
> For this reason, it must always be installed using `composer require --dev`, ensuring it cannot be deployed to higher environments through your CI/CD pipeline.

## The Problem - Business & Technical

### Business risk

To use LiveSearch locally, developers need SaaS credentials. But once configured, Magento may try to sync your local test catalog into the real SaaS data space via cron or reindexing.
This can break search for real customers, pollute production data, and cause trading issues.

### Technical limitation

Adobe Commerce provides no built-in mechanism to query LiveSearch without also enabling the rest of the SaaS synchronisation ecosystem.
If any credentials exist, Magento assumes the full SaaS stack should operate — even locally.

## The Solution

This module introduces dedicated read-only credentials used only by LiveSearch.
Your local environment can safely query any SaaS space, while all outbound sync paths remain impossible to trigger.

```
Local Environment
├── Main SaaS Credentials: EMPTY (no sync possible)
└── LiveSearch Credentials: Any data space (read-only queries)
```

The module overrides DI for `Magento\LiveSearch\Api\ServiceClient` only—other SaaS services remain disconnected.

### Firewall

As an extra safety layer, there is a small Guzzle middleware that effectively blocks any non-search traffic going through the LiveSearch HTTP client. It's not a real firewall, but it works the same way for our purposes - anything that isn't hitting `search/graphql`, `search/auth-graphql`, or `search-admin/graphql` simply won't reach the API.

## Requirements

- Adobe Commerce 2.4.4+
- PHP 8.1+
- `magento/module-live-search`
- `magento/module-services-connector`
- `magento/module-services-id`

## Installation

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
