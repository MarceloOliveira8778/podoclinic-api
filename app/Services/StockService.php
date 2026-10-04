<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use CodeIgniter\Database\BaseBuilder;

class StockService
{
    public function getStock(array $filters = []): array
    {
        $filters = $this->normalizeFilters($filters);

        $builder = db_connect()
            ->table('product p');

        $builder->select([
            'p.id',
            'p.name',
            'p.type',
            'p.unit',
            'p.package_quantity',
            'p.current_stock',
            'p.minimum_stock',
            'p.average_duration_days',
            'p.is_active',
        ]);

        /*
         * Para a tela operacional de estoque,
         * produtos inativos não devem aparecer.
         */
        $builder->where('p.is_active', 1);

        $this->applyFilters(
            $builder,
            $filters
        );

        $builder->orderBy('p.name', 'ASC');

        $rows = $builder
            ->get()
            ->getResultArray();

        return array_map(
            fn (array $row): array =>
                $this->formatStock($row),
            $rows
        );
    }

    private function normalizeFilters(
        array $filters
    ): array {
        $type = strtoupper(
            trim((string) ($filters['type'] ?? ''))
        );

        $status = strtoupper(
            trim((string) ($filters['status'] ?? ''))
        );

        if (
            $type !== ''
            && !in_array(
                $type,
                ['CONSUMPTION', 'RESALE'],
                true
            )
        ) {
            throw new BusinessException(
                'O tipo deve ser CONSUMPTION ou RESALE.'
            );
        }

        if (
            $status !== ''
            && !in_array(
                $status,
                ['NORMAL', 'LOW', 'ZERO'],
                true
            )
        ) {
            throw new BusinessException(
                'O status deve ser NORMAL, LOW ou ZERO.'
            );
        }

        return [
            'type'   => $type,
            'status' => $status,
        ];
    }

    private function applyFilters(
        BaseBuilder $builder,
        array $filters
    ): void {
        if ($filters['type'] !== '') {
            $builder->where(
                'p.type',
                $filters['type']
            );
        }

        if ($filters['status'] === 'ZERO') {
            $builder->where(
                'p.current_stock',
                0
            );
        }

        if ($filters['status'] === 'LOW') {
            $builder->where(
                'p.current_stock >',
                0
            );

            $builder->where(
                'p.current_stock <= p.minimum_stock',
                null,
                false
            );
        }

        if ($filters['status'] === 'NORMAL') {
            $builder->where(
                'p.current_stock > p.minimum_stock',
                null,
                false
            );
        }
    }

    private function formatStock(
        array $row
    ): array {
        $currentStock =
            (int) $row['current_stock'];

        $minimumStock =
            (int) $row['minimum_stock'];

        return [
            'productId' =>
                (int) $row['id'],

            'name' =>
                $row['name'],

            'type' =>
                $row['type'],

            'unit' =>
                $row['unit'],

            'packageQuantity' =>
                $row['package_quantity'] !== null
                    ? (int) $row['package_quantity']
                    : null,

            'currentStock' =>
                $currentStock,

            'minimumStock' =>
                $minimumStock,

            'averageDurationDays' =>
                $row['average_duration_days'] !== null
                    ? (int) $row['average_duration_days']
                    : null,

            'status' =>
                $this->getStockStatus(
                    $currentStock,
                    $minimumStock
                ),
        ];
    }

    private function getStockStatus(
        int $currentStock,
        int $minimumStock
    ): string {
        if ($currentStock === 0) {
            return 'ZERO';
        }

        if ($currentStock <= $minimumStock) {
            return 'LOW';
        }

        return 'NORMAL';
    }
}