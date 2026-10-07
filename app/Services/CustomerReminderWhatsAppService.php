<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Exceptions\ResourceNotFoundException;
use App\Models\CustomerReminderModel;
use App\Models\CustomerModel;
use App\Models\ProductModel;

class CustomerReminderWhatsAppService
{
    public function generateLink(int $reminderId): array
    {
        if ($reminderId <= 0) {
            throw new BusinessException(
                'Informe um lembrete válido.'
            );
        }

        $reminderModel = new CustomerReminderModel();

        $reminder = $reminderModel->find($reminderId);

        if ($reminder === null) {
            throw new ResourceNotFoundException(
                'Lembrete não encontrado.'
            );
        }

        if ($reminder->status !== 'PENDING') {
            throw new BusinessException(
                'Somente lembretes pendentes podem gerar links de contato.'
            );
        }

        $customerModel = new CustomerModel();

        $customer = $customerModel->find(
            $reminder->customer_id
        );

        if ($customer === null) {
            throw new ResourceNotFoundException(
                'Cliente não encontrado.'
            );
        }

        if (!$customer->is_active) {
            throw new BusinessException(
                'Não é possível gerar contato para um cliente inativo.'
            );
        }

        $productModel = new ProductModel();

        $product = $productModel->find(
            $reminder->product_id
        );

        if ($product === null) {
            throw new ResourceNotFoundException(
                'Produto não encontrado.'
            );
        }

        $phone = preg_replace(
            '/\D/',
            '',
            (string) $customer->phone
        );

        if ($phone === null) {
            throw new BusinessException(
                'Não foi possível validar o telefone do cliente.'
            );
        }

        /*
         * Consideramos números brasileiros.
         *
         * 10 ou 11 dígitos:
         * DDD + número
         *
         * 12 ou 13 dígitos:
         * código do país (55) + DDD + número
         */
        if (in_array(strlen($phone), [10, 11], true)) {
            $phone = '55' . $phone;
        } elseif (
            in_array(strlen($phone), [12, 13], true)
            && str_starts_with($phone, '55')
        ) {
            // Número já contém o código do Brasil.
        } else {
            throw new BusinessException(
                'O telefone do cliente não possui um formato brasileiro válido.'
            );
        }

       $message = $this->buildMessage(
            (string) $customer->name,
            (string) $product->name,
            (string) $reminder->estimated_replacement_date
        );

        $url = 'https://wa.me/'
            . $phone
            . '?text='
            . rawurlencode($message);

        return [
            'reminderId' => (int) $reminder->id,
            'customer' => [
                'id' => (int) $customer->id,
                'name' => $customer->name,
            ],
            'product' => [
                'id' => (int) $product->id,
                'name' => $product->name,
            ],
            'phone' => $phone,
            'message' => $message,
            'whatsAppUrl' => $url,
        ];
    }

    private function buildMessage(
        string $customerFullName,
        string $productName,
        string $replacementDate
    ): string {
        $defaultTemplate =
            "Olá, {customerName}! Tudo bem?\n\n"
            . "Estamos entrando em contato para saber se você "
            . "precisa repor o produto {productName}.\n\n"
            . "Se precisar, é só nos chamar por aqui!";

        $template = env(
            'whatsapp.reminderMessage',
            $defaultTemplate
        );

        if (!is_string($template) || trim($template) === '') {
            $template = $defaultTemplate;
        }

        // Converte \n do .env em quebras de linha reais.
        $template = str_replace(
            ['\\r\\n', '\\n', '\\r'],
            ["\n", "\n", "\n"],
            $template
        );

        $customerFullName = trim($customerFullName);

        $nameParts = preg_split(
            '/\s+/',
            $customerFullName
        );

        $customerName = $nameParts[0] ?? '';

        $formattedDate = date(
            'd/m/Y',
            strtotime($replacementDate)
        );

        $variables = [
            '{customerName}'     => $customerName,
            '{customerFullName}' => $customerFullName,
            '{productName}'      => $productName,
            '{replacementDate}'  => $formattedDate,
        ];

        return strtr($template, $variables);
    }
}