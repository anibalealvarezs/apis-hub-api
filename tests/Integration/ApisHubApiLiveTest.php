<?php

namespace Tests\Integration;

use Anibalealvarezs\ApisHubApi\ApisHubApi;
use Exception;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;

class ApisHubApiLiveTest extends TestCase
{
    protected ?ApisHubApi $api = null;
    protected Logger $logger;

    protected function setUp(): void
    {
        $baseUrl = app_config('hub_base_url');
        $apiKey = app_config('hub_admin_api_key');

        if (!$baseUrl || !$apiKey) {
            $this->markTestSkipped('APIs Hub Live credentials not provided in config.yaml');
        }

        $this->api = new ApisHubApi($baseUrl, $apiKey);
        $this->logger = new Logger('test-integration');
        $this->logger->pushHandler(new StreamHandler('tests-integration.log', 'debug'));
    }

    public function testLiveGetHeartbeat(): void
    {
        $data = $this->api->getHeartbeat();
        $this->logger->debug('testLiveGetHeartbeat response', $data);

        $this->assertIsArray($data);
        $this->assertTrue(($data['success'] ?? false) || ($data['status'] ?? '') === 'success');
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('status', $data['data']);
    }

    public function testLiveGetStatus(): void
    {
        $data = $this->api->getStatus();
        $this->logger->debug('testLiveGetStatus response', $data);

        $this->assertIsArray($data);
        $this->assertTrue(($data['success'] ?? false) || ($data['status'] ?? '') === 'success');
        $this->assertArrayHasKey('data', $data);
    }

    public function testLiveEntityCount(): void
    {
        $data = $this->api->countEntities('metric');
        $this->logger->debug('testLiveEntityCount response', $data);

        $this->assertIsArray($data);
        $this->assertTrue(($data['success'] ?? false) || ($data['status'] ?? '') === 'success');
        $this->assertArrayHasKey('data', $data);
    }

    public function testLiveListEntities(): void
    {
        $data = $this->api->listEntities('metric', ['limit' => 2]);
        $this->logger->debug('testLiveListEntities response', $data);

        $this->assertIsArray($data);
        $this->assertTrue(($data['success'] ?? false) || ($data['status'] ?? '') === 'success');
        $this->assertArrayHasKey('data', $data);
        $this->assertIsArray($data['data']);
    }

    public function testLiveGetMonitoringData(): void
    {
        $data = $this->api->getMonitoringData();
        $this->logger->debug('testLiveGetMonitoringData response', $data);

        $this->assertIsArray($data);
        // Monitoring data returns raw object with containers/dbTotals
        $this->assertTrue(isset($data['containers']) || ($data['success'] ?? false) || ($data['status'] ?? '') === 'success');
    }

    public function testLiveGetLogList(): void
    {
        $data = $this->api->getLogList();
        $this->logger->debug('testLiveGetLogList response', $data);

        $this->assertIsArray($data);
        // Log list returns raw object with logs key
        $this->assertTrue(isset($data['logs']) || ($data['success'] ?? false) || ($data['status'] ?? '') === 'success');
    }

    public function testLiveFetchAssets(): void
    {
        // Don't force refresh to avoid long wait/API calls
        $data = $this->api->fetchAssets(['refresh' => '0']);
        $this->logger->debug('testLiveFetchAssets response', $data);

        $this->assertIsArray($data);
        // fetchAssets returns raw object with assets/config
        $this->assertTrue(isset($data['assets']) || ($data['success'] ?? false) || ($data['status'] ?? '') === 'success');
    }
}
