<?php

namespace App\Services\Seo;

class SeoPriorityScorer
{
    /**
     * Priority Score = (Impact × Confidence) / Effort
     * Configurable weights via config/seo.php
     */
    public function score(int $impact, int $confidence, string $effort): float
    {
        $effortMap = (array) config('seo.effort_weights', [
            'low' => 1.0,
            'medium' => 2.0,
            'high' => 3.5,
        ]);
        $effortWeight = (float) ($effortMap[strtolower($effort)] ?? 2.0);
        $impact = max(1, min(100, $impact));
        $confidence = max(1, min(100, $confidence));

        return round(($impact * $confidence) / max(0.5, $effortWeight * 10), 4);
    }

    public function priorityLabel(float $score): string
    {
        $urgent = (float) config('seo.priority_thresholds.urgent', 70);
        $high = (float) config('seo.priority_thresholds.high', 40);
        $medium = (float) config('seo.priority_thresholds.medium', 20);

        return match (true) {
            $score >= $urgent => 'URGENT',
            $score >= $high => 'HIGH',
            $score >= $medium => 'MEDIUM',
            default => 'LOW',
        };
    }
}
