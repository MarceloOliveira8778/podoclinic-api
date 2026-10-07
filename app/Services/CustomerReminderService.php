<?php

namespace App\Services;

use App\Entities\CustomerReminder;
use App\Exceptions\ResourceNotFoundException;
use App\Models\CustomerReminderModel;
use App\Exceptions\BusinessException;
use CodeIgniter\Database\BaseBuilder;
use DateTimeImmutable;

class CustomerReminderService
{
    private const STATUSES = [
        'PENDING',
        'CONTACTED',
        'CANCELLED',
    ];

    private const TIMINGS = [
        'UPCOMING',
        'DUE',
        'OVERDUE',
    ];

    public function getReminders(array $filters = []): array
    {
        $status = $this->normalizeStatus(
            $filters['status'] ?? null
        );

        $timing = $this->normalizeTiming(
            $filters['timing'] ?? null
        );

        $customerId = $this->normalizeOptionalId(
            $filters['customerId'] ?? null,
            'cliente'
        );

        $productId = $this->normalizeOptionalId(
            $filters['productId'] ?? null,
            'produto'
        );

        $today = (new DateTimeImmutable('today'))
            ->format('Y-m-d');

        $db = db_connect();

        /** @var BaseBuilder $builder */
        $builder = $db
            ->table('customer_reminder cr')
            ->select([
                'cr.id',
                'cr.sale_id',
                'cr.customer_id',
                'c.name AS customer_name',
                'c.phone AS customer_phone',
                'cr.product_id',
                'p.name AS product_name',
                'cr.average_duration_days_snapshot',
                'cr.estimated_replacement_date',
                'cr.status',
                'cr.contacted_at',
                'cr.created_at',
                'cr.updated_at',
            ])
            ->join(
                'customer c',
                'c.id = cr.customer_id'
            )
            ->join(
                'product p',
                'p.id = cr.product_id'
            );

        if ($status !== null) {
            $builder->where(
                'cr.status',
                $status
            );
        }

        if ($customerId !== null) {
            $builder->where(
                'cr.customer_id',
                $customerId
            );
        }

        if ($productId !== null) {
            $builder->where(
                'cr.product_id',
                $productId
            );
        }

        $this->applyTimingFilter(
            $builder,
            $timing,
            $today
        );

        $rows = $builder
            ->orderBy(
                'cr.estimated_replacement_date',
                'ASC'
            )
            ->orderBy(
                'cr.id',
                'ASC'
            )
            ->get()
            ->getResultArray();

        return array_map(
            fn (array $row): array =>
                $this->formatReminder(
                    $row,
                    $today
                ),
            $rows
        );
    }

    private function applyTimingFilter(
        BaseBuilder $builder,
        ?string $timing,
        string $today
    ): void {
        if ($timing === null) {
            return;
        }

        switch ($timing) {
            case 'UPCOMING':
                $builder->where(
                    'cr.estimated_replacement_date >',
                    $today
                );
                break;

            case 'DUE':
                $builder->where(
                    'cr.estimated_replacement_date',
                    $today
                );
                break;

            case 'OVERDUE':
                $builder->where(
                    'cr.estimated_replacement_date <',
                    $today
                );
                break;
        }
    }

    private function formatReminder(
        array $row,
        string $today
    ): array {
        return [
            'id' =>
                (int) $row['id'],

            'saleId' =>
                (int) $row['sale_id'],

            'customer' => [
                'id' =>
                    (int) $row['customer_id'],

                'name' =>
                    $row['customer_name'],

                'phone' =>
                    $row['customer_phone'],
            ],

            'product' => [
                'id' =>
                    (int) $row['product_id'],

                'name' =>
                    $row['product_name'],
            ],

            'averageDurationDaysSnapshot' =>
                (int) $row[
                    'average_duration_days_snapshot'
                ],

            'estimatedReplacementDate' =>
                $row[
                    'estimated_replacement_date'
                ],

            'status' =>
                $row['status'],

            'timing' =>
                $this->calculateTiming(
                    $row[
                        'estimated_replacement_date'
                    ],
                    $today
                ),

            'contactedAt' =>
                $row['contacted_at'],

            'createdAt' =>
                $row['created_at'],

            'updatedAt' =>
                $row['updated_at'],
        ];
    }

    private function calculateTiming(
        string $estimatedDate,
        string $today
    ): string {
        if ($estimatedDate < $today) {
            return 'OVERDUE';
        }

        if ($estimatedDate === $today) {
            return 'DUE';
        }

        return 'UPCOMING';
    }

    private function normalizeStatus(
        mixed $value
    ): ?string {
        if (
            $value === null
            || $value === ''
        ) {
            return null;
        }

        $status = strtoupper(
            trim((string) $value)
        );

        if (
            !in_array(
                $status,
                self::STATUSES,
                true
            )
        ) {
            throw new BusinessException(
                'Status de lembrete inválido.'
            );
        }

        return $status;
    }

