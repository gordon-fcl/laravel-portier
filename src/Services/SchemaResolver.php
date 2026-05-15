<?php

namespace Portier\Services;

class SchemaResolver
{
    /**
     * Resolve the schema config into a flat list of permission names.
     *
     * @param  array<int|string, string|array<int|string, mixed>>|null  $schema
     * @return list<string>
     */
    public function resolve(?array $schema = null): array
    {
        $schema ??= config('portier.schema', []);

        $permissions = [];

        foreach ($schema as $key => $value) {
            if (is_string($key) && is_array($value)) {
                foreach ($value as $childKey => $childValue) {
                    if (is_string($childKey) && is_array($childValue)) {
                        // Nested group: 'posts' => ['comments' => ['create', 'delete']]
                        foreach ($this->resolve([$childKey => $childValue]) as $nested) {
                            $permissions[] = $key.'.'.$nested;
                        }
                    } elseif (is_string($childValue)) {
                        $permissions[] = $key.'.'.$childValue;
                    }
                }
            } elseif (is_string($value)) {
                // Flat string entry
                $permissions[] = $value;
            }
        }

        return array_values(array_unique($permissions));
    }
}
