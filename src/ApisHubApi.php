<?php

namespace Anibalealvarezs\ApisHubApi;

use Anibalealvarezs\ApiSkeleton\Clients\ApiKeyClient;
use GuzzleHttp\Exception\GuzzleException;

class ApisHubApi extends ApiKeyClient
{
    /**
     * @param string $baseUrl The base URL of the APIs Hub Node (without /api path)
     * @param string $apiKey The admin API key (X-Admin-API-Key)
     * @param \GuzzleHttp\Client|null $guzzleClient Optional Guzzle client for testing
     * @param bool $debugMode Enable SDK debug mode
     * @throws \Exception
     */
    public function __construct(
        string $baseUrl,
        string $apiKey,
        ?\GuzzleHttp\Client $guzzleClient = null,
        bool $debugMode = false
    ) {
        parent::__construct(
            baseUrl: rtrim($baseUrl, '/') . '/',
            apiKey: $apiKey,
            authSettings: [
                'location' => 'header',
                'name' => 'X-Admin-API-Key',
            ],
            defaultHeaders: [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            guzzleClient: $guzzleClient,
            debugMode: $debugMode
        );
    }

    /**
     * 🛰️ Management: Trigger a background redeployment.
     * @throws GuzzleException
     */
    public function triggerRedeploy(): array
    {
        $response = $this->performRequest(method: 'POST', endpoint: 'api/management/redeploy');
        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * 🛰️ Management: Perform a TOTAL reset of a specific channel (Atomic Cleanup).
     * @param string $channel
     * @throws GuzzleException
     */
    public function resetChannel(string $channel): array
    {
        $response = $this->performRequest(
            method: 'POST', 
            endpoint: 'api/management/reset-channel',
            body: json_encode(['channel' => $channel])
        );
        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * 🛰️ Management: Update remote environment credentials.
     * @throws GuzzleException
     */
    public function updateCredentials(array $credentials): array
    {
        $response = $this->performRequest(
            method: 'POST',
            endpoint: 'api/management/update-credentials',
            body: json_encode($credentials)
        );
        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * 🩹 Health: Fetch comprehensive heartbeat diagnostic.
     * @throws GuzzleException
     */
    public function getHeartbeat(): array
    {
        $response = $this->performRequest(method: 'GET', endpoint: 'api/heartbeat');
        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * 📊 Synchronization: Manually trigger a data sync job.
     * @param string $channel e.g. "all", "facebook_marketing", "google_search_console"
     * @throws GuzzleException
     */
    public function triggerSync(string $channel = 'all'): array
    {
        $response = $this->performRequest(
            method: 'POST',
            endpoint: "cache/{$channel}/all"
        );
        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * 📊 Synchronization: Interrupt all running jobs.
     * @throws GuzzleException
     */
    public function stopJobs(): array
    {
        $response = $this->performRequest(method: 'POST', endpoint: 'cache/interrupt');
        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * 📝 CRUD: List all instances of a base entity.
     * @throws GuzzleException
     */
    public function listEntities(string $entity, array $params = []): array
    {
        $response = $this->performRequest(method: 'GET', endpoint: "entity/{$entity}", query: $params);
        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * 📝 CRUD: Create a new base entity instance.
     * @throws GuzzleException
     */
    public function createEntity(string $entity, array $data): array
    {
        $response = $this->performRequest(method: 'POST', endpoint: "entity/{$entity}/create", body: json_encode($data));
        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * 📝 CRUD: Read a specific base entity instance.
     * @throws GuzzleException
     */
    public function readEntity(string $entity, string|int $id): array
    {
        $response = $this->performRequest(method: 'GET', endpoint: "entity/{$entity}/{$id}");
        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * 📝 CRUD: Update a specific base entity instance.
     * @throws GuzzleException
     */
    public function updateEntity(string $entity, string|int $id, array $data): array
    {
        $response = $this->performRequest(method: 'PUT', endpoint: "entity/{$entity}/{$id}/update", body: json_encode($data));
        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * 📝 CRUD: Delete a specific base entity instance.
     * @throws GuzzleException
     */
    public function deleteEntity(string $entity, string|int $id): array
    {
        $response = $this->performRequest(method: 'DELETE', endpoint: "entity/{$entity}/{$id}/delete");
        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * 📝 CRUD: Count base entity instances.
     * @throws GuzzleException
     */
    public function countEntities(string $entity, array $params = []): array
    {
        $response = $this->performRequest(method: 'GET', endpoint: "entity/{$entity}/count", query: $params);
        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * 📝 CRUD: Perform an aggregation query on base entities.
     * @throws GuzzleException
     */
    public function aggregateEntities(string $entity, array $payload): array
    {
        $response = $this->performRequest(method: 'POST', endpoint: "entity/{$entity}/aggregate", body: json_encode($payload));
        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * 🧬 Channeled CRUD: List entities filtered by channel.
     * @throws GuzzleException
     */
    public function listChanneled(string $channel, string $entity, array $params = []): array
    {
        $response = $this->performRequest(method: 'GET', endpoint: "{$channel}/{$entity}", query: $params);
        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * 🧬 Channeled CRUD: Read a specific channeled entity instance.
     * @throws GuzzleException
     */
    public function readChanneled(string $channel, string $entity, string|int $id): array
    {
        $response = $this->performRequest(method: 'GET', endpoint: "{$channel}/{$entity}/{$id}");
        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * 🧬 Channeled CRUD: Count channeled instances.
     * @throws GuzzleException
     */
    public function countChanneled(string $channel, string $entity, array $params = []): array
    {
        $response = $this->performRequest(method: 'GET', endpoint: "{$channel}/{$entity}/count", query: $params);
        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * 🧬 Channeled CRUD: Perform an aggregation query on channeled entities.
     * @throws GuzzleException
     */
    public function aggregateChanneled(string $channel, string $entity, array $payload): array
    {
        $response = $this->performRequest(method: 'POST', endpoint: "{$channel}/{$entity}/aggregate", body: json_encode($payload));
        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * 🧬 Channeled CRUD: Fetch the available range (min/max) for an entity field.
     * @throws GuzzleException
     */
    public function rangeChanneled(string $channel, string $entity, array $params = []): array
    {
        $response = $this->performRequest(method: 'GET', endpoint: "{$channel}/{$entity}/range", query: $params);
        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * ⚡ Cache: Trigger a background cache aggregation job.
     * @throws GuzzleException
     */
    public function triggerCache(string $channel, string $entity, array $payload = []): array
    {
        $response = $this->performRequest(method: 'POST', endpoint: "cache/{$channel}/{$entity}", body: json_encode($payload));
        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * ⚡ Cache: Interrupt running cache aggregation jobs.
     * @throws GuzzleException
     */
    public function interruptCache(?string $channel = null, ?string $entity = null): array
    {
        $response = $this->performRequest(
            method: 'POST',
            endpoint: 'cache/interrupt',
            body: json_encode(['channel' => $channel, 'entity' => $entity])
        );
        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * ⚡ Cache: Manually reset/invalidate the cache for an entity.
     * @throws GuzzleException
     */
    public function resetCache(string $entity, ?array $ids = null, ?string $channel = null): array
    {
        $response = $this->performRequest(
            method: 'POST',
            endpoint: "cache/reset/{$entity}",
            body: json_encode(array_filter(['ids' => $ids, 'channel' => $channel]))
        );
        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * 🛠️ Configuration: Update channel assets or global node config.
     * @throws GuzzleException
     */
    public function updateConfig(array $payload): array
    {
        $response = $this->performRequest(
            method: 'POST',
            endpoint: 'api/config-manager/update',
            body: json_encode($payload)
        );
        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * 🛰️ Management: Import social credentials (zero-downtime hot-reload).
     * @param string $provider e.g. "facebook"
     * @param array $payload Access token and user ID data
     * @throws GuzzleException
     */
    public function importCredentials(string $provider, array $payload): array
    {
        $response = $this->performRequest(
            method: 'POST',
            endpoint: "api/auth/{$provider}/import",
            body: json_encode($payload),
            headers: [
                'X-Config-Token' => $this->apiKey // Using Config Token auth here
            ]
        );
        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * 🔍 Monitoring: Fetch real-time job/log state.
     * @throws GuzzleException
     */
    public function getMonitoringData(): array
    {
        $response = $this->performRequest(method: 'GET', endpoint: 'api/monitoring/data');
        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * 🔍 Monitoring: Get lightweight infra status.
     * @throws GuzzleException
     */
    public function getStatus(): array
    {
        $response = $this->performRequest(method: 'GET', endpoint: 'api/management/status');
        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * 🔍 Monitoring: Get log history/list.
     * @throws GuzzleException
     */
    public function getLogList(): array
    {
        $response = $this->performRequest(method: 'GET', endpoint: 'api/monitoring/logs/list');
        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * 🔍 Monitoring: Get specific log content.
     * @throws GuzzleException
     */
    public function getLogs(array $params = []): array
    {
        $response = $this->performRequest(method: 'GET', endpoint: 'api/monitoring/logs', query: $params);
        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * 🔍 Monitoring: Perform action on a background job (e.g. "cancel").
     * @throws GuzzleException
     */
    public function jobAction(string $jobId, string $action): array
    {
        $response = $this->performRequest(
            method: 'POST',
            endpoint: 'api/monitoring/jobs/action',
            body: json_encode(['id' => $jobId, 'action' => $action])
        );
        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * 🛠️ Configuration: Fetch discoverable assets from remote platforms.
     * @throws GuzzleException
     */
    public function fetchAssets(array $params = []): array
    {
        $response = $this->performRequest(method: 'GET', endpoint: 'api/config-manager/assets', query: $params);
        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * 🛠️ Configuration: Validate platform tokens/credentials.
     * @throws GuzzleException
     */
    public function validateTokens(array $payload): array
    {
        $response = $this->performRequest(
            method: 'POST',
            endpoint: 'api/config-manager/validate-tokens',
            body: json_encode($payload)
        );
        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * 🛠️ Configuration: Export node configuration as JSON/YAML.
     * @throws GuzzleException
     */
    public function exportConfig(): array
    {
        $response = $this->performRequest(method: 'POST', endpoint: 'api/config-manager/export');
        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * 🛰️ Management: Perform action on a specific container (e.g. "start", "stop").
     * @throws GuzzleException
     */
    public function containerAction(string $name, string $action): array
    {
        $response = $this->performRequest(
            method: 'POST',
            endpoint: 'api/management/container/action',
            body: json_encode(['name' => $name, 'action' => $action])
        );
        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * 🛠️ Configuration: Flush system caches.
     * @throws GuzzleException
     */
    public function flushCache(): array
    {
        $response = $this->performRequest(method: 'POST', endpoint: 'api/config-manager/flush-cache');
        return json_decode($response->getBody()->getContents(), true);
    }
}
