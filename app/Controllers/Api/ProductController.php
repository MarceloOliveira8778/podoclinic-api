<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Entities\Product;
use App\Models\ProductModel;
use App\Services\ProductService;
use CodeIgniter\API\ResponseTrait;

class ProductController extends BaseController
{
    use ResponseTrait;

    public function index()
    {
        $productModel = new ProductModel();

        $name = trim((string) $this->request->getGet('name'));
        $type = strtoupper(
            trim((string) $this->request->getGet('type'))
        );

        if ($name !== '') {
            $productModel->like('name', $name);
        }

        if ($type !== '') {
            $productModel->where('type', $type);
        }

        $products = $productModel
            ->orderBy('name', 'ASC')
            ->findAll();

        $formattedProducts = array_map(
            fn (Product $product) => $this->formatProduct($product),
            $products
        );

        return $this->respond($formattedProducts);
    }

    public function show($id = null)
    {
        $productModel = new ProductModel();

        $product = $productModel->find($id);

        if ($product === null) {
            return $this->failNotFound(
                'Produto não encontrado.'
            );
        }

        return $this->respond(
            $this->formatProduct($product)
        );
    }

    public function create()
    {
        $productRequest = $this->request->getJSON(true);

        if (!is_array($productRequest)) {
            return $this->failValidationErrors(
                'Dados da requisição inválidos.'
            );
        }

        if (array_key_exists('currentStock', $productRequest)) {
            return $this->failValidationErrors([
                'currentStock' =>
                    'O estoque atual não pode ser informado diretamente no cadastro do produto.',
            ]);
        }

        $productRequest = $this->mapApiRequest($productRequest);

        $productService = new ProductService();

        $businessRuleErrors = $productService->validateBusinessRules(
            $productRequest
        );

        if ($businessRuleErrors !== []) {
            return $this->failValidationErrors(
                $businessRuleErrors
            );
        }

        $productModel = new ProductModel();

        if (!$productModel->insert($productRequest)) {
            return $this->failValidationErrors(
                $productModel->errors()
            );
        }

        $productId = $productModel->getInsertID();

        $product = $productModel->find($productId);

        return $this->respondCreated(
            $this->formatProduct($product)
        );
    }

    public function update($id = null)
    {
        $productModel = new ProductModel();

        $product = $productModel->find($id);

        if ($product === null) {
            return $this->failNotFound(
                'Produto não encontrado.'
            );
        }

        $productRequest = $this->request->getJSON(true);

        if (!is_array($productRequest)) {
            return $this->failValidationErrors(
                'Dados da requisição inválidos.'
            );
        }

        /*
        * O estoque não pode ser alterado pelo cadastro do produto.
        */
        if (array_key_exists('currentStock', $productRequest)) {
            return $this->failValidationErrors([
                'currentStock' =>
                    'O estoque atual não pode ser alterado diretamente pelo cadastro do produto.',
            ]);
        }

        $productRequest = $this->mapApiRequest($productRequest);

        /*
        * Montamos o estado final do produto para validar
        * as regras de negócio antes da atualização.
        */
        $productData = [
            'type' => $productRequest['type'] ?? $product->type,
            'average_duration_days' =>
                array_key_exists('average_duration_days', $productRequest)
                    ? $productRequest['average_duration_days']
                    : $product->average_duration_days,
        ];

        $productService = new ProductService();

        $businessRuleErrors = $productService->validateBusinessRules(
            $productData
        );

        if ($businessRuleErrors !== []) {
            return $this->failValidationErrors(
                $businessRuleErrors
            );
        }

        if (!$productModel->update($id, $productRequest)) {
            return $this->failValidationErrors(
                $productModel->errors()
            );
        }

        $updatedProduct = $productModel->find($id);

        return $this->respond(
            $this->formatProduct($updatedProduct)
        );
    }

    private function mapApiRequest(array $productRequest): array
    {
        $fieldMappings = [
            'packageQuantity'      => 'package_quantity',
            'minimumStock'         => 'minimum_stock',
            'averageDurationDays'  => 'average_duration_days',
            'isActive'             => 'is_active',
        ];

        foreach ($fieldMappings as $apiField => $databaseField) {
            if (array_key_exists($apiField, $productRequest)) {
                $productRequest[$databaseField] =
                    $productRequest[$apiField];

                unset($productRequest[$apiField]);
            }
        }

        return $productRequest;
    }

    private function formatProduct(Product $product): array
    {
        return [
            'id'                  => $product->id,
            'name'                => $product->name,
            'type'                => $product->type,
            'unit'                => $product->unit,
            'packageQuantity'     => $product->package_quantity,
            'currentStock'        => $product->current_stock,
            'minimumStock'        => $product->minimum_stock,
            'averageDurationDays' => $product->average_duration_days,
            'isActive'            => $product->is_active,
            'createdAt'           => $product->created_at,
            'updatedAt'           => $product->updated_at,
        ];
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
        $productModel = new ProductModel();

        $product = $productModel->find($id);

        if ($product === null) {
            return $this->failNotFound(
                'Produto não encontrado.'
            );
        }

        if (!$productModel->update($id, [
            'is_active' => $isActive,
        ])) {
            return $this->failServerError(
                'Não foi possível alterar a situação do produto.'
            );
        }

        $updatedProduct = $productModel->find($id);

        return $this->respond(
            $this->formatProduct($updatedProduct)
        );
    }
}