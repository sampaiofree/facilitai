<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Psr\Http\Message\ResponseInterface;

class UazapiGruposService
{
    protected string $baseUrl;
    protected Client $client;

    public function __construct(?Client $client = null)
    {
        $this->baseUrl = (string) config('services.uazapi.url');
        $baseUri = $this->baseUrl !== '' ? $this->baseUrl : 'https://free.uazapi.com';

        $this->client = $client ?? new Client([
            'base_uri' => $baseUri,
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    public function listGroups(string $token, bool $force = false, bool $noParticipants = false): array
    {
        $normalizedToken = trim($token);
        if ($normalizedToken === '') {
            return $this->validationFailed('listGroups', [
                'token' => ['The token field is required.'],
            ]);
        }

        try {
            $response = $this->client->get('/group/list', [
                'headers' => $this->instanceHeaders($normalizedToken),
                'query' => [
                    'force' => $force,
                    'noparticipants' => $noParticipants,
                ],
            ]);

            return $this->successResponse($response);
        } catch (RequestException $exception) {
            return $this->handleRequestException('listGroups', $exception);
        } catch (\Throwable $exception) {
            return $this->handleUnexpectedError('listGroups', $exception);
        }
    }

    public function listGroupsPaginated(string $token, array $filters = []): array
    {
        $normalizedToken = trim($token);
        if ($normalizedToken === '') {
            return $this->validationFailed('listGroupsPaginated', [
                'token' => ['The token field is required.'],
            ]);
        }

        $validator = Validator::make($filters, [
            'page' => ['sometimes', 'integer', 'min:1'],
            'pageSize' => ['sometimes', 'integer', 'min:1', 'max:1000'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:1000'],
            'offset' => ['sometimes', 'integer', 'min:0'],
            'search' => ['sometimes', 'string'],
            'force' => ['sometimes', 'boolean'],
            'noParticipants' => ['sometimes', 'boolean'],
        ]);

        if ($validator->fails()) {
            return $this->validationFailed('listGroupsPaginated', $validator->errors()->toArray());
        }

        try {
            $response = $this->client->post('/group/list', [
                'headers' => $this->instanceHeaders($normalizedToken),
                'json' => $this->removeEmptyValues($validator->validated()),
            ]);

            return $this->successResponse($response);
        } catch (RequestException $exception) {
            return $this->handleRequestException('listGroupsPaginated', $exception);
        } catch (\Throwable $exception) {
            return $this->handleUnexpectedError('listGroupsPaginated', $exception);
        }
    }

    public function getGroupInfo(string $token, string $groupJid, array $options = []): array
    {
        $normalizedToken = trim($token);
        if ($normalizedToken === '') {
            return $this->validationFailed('getGroupInfo', [
                'token' => ['The token field is required.'],
            ]);
        }

        $validator = Validator::make(array_merge($options, [
            'groupjid' => trim($groupJid),
        ]), [
            'groupjid' => ['required', 'string', 'regex:/^[0-9]+@g\.us$/'],
            'getInviteLink' => ['sometimes', 'boolean'],
            'getRequestsParticipants' => ['sometimes', 'boolean'],
            'force' => ['sometimes', 'boolean'],
        ]);

        if ($validator->fails()) {
            return $this->validationFailed('getGroupInfo', $validator->errors()->toArray());
        }

        try {
            $response = $this->client->post('/group/info', [
                'headers' => $this->instanceHeaders($normalizedToken),
                'json' => $validator->validated(),
            ]);

            return $this->successResponse($response);
        } catch (RequestException $exception) {
            return $this->handleRequestException('getGroupInfo', $exception);
        } catch (\Throwable $exception) {
            return $this->handleUnexpectedError('getGroupInfo', $exception);
        }
    }

    public function getGroupInviteInfo(string $token, string $inviteCodeOrUrl): array
    {
        $normalizedToken = trim($token);
        if ($normalizedToken === '') {
            return $this->validationFailed('getGroupInviteInfo', [
                'token' => ['The token field is required.'],
            ]);
        }

        $validator = Validator::make([
            'invitecode' => trim($inviteCodeOrUrl),
        ], [
            'invitecode' => [
                'required',
                'string',
                'max:2048',
                'regex:/^(https?:\/\/chat\.whatsapp\.com\/(?:invite\/)?[A-Za-z0-9]+\/?(?:\?.*)?|[A-Za-z0-9]+)$/i',
            ],
        ]);

        if ($validator->fails()) {
            return $this->validationFailed('getGroupInviteInfo', $validator->errors()->toArray());
        }

        try {
            $response = $this->client->post('/group/inviteInfo', [
                'headers' => $this->instanceHeaders($normalizedToken),
                'json' => $validator->validated(),
            ]);

            return $this->successResponse($response);
        } catch (RequestException $exception) {
            return $this->handleRequestException('getGroupInviteInfo', $exception);
        } catch (\Throwable $exception) {
            return $this->handleUnexpectedError('getGroupInviteInfo', $exception);
        }
    }

    public function updateGroupDescription(string $token, string $groupJid, string $description): array
    {
        $normalizedToken = trim($token);
        if ($normalizedToken === '') {
            return $this->validationFailed('updateGroupDescription', [
                'token' => ['The token field is required.'],
            ]);
        }

        $validator = Validator::make([
            'groupjid' => trim($groupJid),
            'description' => trim($description),
        ], [
            'groupjid' => ['required', 'string', 'regex:/^[0-9]+@g\.us$/'],
            'description' => ['required', 'string', 'max:512'],
        ]);

        if ($validator->fails()) {
            return $this->validationFailed('updateGroupDescription', $validator->errors()->toArray());
        }

        try {
            $response = $this->client->post('/group/updateDescription', [
                'headers' => $this->instanceHeaders($normalizedToken),
                'json' => $validator->validated(),
            ]);

            return $this->successResponse($response);
        } catch (RequestException $exception) {
            return $this->handleRequestException('updateGroupDescription', $exception);
        } catch (\Throwable $exception) {
            return $this->handleUnexpectedError('updateGroupDescription', $exception);
        }
    }

    public function updateGroupImage(string $token, string $groupJid, string $image): array
    {
        $normalizedToken = trim($token);
        if ($normalizedToken === '') {
            return $this->validationFailed('updateGroupImage', [
                'token' => ['The token field is required.'],
            ]);
        }

        $validator = Validator::make([
            'groupjid' => trim($groupJid),
            'image' => trim($image),
        ], [
            'groupjid' => ['required', 'string', 'regex:/^[0-9]+@g\.us$/'],
            'image' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return $this->validationFailed('updateGroupImage', $validator->errors()->toArray());
        }

        try {
            $response = $this->client->post('/group/updateImage', [
                'headers' => $this->instanceHeaders($normalizedToken),
                'json' => $validator->validated(),
            ]);

            return $this->successResponse($response);
        } catch (RequestException $exception) {
            return $this->handleRequestException('updateGroupImage', $exception);
        } catch (\Throwable $exception) {
            return $this->handleUnexpectedError('updateGroupImage', $exception);
        }
    }

    public function updateGroupName(string $token, string $groupJid, string $name): array
    {
        $normalizedToken = trim($token);
        if ($normalizedToken === '') {
            return $this->validationFailed('updateGroupName', [
                'token' => ['The token field is required.'],
            ]);
        }

        $validator = Validator::make([
            'groupjid' => trim($groupJid),
            'name' => trim($name),
        ], [
            'groupjid' => ['required', 'string', 'regex:/^[0-9]+@g\.us$/'],
            'name' => ['required', 'string', 'min:1', 'max:25'],
        ]);

        if ($validator->fails()) {
            return $this->validationFailed('updateGroupName', $validator->errors()->toArray());
        }

        try {
            $response = $this->client->post('/group/updateName', [
                'headers' => $this->instanceHeaders($normalizedToken),
                'json' => $validator->validated(),
            ]);

            return $this->successResponse($response);
        } catch (RequestException $exception) {
            return $this->handleRequestException('updateGroupName', $exception);
        } catch (\Throwable $exception) {
            return $this->handleUnexpectedError('updateGroupName', $exception);
        }
    }

    public function sendTextToGroup(string $token, string $groupJid, string $text, array $options = []): array
    {
        $normalizedToken = trim($token);
        if ($normalizedToken === '') {
            return $this->validationFailed('sendTextToGroup', [
                'token' => ['The token field is required.'],
            ]);
        }

        $validator = Validator::make([
            'groupjid' => trim($groupJid),
            'text' => trim($text),
        ], [
            'groupjid' => ['required', 'string', 'regex:/^[0-9]+@g\.us$/'],
            'text' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return $this->validationFailed('sendTextToGroup', $validator->errors()->toArray());
        }

        $validated = $validator->validated();
        $payload = array_merge(
            $this->sanitizeSendOptions($options, ['number', 'text']),
            [
                'number' => $validated['groupjid'],
                'text' => $validated['text'],
            ]
        );

        try {
            $response = $this->client->post('/send/text', [
                'headers' => $this->instanceHeaders($normalizedToken),
                'json' => $payload,
            ]);

            return $this->successResponse($response);
        } catch (RequestException $exception) {
            return $this->handleRequestException('sendTextToGroup', $exception);
        } catch (\Throwable $exception) {
            return $this->handleUnexpectedError('sendTextToGroup', $exception);
        }
    }

    public function sendMediaToGroup(string $token, string $groupJid, string $type, string $file, array $options = []): array
    {
        $normalizedToken = trim($token);
        if ($normalizedToken === '') {
            return $this->validationFailed('sendMediaToGroup', [
                'token' => ['The token field is required.'],
            ]);
        }

        $validator = Validator::make([
            'groupjid' => trim($groupJid),
            'type' => trim($type),
            'file' => trim($file),
        ], [
            'groupjid' => ['required', 'string', 'regex:/^[0-9]+@g\.us$/'],
            'type' => ['required', 'string', 'in:image,video,document,audio'],
            'file' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return $this->validationFailed('sendMediaToGroup', $validator->errors()->toArray());
        }

        $validated = $validator->validated();
        $payload = array_merge(
            $this->sanitizeSendOptions($options, ['number', 'type', 'file']),
            [
                'number' => $validated['groupjid'],
                'type' => $validated['type'],
                'file' => $validated['file'],
            ]
        );

        try {
            $response = $this->client->post('/send/media', [
                'headers' => $this->instanceHeaders($normalizedToken),
                'json' => $payload,
            ]);

            return $this->successResponse($response);
        } catch (RequestException $exception) {
            return $this->handleRequestException('sendMediaToGroup', $exception);
        } catch (\Throwable $exception) {
            return $this->handleUnexpectedError('sendMediaToGroup', $exception);
        }
    }

    protected function parseJson(?string $payload): mixed
    {
        if ($payload === null) {
            return null;
        }

        $decoded = json_decode($payload, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : $payload;
    }

    protected function successResponse(ResponseInterface $response): array
    {
        $payload = (string) $response->getBody();
        $decoded = $this->parseJson($payload);

        return is_array($decoded) ? $decoded : ['body' => $decoded];
    }

    protected function handleRequestException(string $context, RequestException $exception): array
    {
        $status = $exception->hasResponse() ? $exception->getResponse()->getStatusCode() : 0;
        $body = $exception->hasResponse() ? (string) $exception->getResponse()->getBody() : null;

        Log::channel('uazapi')->error("UazapiGruposService::{$context} request failed", [
            'status' => $status,
            'body' => $body,
            'exception' => $exception->getMessage(),
        ]);

        return [
            'error' => true,
            'status' => $status,
            'body' => $this->parseJson($body),
        ];
    }

    protected function handleUnexpectedError(string $context, \Throwable $exception): array
    {
        Log::channel('uazapi')->error("UazapiGruposService::{$context} unexpected error", [
            'exception' => $exception->getMessage(),
        ]);

        return [
            'error' => true,
            'status' => 0,
            'body' => $exception->getMessage(),
        ];
    }

    protected function instanceHeaders(string $token): array
    {
        return [
            'token' => $token,
        ];
    }

    private function validationFailed(string $context, array $errors): array
    {
        Log::channel('uazapi')->error("UazapiGruposService::{$context} validation failed", [
            'errors' => $errors,
        ]);

        return [
            'error' => true,
            'status' => 422,
            'body' => $errors,
        ];
    }

    private function removeEmptyValues(array $payload): array
    {
        return array_filter($payload, static function ($value) {
            if (is_string($value)) {
                return trim($value) !== '';
            }

            return $value !== null;
        });
    }

    private function sanitizeSendOptions(array $options, array $restrictedKeys): array
    {
        $payload = $this->removeEmptyValues($options);
        foreach ($restrictedKeys as $key) {
            unset($payload[$key]);
        }

        return $payload;
    }
}
