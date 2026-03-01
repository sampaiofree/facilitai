<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class WhatsappCloudApiService
{
    private string $baseUrl;
    private string $version;
    private ?string $defaultPhoneNumberId;
    private ?string $defaultAccessToken;
    private int $timeout;
    private int $retryTimes;
    private int $retrySleepMs;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.whatsapp_cloud.base_url', 'https://graph.facebook.com'), '/');
        $this->version = trim((string) config('services.whatsapp_cloud.version', 'v23.0'));
        $this->defaultPhoneNumberId = $this->sanitizeConfigValue(config('services.whatsapp_cloud.phone_number_id'));
        $this->defaultAccessToken = $this->sanitizeConfigValue(config('services.whatsapp_cloud.access_token'));
        $this->timeout = max(3, (int) config('services.whatsapp_cloud.timeout', 15));
        $this->retryTimes = max(0, (int) config('services.whatsapp_cloud.retry_times', 2));
        $this->retrySleepMs = max(0, (int) config('services.whatsapp_cloud.retry_sleep_ms', 300));
    }

    /**
     * Endpoint Meta: POST /{PHONE_NUMBER_ID}/messages (type=text)
     */
    public function sendText(string $to, string $text, array $options = []): array
    {
        $normalizedTo = $this->normalizePhone($to);
        $body = trim($text);

        $validator = Validator::make([
            'to' => $normalizedTo,
            'text' => $body,
        ], [
            'to' => ['required', 'string', 'regex:/^\d{8,20}$/'],
            'text' => ['required', 'string', 'max:4096'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $normalizedTo,
            'type' => 'text',
            'text' => [
                'body' => $body,
                'preview_url' => (bool) ($options['preview_url'] ?? false),
            ],
        ];

        return $this->dispatchMessage($payload, $options, 'send_text', [
            'to' => $normalizedTo,
        ]);
    }

    /**
     * Endpoint Meta: POST /{PHONE_NUMBER_ID}/messages (type=audio)
     * Observação: a Cloud API envia áudio por "audio"; não há flag oficial separada para PTT.
     */
    public function sendAudioPtt(string $to, string $audio, array $options = []): array
    {
        $options = array_merge([
            'upload_before_send' => true,
            'fallback_to_link_on_upload_failure' => false,
            'audio_voice' => true,
            'audio_force_opus' => true,
        ], $options);

        return $this->sendMedia(
            'audio',
            $to,
            $audio,
            null,
            null,
            $options,
            'send_audio_ptt'
        );
    }

    /**
     * Endpoint Meta: POST /{PHONE_NUMBER_ID}/messages (type=image)
     */
    public function sendImage(string $to, string $image, ?string $caption = null, array $options = []): array
    {
        return $this->sendMedia(
            'image',
            $to,
            $image,
            $caption,
            null,
            $options,
            'send_image'
        );
    }

    /**
     * Endpoint Meta: POST /{PHONE_NUMBER_ID}/messages (type=video)
     */
    public function sendVideo(string $to, string $video, ?string $caption = null, array $options = []): array
    {
        return $this->sendMedia(
            'video',
            $to,
            $video,
            $caption,
            null,
            $options,
            'send_video'
        );
    }

    /**
     * Endpoint Meta: POST /{PHONE_NUMBER_ID}/messages (type=document)
     */
    public function sendDocumentPdf(string $to, string $document, ?string $filename = null, ?string $caption = null, array $options = []): array
    {
        $isUrl = $this->isHttpUrl($document);
        if ($isUrl && !$this->looksLikePdfUrl($document) && empty($options['allow_non_pdf_url'])) {
            return [
                'error' => true,
                'status' => 422,
                'body' => [
                    'document' => ['A URL informada não aparenta ser um PDF.'],
                ],
            ];
        }

        return $this->sendMedia(
            'document',
            $to,
            $document,
            $caption,
            $filename,
            $options,
            'send_document_pdf'
        );
    }

    /**
     * Endpoint Meta: POST /{PHONE_NUMBER_ID}/messages (type=template)
     */
    public function sendTemplateUtility(string $to, string $templateName, array $variables = [], array $options = []): array
    {
        $normalizedTo = $this->normalizePhone($to);
        $languageCode = trim((string) ($options['language_code'] ?? 'pt_BR'));
        $components = $this->buildTemplateComponents($variables, $options['components'] ?? null);

        $validator = Validator::make([
            'to' => $normalizedTo,
            'template_name' => $templateName,
            'language_code' => $languageCode,
            'components' => $components,
        ], [
            'to' => ['required', 'string', 'regex:/^\d{8,20}$/'],
            'template_name' => ['required', 'string', 'max:512'],
            'language_code' => ['required', 'string', 'max:32'],
            'components' => ['nullable', 'array'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        $templatePayload = [
            'name' => trim($templateName),
            'language' => [
                'code' => $languageCode,
            ],
        ];

        if (!empty($components)) {
            $templatePayload['components'] = $components;
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $normalizedTo,
            'type' => 'template',
            'template' => $templatePayload,
        ];

        return $this->dispatchMessage($payload, $options, 'send_template_utility', [
            'to' => $normalizedTo,
            'template_name' => trim($templateName),
        ]);
    }

    /**
     * Endpoint Meta: POST /{WHATSAPP_BUSINESS_ACCOUNT_ID}/message_templates
     */
    public function createMessageTemplate(string $businessAccountId, array $templatePayload, array $options = []): array
    {
        $businessAccountId = trim($businessAccountId);

        $validator = Validator::make([
            'business_account_id' => $businessAccountId,
            'name' => $templatePayload['name'] ?? null,
            'language' => $templatePayload['language'] ?? null,
            'category' => $templatePayload['category'] ?? null,
            'components' => $templatePayload['components'] ?? [],
        ], [
            'business_account_id' => ['required', 'string', 'regex:/^\d+$/', 'max:50'],
            'name' => ['required', 'string', 'max:512'],
            'language' => ['required', 'string', 'max:32'],
            'category' => ['required', 'string', 'max:32'],
            'components' => ['required', 'array', 'min:1'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        $credentials = $this->resolveAccessTokenOnly($options, 'create_message_template', [
            'business_account_id' => $businessAccountId,
            'name' => (string) $templatePayload['name'],
        ]);
        if (!empty($credentials['error'])) {
            return $credentials;
        }

        $url = sprintf(
            '%s/%s/%s/message_templates',
            $this->baseUrl,
            trim($this->version, '/'),
            $businessAccountId
        );

        return $this->dispatchGraphRequest(
            'POST',
            $url,
            $templatePayload,
            $credentials['access_token'],
            'create_message_template',
            [
                'business_account_id' => $businessAccountId,
                'name' => (string) $templatePayload['name'],
            ]
        );
    }

    /**
     * Endpoint Meta: POST /{MESSAGE_TEMPLATE_ID}
     */
    public function editMessageTemplate(string $messageTemplateId, array $templatePayload, array $options = []): array
    {
        $messageTemplateId = trim($messageTemplateId);

        $validator = Validator::make([
            'message_template_id' => $messageTemplateId,
            'components' => $templatePayload['components'] ?? [],
            'category' => $templatePayload['category'] ?? null,
        ], [
            'message_template_id' => ['required', 'string', 'max:80'],
            'components' => ['required', 'array', 'min:1'],
            'category' => ['nullable', 'string', 'max:32'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        $credentials = $this->resolveAccessTokenOnly($options, 'edit_message_template', [
            'message_template_id' => $messageTemplateId,
        ]);
        if (!empty($credentials['error'])) {
            return $credentials;
        }

        $url = sprintf(
            '%s/%s/%s',
            $this->baseUrl,
            trim($this->version, '/'),
            $messageTemplateId
        );

        return $this->dispatchGraphRequest(
            'POST',
            $url,
            $templatePayload,
            $credentials['access_token'],
            'edit_message_template',
            [
                'message_template_id' => $messageTemplateId,
            ]
        );
    }

    /**
     * Endpoint Meta: DELETE /{WHATSAPP_BUSINESS_ACCOUNT_ID}/message_templates?name={template_name}
     */
    public function deleteMessageTemplateByName(string $businessAccountId, string $templateName, array $options = []): array
    {
        $businessAccountId = trim($businessAccountId);
        $templateName = trim($templateName);

        $validator = Validator::make([
            'business_account_id' => $businessAccountId,
            'template_name' => $templateName,
        ], [
            'business_account_id' => ['required', 'string', 'regex:/^\d+$/', 'max:50'],
            'template_name' => ['required', 'string', 'max:512'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        $credentials = $this->resolveAccessTokenOnly($options, 'delete_message_template', [
            'business_account_id' => $businessAccountId,
            'template_name' => $templateName,
        ]);
        if (!empty($credentials['error'])) {
            return $credentials;
        }

        $url = sprintf(
            '%s/%s/%s/message_templates?name=%s',
            $this->baseUrl,
            trim($this->version, '/'),
            $businessAccountId,
            rawurlencode($templateName)
        );

        return $this->dispatchGraphRequest(
            'DELETE',
            $url,
            null,
            $credentials['access_token'],
            'delete_message_template',
            [
                'business_account_id' => $businessAccountId,
                'template_name' => $templateName,
            ]
        );
    }

    /**
     * Endpoint Meta: GET /{WHATSAPP_BUSINESS_ACCOUNT_ID}/message_templates?name={template_name}
     */
    public function getMessageTemplateByName(string $businessAccountId, string $templateName, array $options = []): array
    {
        $businessAccountId = trim($businessAccountId);
        $templateName = trim($templateName);

        $validator = Validator::make([
            'business_account_id' => $businessAccountId,
            'template_name' => $templateName,
        ], [
            'business_account_id' => ['required', 'string', 'regex:/^\d+$/', 'max:50'],
            'template_name' => ['required', 'string', 'max:512'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        $credentials = $this->resolveAccessTokenOnly($options, 'get_message_template_by_name', [
            'business_account_id' => $businessAccountId,
            'template_name' => $templateName,
        ]);
        if (!empty($credentials['error'])) {
            return $credentials;
        }

        $url = sprintf(
            '%s/%s/%s/message_templates',
            $this->baseUrl,
            trim($this->version, '/'),
            $businessAccountId
        );

        return $this->dispatchGraphRequest(
            'GET',
            $url,
            [
                'name' => $templateName,
                'fields' => 'id,name,status,language,category,rejected_reason',
                'limit' => 50,
            ],
            $credentials['access_token'],
            'get_message_template_by_name',
            [
                'business_account_id' => $businessAccountId,
                'template_name' => $templateName,
            ]
        );
    }

    /**
     * Endpoint Meta: POST /{PHONE_NUMBER_ID}/messages (status=read)
     */
    public function markAsRead(string $messageId, array $options = []): array
    {
        $messageId = trim($messageId);

        $validator = Validator::make([
            'message_id' => $messageId,
        ], [
            'message_id' => ['required', 'string', 'max:512'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'status' => 'read',
            'message_id' => $messageId,
        ];

        return $this->dispatchMessage($payload, $options, 'mark_as_read', [
            'message_id' => $messageId,
        ]);
    }

    /**
     * Endpoint Meta: POST /{PHONE_NUMBER_ID}/messages (payload customizado)
     */
    public function sendMessage(array $payload, array $options = []): array
    {
        if (!isset($payload['messaging_product'])) {
            $payload['messaging_product'] = 'whatsapp';
        }

        return $this->dispatchMessage($payload, $options, 'send_message');
    }

    private function sendMedia(
        string $type,
        string $to,
        string $media,
        ?string $caption,
        ?string $filename,
        array $options,
        string $context
    ): array {
        $normalizedTo = $this->normalizePhone($to);
        $mediaReference = $this->buildMediaReference($media);

        if ($mediaReference === null) {
            return [
                'error' => true,
                'status' => 422,
                'body' => [
                    'media' => ['Informe uma URL válida (http/https) ou um media id.'],
                ],
            ];
        }

        $validator = Validator::make([
            'to' => $normalizedTo,
            'type' => $type,
        ], [
            'to' => ['required', 'string', 'regex:/^\d{8,20}$/'],
            'type' => ['required', 'in:audio,image,video,document'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        $mediaReference = $this->resolveMediaReference(
            $type,
            $mediaReference,
            $options,
            $context,
            ['to' => $normalizedTo, 'type' => $type]
        );
        if (!empty($mediaReference['error'])) {
            return $mediaReference;
        }

        $mediaPayload = $mediaReference;
        $caption = is_string($caption) ? trim($caption) : '';
        if ($caption !== '' && in_array($type, ['image', 'video', 'document'], true)) {
            $mediaPayload['caption'] = $caption;
        }

        $filename = is_string($filename) ? trim($filename) : '';
        if ($filename !== '' && $type === 'document') {
            $mediaPayload['filename'] = $filename;
        }

        if ($type === 'audio' && array_key_exists('audio_voice', $options)) {
            $mediaPayload['voice'] = (bool) $options['audio_voice'];
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $normalizedTo,
            'type' => $type,
            $type => $mediaPayload,
        ];

        return $this->dispatchMessage($payload, $options, $context, [
            'to' => $normalizedTo,
            'type' => $type,
        ]);
    }

    private function resolveMediaReference(
        string $type,
        array $mediaReference,
        array $options,
        string $context,
        array $logContext = []
    ): array {
        $mediaLink = $mediaReference['link'] ?? null;
        if (!is_string($mediaLink) || $mediaLink === '') {
            return $mediaReference;
        }

        $uploadBeforeSend = (bool) ($options['upload_before_send'] ?? false);
        if (!$uploadBeforeSend) {
            return $mediaReference;
        }

        $uploadResult = $this->uploadMediaByUrl($type, $mediaLink, $options, $context, $logContext);
        if (empty($uploadResult['error']) && !empty($uploadResult['media_id'])) {
            return ['id' => (string) $uploadResult['media_id']];
        }

        $fallbackToLink = (bool) ($options['fallback_to_link_on_upload_failure'] ?? true);
        if ($fallbackToLink) {
            Log::warning("WhatsappCloudApiService::{$context} upload falhou; fallback para link direto", array_merge(
                $logContext,
                [
                    'upload_status' => $uploadResult['status'] ?? null,
                    'upload_body' => $uploadResult['body'] ?? null,
                ]
            ));

            return $mediaReference;
        }

        return $uploadResult;
    }

    private function dispatchMessage(array $payload, array $options, string $context, array $logContext = []): array
    {
        $credentials = $this->resolveCredentials($options, $context, $logContext);
        if (!empty($credentials['error'])) {
            return $credentials;
        }

        $url = sprintf(
            '%s/%s/%s/messages',
            $this->baseUrl,
            trim($this->version, '/'),
            $credentials['phone_number_id']
        );

        try {
            $request = Http::asJson()
                ->acceptJson()
                ->withToken($credentials['access_token'])
                ->timeout($this->timeout);

            if ($this->retryTimes > 0) {
                $request = $request->retry($this->retryTimes, $this->retrySleepMs);
            }

            $response = $request->post($url, $payload);
        } catch (RequestException $exception) {
            $response = $exception->response;
            Log::warning("WhatsappCloudApiService::{$context} request exception with response", array_merge($logContext, [
                'status' => $response?->status(),
                'error' => $exception->getMessage(),
                'response_body' => $response?->json() ?: $response?->body(),
            ]));

            if ($response instanceof Response) {
                return $this->normalizeResponse($response, $context, $logContext);
            }

            return [
                'error' => true,
                'status' => null,
                'body' => ['message' => 'Falha de comunicação com a WhatsApp Cloud API.'],
            ];
        } catch (\Throwable $exception) {
            Log::error("WhatsappCloudApiService::{$context} request exception", array_merge($logContext, [
                'error' => $exception->getMessage(),
            ]));

            return [
                'error' => true,
                'status' => null,
                'body' => ['message' => 'Falha de comunicação com a WhatsApp Cloud API.'],
            ];
        }

        return $this->normalizeResponse($response, $context, $logContext);
    }

    private function dispatchGraphRequest(
        string $method,
        string $url,
        ?array $payload,
        string $accessToken,
        string $context,
        array $logContext = []
    ): array {
        try {
            $request = Http::asJson()
                ->acceptJson()
                ->withToken($accessToken)
                ->timeout($this->timeout);

            if ($this->retryTimes > 0) {
                $request = $request->retry($this->retryTimes, $this->retrySleepMs);
            }

            $upperMethod = strtoupper($method);
            $response = match ($upperMethod) {
                'GET' => $request->get($url, $payload ?? []),
                'POST' => $request->post($url, $payload ?? []),
                'DELETE' => $request->delete($url, $payload ?? []),
                default => $request->send($upperMethod, $url, ['json' => $payload ?? []]),
            };
        } catch (RequestException $exception) {
            $response = $exception->response;
            Log::warning("WhatsappCloudApiService::{$context} request exception with response", array_merge($logContext, [
                'status' => $response?->status(),
                'error' => $exception->getMessage(),
                'response_body' => $response?->json() ?: $response?->body(),
            ]));

            if ($response instanceof Response) {
                return $this->normalizeResponse($response, $context, $logContext);
            }

            return [
                'error' => true,
                'status' => null,
                'body' => ['message' => 'Falha de comunicação com a WhatsApp Cloud API.'],
            ];
        } catch (\Throwable $exception) {
            Log::error("WhatsappCloudApiService::{$context} request exception", array_merge($logContext, [
                'error' => $exception->getMessage(),
            ]));

            return [
                'error' => true,
                'status' => null,
                'body' => ['message' => 'Falha de comunicação com a WhatsApp Cloud API.'],
            ];
        }

        return $this->normalizeResponse($response, $context, $logContext);
    }

    private function uploadMediaByUrl(
        string $type,
        string $mediaUrl,
        array $options,
        string $context,
        array $logContext = []
    ): array {
        $credentials = $this->resolveCredentials($options, $context, $logContext);
        if (!empty($credentials['error'])) {
            return $credentials;
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'wa_cloud_media_');
        if ($tempFile === false) {
            return [
                'error' => true,
                'status' => 500,
                'body' => ['message' => 'Não foi possível preparar o arquivo temporário de mídia.'],
            ];
        }

        try {
            $downloadRequest = Http::timeout($this->timeout);
            if ($this->retryTimes > 0) {
                $downloadRequest = $downloadRequest->retry($this->retryTimes, $this->retrySleepMs);
            }

            $downloadResponse = $downloadRequest
                ->sink($tempFile)
                ->get($mediaUrl);

            if (!$downloadResponse->successful()) {
                return [
                    'error' => true,
                    'status' => $downloadResponse->status(),
                    'body' => [
                        'message' => 'Não foi possível baixar a mídia para upload na Meta.',
                        'download_status' => $downloadResponse->status(),
                    ],
                ];
            }

            $fileSize = @filesize($tempFile);
            if (!is_int($fileSize) || $fileSize <= 0) {
                return [
                    'error' => true,
                    'status' => 422,
                    'body' => ['message' => 'Arquivo de mídia vazio ou inválido para upload na Meta.'],
                ];
            }

            $fileToUpload = $tempFile;
            $temporaryFiles = [];

            if (
                $type === 'audio'
                && (bool) ($options['audio_voice'] ?? false)
                && (bool) ($options['audio_force_opus'] ?? false)
            ) {
                $convertedAudio = $this->convertAudioToOpusOgg($tempFile);
                if ($convertedAudio !== null) {
                    $fileToUpload = $convertedAudio;
                    $temporaryFiles[] = $convertedAudio;
                }
            }

            $contentType = $this->sanitizeMimeType($downloadResponse->header('Content-Type'));
            $mimeType = $this->resolveMimeTypeForUpload($type, $mediaUrl, $contentType, $fileToUpload);
            $fileName = $this->resolveUploadFilename($type, $mediaUrl, $mimeType, $fileToUpload);

            $uploadUrl = sprintf(
                '%s/%s/%s/media',
                $this->baseUrl,
                trim($this->version, '/'),
                $credentials['phone_number_id']
            );

            $fileHandle = fopen($fileToUpload, 'r');
            if ($fileHandle === false) {
                return [
                    'error' => true,
                    'status' => 500,
                    'body' => ['message' => 'Não foi possível abrir o arquivo temporário para upload na Meta.'],
                ];
            }

            try {
                $uploadRequest = Http::withToken($credentials['access_token'])
                    ->asMultipart()
                    ->acceptJson()
                    ->timeout($this->timeout);

                if ($this->retryTimes > 0) {
                    $uploadRequest = $uploadRequest->retry($this->retryTimes, $this->retrySleepMs);
                }

                $uploadResponse = $uploadRequest
                    ->attach('file', $fileHandle, $fileName)
                    ->post($uploadUrl, [
                        'messaging_product' => 'whatsapp',
                        'type' => $mimeType,
                    ]);
            } finally {
                fclose($fileHandle);
                foreach ($temporaryFiles as $file) {
                    @unlink($file);
                }
            }

            $normalized = $this->normalizeResponse($uploadResponse, "{$context}_upload_media", $logContext);
            $mediaId = (string) ($normalized['body']['id'] ?? '');

            if (!empty($normalized['error']) || $mediaId === '') {
                if (empty($normalized['error'])) {
                    return [
                        'error' => true,
                        'status' => $normalized['status'] ?? 500,
                        'body' => array_merge($normalized['body'] ?? [], [
                            'message' => 'Meta não retornou media id para envio.',
                        ]),
                    ];
                }

                return $normalized;
            }

            return [
                'error' => false,
                'status' => $normalized['status'],
                'body' => $normalized['body'],
                'media_id' => $mediaId,
            ];
        } catch (\Throwable $exception) {
            Log::error("WhatsappCloudApiService::{$context} upload exception", array_merge($logContext, [
                'media_url' => $mediaUrl,
                'error' => $exception->getMessage(),
            ]));

            return [
                'error' => true,
                'status' => null,
                'body' => ['message' => 'Falha de comunicação ao fazer upload da mídia na WhatsApp Cloud API.'],
            ];
        } finally {
            @unlink($tempFile);
        }
    }

    private function resolveCredentials(array $options, string $context, array $logContext = []): array
    {
        $phoneNumberId = $this->sanitizeConfigValue($options['phone_number_id'] ?? null)
            ?? $this->defaultPhoneNumberId;
        $accessToken = $this->sanitizeConfigValue($options['access_token'] ?? null)
            ?? $this->defaultAccessToken;

        $missing = [];
        if (!$phoneNumberId) {
            $missing[] = 'WHATSAPP_CLOUD_PHONE_NUMBER_ID';
        }
        if (!$accessToken) {
            $missing[] = 'WHATSAPP_CLOUD_ACCESS_TOKEN';
        }

        if (!empty($missing)) {
            Log::error("WhatsappCloudApiService::{$context} configuração ausente", array_merge($logContext, [
                'missing_env' => $missing,
            ]));

            return [
                'error' => true,
                'status' => 500,
                'body' => [
                    'message' => 'Configuração da WhatsApp Cloud API incompleta.',
                    'missing_env' => $missing,
                ],
            ];
        }

        return [
            'error' => false,
            'phone_number_id' => $phoneNumberId,
            'access_token' => $accessToken,
        ];
    }

    private function resolveAccessTokenOnly(array $options, string $context, array $logContext = []): array
    {
        $accessToken = $this->sanitizeConfigValue($options['access_token'] ?? null)
            ?? $this->defaultAccessToken;

        if (!$accessToken) {
            Log::error("WhatsappCloudApiService::{$context} configuração ausente", array_merge($logContext, [
                'missing_env' => ['WHATSAPP_CLOUD_ACCESS_TOKEN'],
            ]));

            return [
                'error' => true,
                'status' => 500,
                'body' => [
                    'message' => 'Configuração da WhatsApp Cloud API incompleta.',
                    'missing_env' => ['WHATSAPP_CLOUD_ACCESS_TOKEN'],
                ],
            ];
        }

        return [
            'error' => false,
            'access_token' => $accessToken,
        ];
    }

    private function normalizeResponse(Response $response, string $context, array $logContext = []): array
    {
        $status = $response->status();
        $json = $response->json();
        $body = is_array($json) ? $json : ['raw' => $response->body()];

        if ($response->successful()) {
            return [
                'error' => false,
                'status' => $status,
                'body' => $body,
            ];
        }

        Log::warning("WhatsappCloudApiService::{$context} failed", array_merge($logContext, [
            'status' => $status,
            'body' => $body,
        ]));

        return [
            'error' => true,
            'status' => $status,
            'body' => $body,
        ];
    }

    private function validationError(array $errors): array
    {
        return [
            'error' => true,
            'status' => 422,
            'body' => $errors,
        ];
    }

    private function buildTemplateComponents(array $variables, mixed $components = null): array
    {
        if (is_array($components) && !empty($components)) {
            return $components;
        }

        if (empty($variables)) {
            return [];
        }

        $parameters = [];

        foreach (array_values($variables) as $value) {
            if (is_array($value) && isset($value['type'])) {
                $parameters[] = $value;
                continue;
            }

            $parameters[] = [
                'type' => 'text',
                'text' => (string) $value,
            ];
        }

        return [[
            'type' => 'body',
            'parameters' => $parameters,
        ]];
    }

    private function buildMediaReference(string $media): ?array
    {
        $media = trim($media);
        if ($media === '') {
            return null;
        }

        if ($this->isHttpUrl($media)) {
            return ['link' => $media];
        }

        return ['id' => $media];
    }

    private function isHttpUrl(string $value): bool
    {
        if (!filter_var($value, FILTER_VALIDATE_URL)) {
            return false;
        }

        $scheme = strtolower((string) parse_url($value, PHP_URL_SCHEME));
        return in_array($scheme, ['http', 'https'], true);
    }

    private function looksLikePdfUrl(string $value): bool
    {
        $path = (string) (parse_url($value, PHP_URL_PATH) ?? '');
        if ($path === '') {
            return false;
        }

        return Str::lower((string) pathinfo($path, PATHINFO_EXTENSION)) === 'pdf';
    }

    private function sanitizeMimeType(?string $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $parts = explode(';', $value, 2);
        $mimeType = trim(Str::lower($parts[0] ?? ''));

        return $mimeType !== '' ? $mimeType : null;
    }

    private function resolveMimeTypeForUpload(
        string $type,
        string $mediaUrl,
        ?string $headerMimeType,
        ?string $localFilePath = null
    ): string {
        if (is_string($localFilePath) && $localFilePath !== '') {
            $localExtension = Str::lower((string) pathinfo($localFilePath, PATHINFO_EXTENSION));
            if ($type === 'audio' && in_array($localExtension, ['ogg', 'opus'], true)) {
                return 'audio/ogg';
            }
        }

        if (is_string($headerMimeType)) {
            if ($type === 'audio' && str_starts_with($headerMimeType, 'audio/')) {
                return $headerMimeType;
            }
            if ($type === 'image' && str_starts_with($headerMimeType, 'image/')) {
                return $headerMimeType;
            }
            if ($type === 'video' && str_starts_with($headerMimeType, 'video/')) {
                return $headerMimeType;
            }
            if ($type === 'document' && (
                str_starts_with($headerMimeType, 'application/pdf')
                || str_starts_with($headerMimeType, 'application/')
            )) {
                return $headerMimeType;
            }
        }

        $extension = Str::lower((string) pathinfo((string) parse_url($mediaUrl, PHP_URL_PATH), PATHINFO_EXTENSION));

        return match ($type) {
            'audio' => match ($extension) {
                'ogg', 'opus' => 'audio/ogg',
                default => 'audio/mpeg',
            },
            'image' => match ($extension) {
                'png' => 'image/png',
                'webp' => 'image/webp',
                default => 'image/jpeg',
            },
            'video' => 'video/mp4',
            'document' => 'application/pdf',
            default => 'application/octet-stream',
        };
    }

    private function resolveUploadFilename(string $type, string $mediaUrl, string $mimeType, ?string $localFilePath = null): string
    {
        if (is_string($localFilePath) && $localFilePath !== '') {
            $localName = trim((string) basename($localFilePath));
            if ($localName !== '' && str_contains($localName, '.')) {
                return $localName;
            }
        }

        $path = (string) parse_url($mediaUrl, PHP_URL_PATH);
        $candidate = trim((string) basename($path));
        if ($candidate !== '' && str_contains($candidate, '.')) {
            return $candidate;
        }

        $extension = $this->extensionFromMimeType($mimeType);
        if ($extension === null) {
            $extension = match ($type) {
                'audio' => 'mp3',
                'image' => 'jpg',
                'video' => 'mp4',
                'document' => 'pdf',
                default => 'bin',
            };
        }

        return "{$type}.{$extension}";
    }

    private function extensionFromMimeType(string $mimeType): ?string
    {
        return match (Str::lower($mimeType)) {
            'audio/mpeg', 'audio/mp3' => 'mp3',
            'audio/ogg' => 'ogg',
            'image/jpeg', 'image/jpg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'video/mp4' => 'mp4',
            'application/pdf' => 'pdf',
            default => null,
        };
    }

    private function convertAudioToOpusOgg(string $sourceFile): ?string
    {
        $ffmpegPath = trim((string) @shell_exec('command -v ffmpeg 2>/dev/null'));
        if ($ffmpegPath === '') {
            return null;
        }

        $targetFile = tempnam(sys_get_temp_dir(), 'wa_cloud_opus_');
        if ($targetFile === false) {
            return null;
        }

        $targetWithExtension = $targetFile . '.ogg';
        @rename($targetFile, $targetWithExtension);

        $command = sprintf(
            '%s -y -i %s -ac 1 -ar 48000 -c:a libopus -b:a 32k %s 2>&1',
            escapeshellarg($ffmpegPath),
            escapeshellarg($sourceFile),
            escapeshellarg($targetWithExtension)
        );

        @exec($command, $output, $exitCode);
        if ($exitCode !== 0 || !is_file($targetWithExtension) || (int) @filesize($targetWithExtension) <= 0) {
            @unlink($targetWithExtension);
            return null;
        }

        return $targetWithExtension;
    }

    private function normalizePhone(string $value): string
    {
        return preg_replace('/\D+/', '', trim($value)) ?? '';
    }

    private function sanitizeConfigValue(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);
        return $value !== '' ? $value : null;
    }
}
