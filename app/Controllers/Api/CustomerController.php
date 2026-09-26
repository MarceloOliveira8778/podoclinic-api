<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\CustomerModel;
use CodeIgniter\API\ResponseTrait;
use App\Entities\Customer;

class CustomerController extends BaseController
{
    use ResponseTrait;

    public function index()
    {
        $customerModel = new CustomerModel();

        $customers = $customerModel
            ->orderBy('name', 'ASC')
            ->findAll();

        $formattedCustomers = array_map(
            fn ($customer) => $this->formatCustomer($customer),
            $customers
        );

        return $this->respond($formattedCustomers);
    }

    public function show($id = null)
    {
        $customerModel = new CustomerModel();

        $customer = $customerModel->find($id);

        if ($customer === null) {
            return $this->failNotFound('Cliente não encontrado.');
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
            $customerRequest['phone'] = preg_replace(
                '/\D/',
                '',
                $customerRequest['phone']
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
}