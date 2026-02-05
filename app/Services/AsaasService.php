<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

class AsaasService
{
    protected Client $client;
    protected string $accessToken;
    protected string $baseUrl;

    public function __construct()
    {
        // Pega o token e a URL base do .env para maior segurança e flexibilidade
        $this->accessToken = config('services.asaas.token');
        $this->baseUrl = config('services.asaas.url');

        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'headers' => [
                'accept' => 'application/json',
                'access_token' => $this->accessToken,
                'content-type' => 'application/json',
            ],
        ]);
    }

    /**
     * Cria um novo cliente no Asaas.
     *
     * @param array $customerData Um array com os dados do cliente (name, cpfCnpj, email, mobilePhone).
     * @return array|null O objeto do cliente criado ou null em caso de erro.
     */
    public function createCustomer(array $customerData): ?array
    {
        try {
            $response = $this->client->post('customers', [
                'json' => $customerData,
            ]);

            return json_decode($response->getBody()->getContents(), true);

        } catch (RequestException $e) {
            $errorBody = $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : null;
            Log::error('Erro ao criar cliente Asaas:', [
                'message' => $e->getMessage(),
                'request_body' => json_encode($customerData),
                'response_body' => $errorBody,
            ]);

            return [
                'error' => true,
                'response' => $errorBody ? json_decode($errorBody, true) : null
            ];
        }
    }


    /**
     * Cria uma cobrança (fatura ou pagamento) no Asaas.
     *
     * @param array $paymentData Um array com os dados da cobrança (billingType, value, dueDate, description, externalReference, customer).
     * @return array|null O objeto da cobrança criada ou null em caso de erro.
     */
    public function createPayment(array $paymentData): ?array
    {
        try {
            $response = $this->client->post('payments', [
                'json' => $paymentData,
            ]);

            return json_decode($response->getBody()->getContents(), true);

        } catch (RequestException $e) {
            Log::error('Erro ao criar cobrança Asaas:', [
                'message' => $e->getMessage(),
                'request_body' => json_encode($paymentData),
                'response_body' => $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : 'N/A',
                'stack_trace' => $e->getTraceAsString(),
            ]);
            return null;
        } catch (\Exception $e) {
            Log::error('Erro inesperado ao criar cobrança Asaas: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Cria uma assinatura no Asaas.
     *
     * @param array $subscriptionData Um array com os dados da assinatura (customer, billingType, value, nextDueDate, cycle, etc).
     * @return array|null O objeto da assinatura criada ou detalhes de erro.
     */
    public function createSubscription(array $subscriptionData): ?array
    {
        try {
            $response = $this->client->post('subscriptions', [
                'json' => $subscriptionData,
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            $errorBody = $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : null;
            Log::error('Erro ao criar assinatura Asaas:', [
                'message' => $e->getMessage(),
                'request_body' => json_encode($subscriptionData),
                'response_body' => $errorBody,
            ]);

            return [
                'error' => true,
                'response' => $errorBody ? json_decode($errorBody, true) : null,
            ];
        } catch (\Exception $e) {
            Log::error('Erro inesperado ao criar assinatura Asaas: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Atualiza uma assinatura no Asaas.
     *
     * @param string $subscriptionId Identificador da assinatura no Asaas.
     * @param array $subscriptionData Dados para atualizar a assinatura.
     * @return array|null O objeto da assinatura atualizada ou detalhes de erro.
     */
    public function updateSubscription(string $subscriptionId, array $subscriptionData): ?array
    {
        try {
            $response = $this->client->put("subscriptions/{$subscriptionId}", [
                'json' => $subscriptionData,
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            $errorBody = $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : null;
            Log::error('Erro ao atualizar assinatura Asaas:', [
                'message' => $e->getMessage(),
                'subscription_id' => $subscriptionId,
                'request_body' => json_encode($subscriptionData),
                'response_body' => $errorBody,
            ]);

            return [
                'error' => true,
                'response' => $errorBody ? json_decode($errorBody, true) : null,
            ];
        } catch (\Exception $e) {
            Log::error('Erro inesperado ao atualizar assinatura Asaas: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Retorna o link da cobrança de uma assinatura (invoiceUrl ou bankSlipUrl).
     *
     * @param string $subscriptionId Identificador da assinatura no Asaas.
     * @param array $query Filtros opcionais (ex: ['status' => 'PENDING']).
     * @return array|null Link da cobrança ou detalhes de erro.
     */
    public function getSubscriptionPaymentLink(string $subscriptionId, array $query = []): ?array
    {
        try {
            $response = $this->client->get("subscriptions/{$subscriptionId}/payments", [
                'query' => $query,
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            $payments = $data['data'] ?? [];

            foreach ($payments as $payment) {
                $invoiceUrl = $payment['invoiceUrl'] ?? null;
                $bankSlipUrl = $payment['bankSlipUrl'] ?? null;
                $link = $invoiceUrl ?: $bankSlipUrl;

                if ($link) {
                    return [
                        'invoice_url' => $link,
                        'payment_id' => $payment['id'] ?? null,
                        'payment' => $payment,
                    ];
                }
            }

            return [
                'error' => true,
                'message' => 'Nenhum link de cobrança encontrado para esta assinatura.',
                'response' => $data,
            ];
        } catch (RequestException $e) {
            $errorBody = $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : null;
            Log::error('Erro ao listar cobranças da assinatura Asaas:', [
                'message' => $e->getMessage(),
                'subscription_id' => $subscriptionId,
                'query' => $query,
                'response_body' => $errorBody,
            ]);

            return [
                'error' => true,
                'response' => $errorBody ? json_decode($errorBody, true) : null,
            ];
        } catch (\Exception $e) {
            Log::error('Erro inesperado ao listar cobranças da assinatura Asaas: ' . $e->getMessage());
            return null;
        }
    }

    // Você pode adicionar outros métodos aqui, como:
    // public function getCustomer(string $customerId): ?array
    // public function updateCustomer(string $customerId, array $data): ?array
    // public function getPayment(string $paymentId): ?array
    // public function refundPayment(string $paymentId, float $value = null): ?array
}
