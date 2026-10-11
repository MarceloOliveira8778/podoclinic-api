<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Exceptions\BusinessException;
use App\Exceptions\ResourceNotFoundException;
use App\Services\SaleCancellationService;
use CodeIgniter\API\ResponseTrait;
use Config\Services;
use Throwable;

class SaleCancellationController extends BaseController
{
    use ResponseTrait;

    public function cancel(int $id)
    {
        try {
            $data = $this->request->getJSON(true);

            if (!is_array($data)) {
                return $this->failValidationErrors(
                    'Informe os dados do cancelamento.'
                );
            }

            $cancellationType = $data['type'] ?? null;
            $reason = $data['reason'] ?? null;

            if (
                !is_string($cancellationType)
                || !is_string($reason)
            ) {
                return $this->failValidationErrors(
                    'Informe o tipo e a justificativa do cancelamento.'
                );
            }

            // Obtém o usuário autenticado pelo token Bearer.
            $authenticatedUserId = Services::authenticatedUser()
                ->getUserId();

            $service = new SaleCancellationService();

            $result = $service->cancel(
                $id,
                $authenticatedUserId,
                $cancellationType,
                $reason
            );

            return $this->respond($result);

        } 
        catch (ResourceNotFoundException $exception) {
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
                'Erro ao cancelar venda {saleId}: {message}',
                [
                    'saleId' => $id,
                    'message' => $exception->getMessage(),
                ]
            );

            return $this->failServerError(
                'Não foi possível cancelar a venda.'
            );
        }
    }
}