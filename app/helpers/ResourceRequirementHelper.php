<?php

declare(strict_types=1);

final class ResourceRequirementHelper
{
    public static function normalize(array $requirements): array
    {
        $normalized = [];

        foreach ($requirements as $index => $requirement) {
            [$resourceId, $quantity] = self::extractRequirement($requirement, $index, $requirements);

            if ($resourceId <= 0 || $quantity <= 0) {
                continue;
            }

            if (!isset($normalized[$resourceId])) {
                $normalized[$resourceId] = [
                    'resource_id' => $resourceId,
                    'quantity' => 0,
                ];
            }

            $normalized[$resourceId]['quantity'] += $quantity;
        }

        return array_values($normalized);
    }

    public static function availableQuantityByResource(array $roomResources): array
    {
        $available = [];

        foreach ($roomResources as $resource) {
            if (!$resource instanceof RoomResource || !$resource->isAvailable()) {
                continue;
            }

            $resourceId = (int) $resource->get('resource_id');
            if ($resourceId <= 0) {
                continue;
            }

            $available[$resourceId] = ($available[$resourceId] ?? 0) + $resource->quantityValue();
        }

        return $available;
    }

    public static function satisfies(array $roomResources, array $requirements): bool
    {
        $available = self::availableQuantityByResource($roomResources);

        foreach (self::normalize($requirements) as $requirement) {
            $resourceId = (int) ($requirement['resource_id'] ?? 0);
            $requiredQuantity = (int) ($requirement['quantity'] ?? 0);

            if (($available[$resourceId] ?? 0) < $requiredQuantity) {
                return false;
            }
        }

        return true;
    }

    private static function extractRequirement(mixed $requirement, int|string $index, array $allRequirements): array
    {
        if (is_array($requirement)) {
            $resourceId = (int) ($requirement['resource_id'] ?? $requirement['id'] ?? 0);
            $quantity = (int) ($requirement['quantity'] ?? $requirement['requested_quantity'] ?? 1);

            return [$resourceId, max(1, $quantity)];
        }

        $resourceId = (int) $requirement;
        if ($resourceId <= 0) {
            return [0, 0];
        }

        $quantity = 1;
        if (isset($allRequirements['quantities']) && is_array($allRequirements['quantities'])) {
            $quantity = (int) ($allRequirements['quantities'][$index] ?? $allRequirements['quantities'][(string) $resourceId] ?? 1);
        }

        return [$resourceId, max(1, $quantity)];
    }
}
