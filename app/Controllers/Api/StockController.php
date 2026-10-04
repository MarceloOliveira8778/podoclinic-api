<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Exceptions\BusinessException;
use App\Services\StockService;
use CodeIgniter\API\ResponseTrait;
use Throwable;

class StockController extends BaseController
{
    use ResponseTrait;

    public function index()
    {
        try {
            $service = new StockService();

            $stock = $service->getStock([
                'type' =>
                    $this->request->getGet('type'),

                'status' =>
                    $this->request->getGet('status'),
            ]);

            return $this->respond(
                $stock
            );
        } catch (
            BusinessException $exception
        ) {
            return $this->failValidationErrors(
                $exception->getMessage()
            );
        } catch (Throwable $exception) {
            log_message(
                'error',
                'Erro ao consultar estoque: {message}',
                [
                    'message' =>
                        $exception->getMessage(),
                ]
            );

            return $this->failServerError(
                'Não foi possível consultar o estoque.'
            );
        }
    }
}