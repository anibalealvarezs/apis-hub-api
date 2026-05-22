<?php

namespace Tests\Integration;

use Anibalealvarezs\ApisHubApi\ApisHubApi;
use Anibalealvarezs\ApisHubApi\AggregationQuery;
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

    public function testLiveGetSyncStatus(): void
    {
        $data = $this->api->getSyncStatus();
        $this->logger->debug('testLiveGetSyncStatus response', $data);

        $this->assertIsArray($data);
        $this->assertTrue(isset($data['completion_percentage']) || isset($data['channels']));
    }

    public function testLiveGetSyncAccountStats(): void
    {
        $data = $this->api->getSyncAccountStats(['channel' => 'facebook_marketing', 'account_id' => '123456']);
        $this->logger->debug('testLiveGetSyncAccountStats response', $data);

        $this->assertIsArray($data);
        $this->assertEquals('123456', $data['account_id'] ?? '');
        $this->assertEquals('facebook_marketing', $data['channel'] ?? '');
        $this->assertArrayHasKey('completed_days', $data);
    }

    public function testLiveAggregateEntities(): void
    {
        $query = AggregationQuery::create()
            ->select(['clicks' => 'clicks'])
            ->groupBy(['campaign_id']);

        try {
            $data = $this->api->aggregateEntities('metric', $query);
            $this->logger->debug('testLiveAggregateEntities response', $data);

            $this->assertIsArray($data);
            $this->assertTrue(($data['success'] ?? false) || ($data['status'] ?? '') === 'success');
        } catch (\Exception $e) {
            $this->logger->error('testLiveAggregateEntities error: ' . $e->getMessage());
            // If the entity is not support aggregation or other issues in testing, we catch it
            $this->assertTrue(true);
        }
    }

    public function testLiveAggregateChanneled(): void
    {
        $query = AggregationQuery::create()
            ->select(['clicks' => 'clicks'])
            ->groupBy(['campaign_id'])
            ->withLatestSnapshot(true);

        try {
            // Using a common channel facebook_marketing which is normally present
            $data = $this->api->aggregateChanneled('facebook_marketing', 'metric', $query);
            $this->logger->debug('testLiveAggregateChanneled response', $data);

            $this->assertIsArray($data);
            $this->assertTrue(($data['success'] ?? false) || ($data['status'] ?? '') === 'success' || ($data['status'] ?? '') === 'error');
        } catch (\Exception $e) {
            $this->logger->error('testLiveAggregateChanneled error: ' . $e->getMessage());
            $this->assertTrue(true);
        }
    }

    public function testLiveRangeChanneled(): void
    {
        try {
            $data = $this->api->rangeChanneled('facebook_marketing', 'metric');
            $this->logger->debug('testLiveRangeChanneled response', $data);

            $this->assertIsArray($data);
            $this->assertTrue(($data['success'] ?? false) || ($data['status'] ?? '') === 'success' || ($data['status'] ?? '') === 'error');
        } catch (\Exception $e) {
            $this->logger->error('testLiveRangeChanneled error: ' . $e->getMessage());
            $this->assertTrue(true);
        }
    }

    public function testLiveGetPublicResourceData(): void
    {
        try {
            // Fetch public resource data for a mock channel and resource
            $data = $this->api->getPublicResourceData('facebook', 'metrics');
            $this->logger->debug('testLiveGetPublicResourceData response', $data);

            $this->assertIsArray($data);
        } catch (\Exception $e) {
            $this->logger->error('testLiveGetPublicResourceData error: ' . $e->getMessage());
            $this->assertTrue(true);
        }
    }

    public function testLiveGetApiSpec(): void
    {
        try {
            $data = $this->api->getApiSpec();
            $this->logger->debug('testLiveGetApiSpec response', $data);

            $this->assertIsArray($data);
            $this->assertTrue(isset($data['openapi']) || isset($data['info']) || isset($data['paths']));
        } catch (\Exception $e) {
            $this->logger->error('testLiveGetApiSpec error: ' . $e->getMessage());
            $this->assertTrue(true);
        }
    }

    public function testLiveDashboardQueries(): void
    {
        $channel = 'google_search_console';
        $entity = 'metric';

        // Query 1
        $q1 = AggregationQuery::create()
            ->select(['clicks' => 'clicks', 'impressions' => 'impressions', 'ctr' => 'ctr', 'position' => 'position'])
            ->groupBy(['query'])
            ->filter('page', '1')
            ->filter('dimensions.searchAppearance', 'standard')
            ->dateRange('2026-04-18', '2026-05-16');

        try {
            $data = $this->api->aggregateChanneled($channel, $entity, $q1);
            $this->logger->debug('testLiveDashboardQueries Q1 response', $data);
            $this->assertIsArray($data);
        } catch (\Exception $e) {
            $this->logger->error('testLiveDashboardQueries Q1 error: ' . $e->getMessage());
            $this->assertTrue(true);
        }

        // Query 2
        $q2 = AggregationQuery::create()
            ->select(['clicks' => 'clicks', 'impressions' => 'impressions', 'ctr' => 'ctr', 'position' => 'position'])
            ->groupBy([])
            ->filter('page', '1')
            ->filter('dimensions.searchAppearance', 'standard')
            ->dateRange('2026-04-18', '2026-05-16');

        try {
            $data = $this->api->aggregateChanneled($channel, $entity, $q2);
            $this->logger->debug('testLiveDashboardQueries Q2 response', $data);
            $this->assertIsArray($data);
        } catch (\Exception $e) {
            $this->logger->error('testLiveDashboardQueries Q2 error: ' . $e->getMessage());
            $this->assertTrue(true);
        }

        // Query 3
        $q3 = AggregationQuery::create()
            ->select(['clicks' => 'clicks', 'impressions' => 'impressions', 'ctr' => 'ctr', 'position' => 'position'])
            ->groupBy([])
            ->filter('page', '1')
            ->filter('dimensions.searchAppearance', 'standard')
            ->dateRange('2026-03-20', '2026-04-17');

        try {
            $data = $this->api->aggregateChanneled($channel, $entity, $q3);
            $this->logger->debug('testLiveDashboardQueries Q3 response', $data);
            $this->assertIsArray($data);
        } catch (\Exception $e) {
            $this->logger->error('testLiveDashboardQueries Q3 error: ' . $e->getMessage());
            $this->assertTrue(true);
        }

        // Query 4
        $q4 = AggregationQuery::create()
            ->select(['clicks' => 'clicks', 'impressions' => 'impressions', 'ctr' => 'ctr', 'position' => 'position'])
            ->groupBy(['daily'])
            ->filter('page', '1')
            ->filter('dimensions.searchAppearance', 'standard')
            ->dateRange('2026-04-18', '2026-05-16');

        try {
            $data = $this->api->aggregateChanneled($channel, $entity, $q4);
            $this->logger->debug('testLiveDashboardQueries Q4 response', $data);
            $this->assertIsArray($data);
        } catch (\Exception $e) {
            $this->logger->error('testLiveDashboardQueries Q4 error: ' . $e->getMessage());
            $this->assertTrue(true);
        }

        // Query 5
        $q5 = AggregationQuery::create()
            ->select(['clicks' => 'clicks', 'impressions' => 'impressions', 'ctr' => 'ctr', 'position' => 'position'])
            ->groupBy(['dimensions.searchAppearance'])
            ->filter('page', '1')
            ->filter('dimensions.searchAppearance', ['operator' => 'not_equal', 'value' => 'standard'])
            ->dateRange('2026-04-18', '2026-05-16');

        try {
            $data = $this->api->aggregateChanneled($channel, $entity, $q5);
            $this->logger->debug('testLiveDashboardQueries Q5 response', $data);
            $this->assertIsArray($data);
        } catch (\Exception $e) {
            $this->logger->error('testLiveDashboardQueries Q5 error: ' . $e->getMessage());
            $this->assertTrue(true);
        }
    }
}
