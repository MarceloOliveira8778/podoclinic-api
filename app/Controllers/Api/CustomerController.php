<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Entities\Customer;
use App\Models\CustomerModel;
use CodeIgniter\API\ResponseTrait;
use App\Services\CustomerService;
use App\Exceptions\BusinessException;
use Throwable;

class CustomerController extends BaseController
{
    use ResponseTrait;

    public function index()
    {
        $customerModel = new CustomerModel();

        $name = trim((string) $this->request->getGet('name'));
        $phone = $this->normalizePhone(
            (string) $this->request->getGet('phone')
        );

        if ($name !== '') {
            $customerModel->like('name', $name);
        }

        if ($phone !== '') {
            $customerModel->like('phone', $phone);
        }

        $customers = $customerModel
            ->orderBy('name', 'ASC')
            ->findAll();

        $formattedCustomers = array_map(
            fn (Customer $customer) => $this->formatCustomer($customer),
            $customers
        );

        return $this->respond($formattedCustomers);
    }

    public function show($id = null)
    {
        $customerModel = new CustomerModel();

        $customer = $customerModel->find($id);

        if ($customer === null) {
            return $this->failNotFound(
                'Cliente não encontrado.'
            );
        }

        return $this->respond(
            $this->formatCustomer($customer)
        );
    }

    public function create()
    {
        $customerRequest = $this->request->getJSON(true);

        if (!is_array($customerRequest)) {
            return $this->failValidationErrors(
                'Dados da requisição inválidos.'
            );
        }

        if (isset($customerRequest['phone'])) {
            $customerRequest['phone'] = $this->normalizePhone(
                (string) $customerRequest['phone']
            );
        }

        $customerModel = new CustomerModel();

        if (!$customerModel->insert($customerRequest)) {
            return $this->failValidationErrors(
                $customerModel->errors()
            );
        }

        $customerId = $customerModel->getInsertID();

        $customer = $customerModel->find($customerId);

        return $this->respondCreated(
            $this->formatCustomer($customer)
        );
    }

    public function update($id = null)
    {
        $customerModel = new CustomerModel();

        $customer = $customerModel->find($id);

        if ($customer === null) {
            return $this->failNotFound(
                'Cliente não encontrado.'
            );
        }

        $customerRequest = $this->request->getJSON(true);

        if (!is_array($customerRequest)) {
            return $this->failValidationErrors(
                'Dados da requisição inválidos.'
            );
        }

        if (isset($customerRequest['phone'])) {
            $customerRequest['phone'] = $this->normalizePhone(
                (string) $customerRequest['phone']
            );
        }

        /*
         * isActive pertence ao contrato da API.
         * is_active pertence ao banco de dados.
         */
        if (array_key_exists('isActive', $customerRequest)) {
            $customerRequest['is_active'] = $customerRequest['isActive'];
            unset($customerRequest['isActive']);
        }

        if (!$customerModel->update($id, $customerRequest)) {
            return $this->failValidationErrors(
                $customerModel->errors()
            );
        }

        $updatedCustomer = $customerModel->find($id);

        return $this->respond(
            $this->formatCustomer($updatedCustomer)
        );
    }

    public function activate($id = null)
    {
        return $this->changeActiveStatus($id, true);
    }

    public function deactivate($id = null)
    {
        return $this->changeActiveStatus($id, false);
    }

    private function changeActiveStatus(
        $id,
        bool $isActive
    ) {
        $customerModel = new CustomerModel();

        $customer = $customerModel->find($id);

        if ($customer === null) {
            return $this->failNotFound(
                'Cliente não encontrado.'
            );
        }

        if (!$customerModel->update($id, [
            'is_active' => $isActive,
        ])) {
            return $this->failServerError(
                'Não foi possível alterar a situação do cliente.'
            );
        }

        $updatedCustomer = $customerModel->find($id);

        return $this->respond(
            $this->formatCustomer($updatedCustomer)
        );
    }

    private function normalizePhone(string $phone): string
    {
        return preg_replace('/\D/', '', $phone) ?? '';
    }

    private function formatCustomer(Customer $customer): array
    {
        return [
            'id'        => $customer->id,
            'name'      => $customer->name,
            'phone'     => $customer->phone,
            'isActive'  => $customer->is_active,
            'createdAt' => $customer->created_at,
            'updatedAt' => $customer->updated_at,
        ];
    }

    public function lookup()
    {
        try {
            $search = $this->request->getGet('search');

            if (!is_string($search)) {
                return $this->failValidationErrors(
                    'Informe um termo válido para pesquisar clientes.'
                );
            }

            $service = new CustomerService();

            $customers = $service->lookup($search);

            return $this->respond($customers);
        } catch (BusinessException $exception) {
            return $this->failValidationErrors(
                $exception->getMessage()
            );
        } catch (Throwable $exception) {
            log_message(
                'error',
                'Erro ao pesquisar clientes: {message}',
                [
                    'message' => $exception->getMessage(),
                ]
            );

            return $this->failServerError(
                'Não foi possível pesquisar clientes.'
            );
        }
    }
}