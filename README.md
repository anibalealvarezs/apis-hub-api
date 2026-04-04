# APIs Hub API PHP SDK

A standardized PHP SDK for interacting with **APIs Hub Nodes**, built on top of the `anibalealvarezs/api-client-skeleton`.

---

## 🛠️ Installation

Add the package to your `composer.json` and ensure your `repositories` section includes the private Satis server.

```json
{
    "require": {
        "anibalealvarezs/apis-hub-api": "dev-main"
    },
    "repositories": [
        { "type": "composer", "url": "https://satis.anibalalvarez.com/" }
    ]
}
```

---

## 🚀 Usage

```php
use Anibalealvarezs\ApisHubApi\ApisHubApi;

$hub = new ApisHubApi(
    baseUrl: 'https://gbs.anibalalvarez.com', 
    apiKey: 'your_admin_api_key'
);

// 🏥 Health Check
$status = $hub->getHeartbeat();

// 🛰️ Management
$hub->redeploy();

// 📊 Sync Control
$hub->triggerSync('facebook_marketing');
```

---

## 📡 Available Methods

### Infrastructure & Management
| Method | Endpoint | Description |
| :--- | :--- | :--- |
| `redeploy()` | `POST management/redeploy` | Trigger a soft restart on the node. |
| `updateCredentials(array)` | `POST management/update-credentials` | Push new environment vars. |
| `getStatus()` | `GET management/status` | Light infrastructure status. |
| `getHeartbeat()` | `GET heartbeat` | Full diagnostic report. |

### Configuration & Data
| Method | Endpoint | Description |
| :--- | :--- | :--- |
| `updateConfig(array)` | `POST config-manager/update` | Update channel assets/global config. |
| `triggerSync(string)` | `POST sync/run` | Kick off a specific channel sync. |
| `stopJobs()` | `POST sync/stop` | Interrupt all running tasks. |
| `getMonitoringData()` | `GET monitoring/data` | Real-time job/log visibility. |

---

## 🛡️ License
MIT
