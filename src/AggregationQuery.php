<?php

declare(strict_types=1);

namespace Anibalealvarezs\ApisHubApi;

/**
 * 🧬 APIs Hub Aggregation Query Builder
 * 
 * Provides a clean, fluent, chainable builder to comprehensively construct
 * aggregation and range-slicing payloads for standard and channeled entities.
 */
class AggregationQuery
{
    private array $aggregations = [];
    private array $groupBy = [];
    private ?string $startDate = null;
    private ?string $endDate = null;
    private ?string $orderBy = null;
    private string $orderDir = 'ASC';
    private array $filters = [];

    /**
     * Create a new fluent AggregationQuery instance.
     */
    public static function create(): self
    {
        return new self();
    }

    /**
     * Set the metrics/columns to aggregate (e.g. `['clicks' => 'clicks', 'ctr' => 'ctr']`).
     */
    public function select(array $aggregations): self
    {
        $this->aggregations = $aggregations;
        return $this;
    }

    /**
     * Set the columns/fields to group by (e.g. `['campaign_id', 'adset_id']`).
     */
    public function groupBy(array $groupBy): self
    {
        $this->groupBy = $groupBy;
        return $this;
    }

    /**
     * Set the start and end boundaries for the aggregation time period (Y-m-d).
     */
    public function dateRange(?string $startDate, ?string $endDate): self
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        return $this;
    }

    /**
     * Set the sorting parameters.
     */
    public function orderBy(?string $orderBy, string $orderDir = 'ASC'): self
    {
        $this->orderBy = $orderBy;
        $this->orderDir = strtoupper($orderDir) === 'DESC' ? 'DESC' : 'ASC';
        return $this;
    }

    /**
     * Add a custom standard/advanced filter.
     */
    public function filter(string $key, mixed $value): self
    {
        $this->filters[$key] = $value;
        return $this;
    }

    /**
     * Filter by period type (e.g., 'daily', 'weekly', 'monthly', 'lifetime').
     */
    public function setPeriod(string $period): self
    {
        $this->filters['period'] = $period;
        return $this;
    }

    /**
     * Enable snapshot delta calculation (returns the net metric delta between start and end).
     */
    public function withSnapshotDelta(bool $delta = true): self
    {
        $this->filters['snapshot_delta'] = $delta;
        return $this;
    }

    /**
     * Enforce aggregation based only on the latest available snapshot state.
     */
    public function withLatestSnapshot(bool $latest = true): self
    {
        $this->filters['latest_snapshot'] = $latest;
        return $this;
    }

    /**
     * Set snapshot fallback resilience mode (e.g. 'strict' or 'resilient').
     */
    public function setFallbackMode(string $mode): self
    {
        $this->filters['snapshot_fallback_mode'] = $mode;
        return $this;
    }

    /**
     * Compile the fluent query parameters into a structured APIs Hub payload array.
     */
    public function build(): array
    {
        $payload = [
            'aggregations' => $this->aggregations,
            'groupBy' => $this->groupBy,
        ];

        if ($this->startDate !== null) {
            $payload['startDate'] = $this->startDate;
        }
        if ($this->endDate !== null) {
            $payload['endDate'] = $this->endDate;
        }
        if ($this->orderBy !== null) {
            $payload['orderBy'] = $this->orderBy;
            $payload['orderDir'] = $this->orderDir;
        }
        if (!empty($this->filters)) {
            $payload['filters'] = $this->filters;
        }

        return $payload;
    }
}
