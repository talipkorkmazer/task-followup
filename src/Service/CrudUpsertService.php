<?php

namespace App\Service;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

/**
 * Class CrudUpsertService.
 */
abstract class CrudUpsertService
{
    protected PropertyAccessor $propertyAccessor;

    public function __construct()
    {
        $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
    }

    protected function patchScalars($existingDoc, $newDoc, array $scalars)
    {
        foreach ($scalars as $field) {
            $newValue = $this->propertyAccessor->getValue($newDoc, $field);
            if ($newValue !== $this->propertyAccessor->getValue($existingDoc, $field) && !is_null($newValue)) {
                $this->propertyAccessor->setValue($existingDoc, $field, $newValue);
            }
        }

        return $existingDoc;
    }
}