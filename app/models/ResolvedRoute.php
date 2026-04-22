<?php

declare(strict_types=1);

final class ResolvedRoute extends Route
{
    public static function fromArray(array $attributes): self
    {
        $route = new self();
        $route->attributes = $attributes;

        return $route;
    }
}