    private function normalizeTiming(
        mixed $value
    ): ?string {
        if (
            $value === null
            || $value === ''
        ) {
            return null;
        }

        $timing = strtoupper(
            trim((string) $value)
        );

        if (
            !in_array(
                $timing,
                self::TIMINGS,
                true
            )
        ) {
            throw new BusinessException(
                'Situação de vencimento inválida.'
            );
        }

        return $timing;
    }

    private function normalizeOptionalId(
        mixed $value,
        string $field
    ): ?int {
        if (
            $value === null
            || $value === ''
        ) {
            return null;
        }

        $id = filter_var(
            $value,
            FILTER_VALIDATE_INT,
            [
                'options' => [
                    'min_range' => 1,
                ],
            ]
        );

        if ($id === false) {
            throw new BusinessException(
                "Informe um {$field} válido."
            );
        }

        return $id;
    }

    public function markAsContacted(
        int $reminderId
    ): CustomerReminder {
        if ($reminderId <= 0) {
            throw new BusinessException(
                'Informe um lembrete válido.'
            );
        }

        $db = db_connect();

        $db->transBegin();

        try {
            /*
            * Bloqueia o registro enquanto fazemos
            * a alteração de status.
            */
            $reminder = $db
                ->query(
                    '
                    SELECT
                        id,
                        status,
                        contacted_at
                    FROM customer_reminder
                    WHERE id = ?
                    FOR UPDATE
                    ',
                    [
                        $reminderId,
                    ]
                )
                ->getRowArray();

            if ($reminder === null) {
                throw new ResourceNotFoundException(
                    'Lembrete não encontrado.'
                );
            }

            if ($reminder['status'] === 'CONTACTED') {
                throw new BusinessException(
                    'O lembrete já foi marcado como contatado.'
                );
            }

            if ($reminder['status'] === 'CANCELLED') {
                throw new BusinessException(
                    'Um lembrete cancelado não pode ser marcado como contatado.'
                );
            }

            $contactedAt = date('Y-m-d H:i:s');

            $model = new CustomerReminderModel();

            $updated = $model->update(
                $reminderId,
                [
                    'status'       => 'CONTACTED',
                    'contacted_at' => $contactedAt,
                ]
            );

            if (!$updated) {
                throw new BusinessException(
                    'Não foi possível marcar o lembrete como contatado.'
                );
            }

            if ($db->transStatus() === false) {
                throw new BusinessException(
                    'Não foi possível marcar o lembrete como contatado.'
                );
            }

            $db->transCommit();

            $updatedReminder = $model->find(
                $reminderId
            );

            if (
                !$updatedReminder
                instanceof CustomerReminder
            ) {
                throw new ResourceNotFoundException(
                    'Lembrete não encontrado após a atualização.'
                );
            }

            return $updatedReminder;
        } catch (\Throwable $exception) {
            $db->transRollback();

            throw $exception;
        }
    }

    public function cancel(int $reminderId): CustomerReminder
    {
        if ($reminderId <= 0) {
            throw new BusinessException(
                'Informe um lembrete válido.'
            );
        }

        $db = db_connect();

        $db->transBegin();

        try {
            // Bloqueia o registro durante a alteração.
            $reminder = $db->query(
                '
                SELECT
                    id,
                    status
                FROM customer_reminder
                WHERE id = ?
                FOR UPDATE
                ',
                [$reminderId]
            )->getRowArray();

            if ($reminder === null) {
                throw new ResourceNotFoundException(
                    'Lembrete não encontrado.'
                );
            }

            if ($reminder['status'] === 'CANCELLED') {
                throw new BusinessException(
                    'O lembrete já está cancelado.'
                );
            }

            if ($reminder['status'] === 'CONTACTED') {
                throw new BusinessException(
                    'Um lembrete já contatado não pode ser cancelado.'
                );
            }

            $model = new CustomerReminderModel();

            $updated = $model->update(
                $reminderId,
                [
                    'status'       => 'CANCELLED',
                    'contacted_at' => null,
                ]
            );

            if (!$updated || $db->transStatus() === false) {
                throw new BusinessException(
                    'Não foi possível cancelar o lembrete.'
                );
            }

            $db->transCommit();

            $cancelledReminder = $model->find($reminderId);

            if (!$cancelledReminder instanceof CustomerReminder) {
                throw new ResourceNotFoundException(
                    'Lembrete não encontrado após o cancelamento.'
                );
            }

            return $cancelledReminder;
        } catch (\Throwable $exception) {
            $db->transRollback();

            throw $exception;
        }
    }
}