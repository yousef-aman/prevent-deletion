<?php

namespace YousefAman\PreventDeletion\Traits;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Log;
use ReflectionClass;
use ReflectionMethod;
use YousefAman\PreventDeletion\Exceptions\PreventDeletionException;

trait PreventDeletionIfHasRelations
{
    protected static function bootPreventDeletionIfHasRelations()
    {
        static::deleting(function ($model) {
            $specificConditions = $model->getSpecificConditions();

            foreach ($specificConditions as $condition) {
                if ($condition['condition']) {
                    $message = $model->getDeletionMessage($condition['message']) ?? $condition['message'];
                    Log::warning('Deletion prevented: ' . $message);
                    throw new PreventDeletionException($message);
                }
            }

            $relationships = $model->getRelationshipsWithRecords();

            if (!empty($relationships)) {
                $message = 'Cannot delete this record because it has related records: ' . implode(', ', $relationships) . '.';
                $message = $model->getDeletionMessage($message) ?? $message;
                Log::warning('Deletion prevented: ' . $message);
                throw new PreventDeletionException($message);
            }
        });
    }

    protected function getRelationshipsWithRecords(): array
    {
        $relationships = [];

        $reflection = new ReflectionClass($this);
        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->class != get_class($this) ||
                !empty($method->getParameters()) ||
                $method->getName() == __FUNCTION__) {
                continue;
            }

            $returnType = $method->getReturnType();
            if ($returnType && $returnType instanceof \ReflectionNamedType) {
                $typeName = $returnType->getName();
                if (is_subclass_of($typeName, Relation::class)) {
                    $relationName = $method->getName();

                    // Check if this relationship is excluded
                    if (isset($this->excludedRelations) && in_array($relationName, $this->excludedRelations)) {
                        continue;
                    }

                    // If includedRelations is defined, only include those specified
                    if (isset($this->includedRelations) && !in_array($relationName, $this->includedRelations)) {
                        continue;
                    }

                    $relation = $this->$relationName();

                    if (!($relation instanceof BelongsTo) && $relation->exists()) {
                        $relationships[] = $relationName;
                    }
                }
            }
        }

        return $relationships;
    }

    protected function getSpecificConditions(): array
    {
        return method_exists($this, 'specificConditions') ? $this->specificConditions() : [];
    }

    public function getDeletionMessage($default)
    {
        return $this->deletionMessage ?? $default;
    }
}
