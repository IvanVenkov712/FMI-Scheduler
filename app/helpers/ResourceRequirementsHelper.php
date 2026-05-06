<?php
namespace App\Helpers;
class ResourceRequirementsHelper
{
    public static function normalize_requirements($requirements)
    {
        if ($requirements === null || !is_array($requirements)) {
            return null;
        }

        $normalized_requirements = [];
        foreach ($requirements as $requirement) {
            $resource_id = ResourceRequirementsHelper::read_requirement_value($requirement, 'resource_id');
            $required_quantity = ResourceRequirementsHelper::read_requirement_value($requirement, 'required_quantity');

            if (!is_numeric($resource_id) || !is_numeric($required_quantity)) {
                return null;
            }

            $required_quantity = (int) $required_quantity;
            if ($required_quantity < 0) {
                return null;
            }

            if ($required_quantity === 0) {
                continue;
            }

            $normalized_requirement = new \stdClass();
            $normalized_requirement->resource_id = (int) $resource_id;
            $normalized_requirement->required_quantity = $required_quantity;
            $normalized_requirement->notes = ResourceRequirementsHelper::read_requirement_value($requirement, 'notes') ?? '';
            $normalized_requirements[] = $normalized_requirement;
        }

        return $normalized_requirements;
    }

    private static function read_requirement_value($requirement, $field)
    {
        if (is_object($requirement) && property_exists($requirement, $field)) {
            return $requirement->{$field};
        }

        if (is_array($requirement) && array_key_exists($field, $requirement)) {
            return $requirement[$field];
        }

        return null;
    }
}
