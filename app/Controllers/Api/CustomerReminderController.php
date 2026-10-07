<?php

namespace App\Controllers\Api;

use App\Entities\CustomerReminder;
use App\Exceptions\ResourceNotFoundException;
use App\Controllers\BaseController;
use App\Exceptions\BusinessException;
use App\Services\CustomerReminderService;
use App\Services\CustomerReminderWhatsAppService;
use CodeIgniter\API\ResponseTrait;
use Throwable;

class CustomerReminderController extends BaseController
{
    use ResponseTrait;

    public function index()
    {
        try {
            $service =
                new CustomerReminderService();

            $reminders =
                $service->getReminders(
                    $this->request->getGet()
                );

            return $this->respond(
                $reminders
            );
        } catch (BusinessException $exception) {
            return $this->failValidationErrors(
                $exception->getMessage()
            );
        } catch (Throwable $exception) {
            log_message(
                'error',
                'Erro ao consultar lembretes: {message}',
                [
                    'message' =>
                        $exception->getMessage(),
                ]
            );

            return $this->failServerError(
                'Não foi possível consultar os lembretes.'
            );
        }
    }

    public function markAsContacted(int $id)
    {
        try {
            $service =
                new CustomerReminderService();

            $reminder =
                $service->markAsContacted($id);

            return $this->respond(
                $this->formatReminder(
                    $reminder
                )
            );
        } catch (ResourceNotFoundException $exception) {
            return $this->failNotFound(
                $exception->getMessage()
            );
        } catch (BusinessException $exception) {
            return $this->failValidationErrors(
                $exception->getMessage()
            );
        } catch (Throwable $exception) {
            log_message(
                'error',
                'Erro ao marcar lembrete como contatado: {message}',
                [
                    'message' =>
                        $exception->getMessage(),
                ]
            );

            return $this->failServerError(
                'Não foi possível marcar o lembrete como contatado.'
            );
        }
    }

    private function formatReminder(
        CustomerReminder $reminder
    ): array {
        return [
            'id' =>
                $reminder->id,

            'saleId' =>
                $reminder->sale_id,

            'customerId' =>
                $reminder->customer_id,

            'productId' =>
                $reminder->product_id,

            'averageDurationDaysSnapshot' =>
                $reminder->average_duration_days_snapshot,

            'estimatedReplacementDate' =>
                $reminder->estimated_replacement_date,

            'status' =>
                $reminder->status,

            'contactedAt' =>
                $reminder->contacted_at,

            'createdAt' =>
                $reminder->created_at,

            'updatedAt' =>
                $reminder->updated_at,
        ];
    }

    public function cancel(int $id)
    {
        try {
            $service = new CustomerReminderService();

            $reminder = $service->cancel($id);

            return $this->respond(
                $this->formatReminder($reminder)
            );
        } catch (ResourceNotFoundException $exception) {
            return $this->failNotFound(
                $exception->getMessage()
            );
        } catch (BusinessException $exception) {
            return $this->failValidationErrors(
                $exception->getMessage()
            );
        } catch (Throwable $exception) {
            log_message(
                'error',
                'Erro ao cancelar lembrete: {message}',
                [
                    'message' => $exception->getMessage(),
                ]
            );

            return $this->failServerError(
                'Não foi possível cancelar o lembrete.'
            );
        }
    }

    public function whatsAppLink(int $id)
    {
        try {
            $service = new CustomerReminderWhatsAppService();

            $result = $service->generateLink($id);

            return $this->respond($result);
        } catch (ResourceNotFoundException $exception) {
            return $this->failNotFound(
                $exception->getMessage()
            );
        } catch (BusinessException $exception) {
            return $this->failValidationErrors(
                $exception->getMessage()
            );
        } catch (Throwable $exception) {
            log_message(
                'error',
                'Erro ao gerar link do WhatsApp: {message}',
                [
                    'message' => $exception->getMessage(),
                ]
            );

            return $this->failServerError(
                'Não foi possível gerar o link do WhatsApp.'
            );
        }
    }
}