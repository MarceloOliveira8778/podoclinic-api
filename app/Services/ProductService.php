<?php

namespace App\Services;

class ProductService
{
    public function validateBusinessRules(array $productRequest): array
    {
        $errors = [];

        $productType = $productRequest['type'] ?? null;
        $averageDurationDays = $productRequest['average_duration_days'] ?? null;

        if (
            $productType === 'RESALE'
            && empty($averageDurationDays)
        ) {
            $errors['averageDurationDays'] =
                'A duração média em dias é obrigatória para produtos de revenda.';
        }

        if (
            $productType === 'CONSUMPTION'
            && !empty($averageDurationDays)
        ) {
            $errors['averageDurationDays'] =
                'A duração média em dias não deve ser informada para materiais de consumo.';
        }

        return $errors;
    }
}