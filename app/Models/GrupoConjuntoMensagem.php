<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class GrupoConjuntoMensagem extends Model
{
    use HasFactory;

    protected $table = 'grupo_conjunto_mensagens';

    public const ACTION_SEND_TEXT = 'send_text';
    public const ACTION_SEND_MEDIA = 'send_media';
    public const ACTION_UPDATE_GROUP_NAME = 'update_group_name';
    public const ACTION_UPDATE_GROUP_DESCRIPTION = 'update_group_description';
    public const ACTION_UPDATE_GROUP_IMAGE = 'update_group_image';

    public const ACTION_TYPES = [
        self::ACTION_SEND_TEXT,
        self::ACTION_SEND_MEDIA,
        self::ACTION_UPDATE_GROUP_NAME,
        self::ACTION_UPDATE_GROUP_DESCRIPTION,
        self::ACTION_UPDATE_GROUP_IMAGE,
    ];

    protected $fillable = [
        'user_id',
        'created_by_user_id',
        'grupo_conjunto_id',
        'conexao_id',
        'mensagem',
        'action_type',
        'payload',
        'dispatch_type',
        'scheduled_for',
        'status',
        'recipients',
        'result',
        'sent_count',
        'failed_count',
        'attempts',
        'queued_at',
        'sent_at',
        'failed_at',
        'canceled_at',
        'error_message',
    ];

    protected $casts = [
        'payload' => 'array',
        'recipients' => 'array',
        'result' => 'array',
        'scheduled_for' => 'datetime',
        'queued_at' => 'datetime',
        'sent_at' => 'datetime',
        'failed_at' => 'datetime',
        'canceled_at' => 'datetime',
        'sent_count' => 'integer',
        'failed_count' => 'integer',
        'attempts' => 'integer',
    ];

    public function conjunto()
    {
        return $this->belongsTo(GrupoConjunto::class, 'grupo_conjunto_id');
    }

    public function conexao()
    {
        return $this->belongsTo(Conexao::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function resolveActionType(): string
    {
        $actionType = trim((string) ($this->action_type ?? ''));

        return in_array($actionType, self::ACTION_TYPES, true)
            ? $actionType
            : self::ACTION_SEND_TEXT;
    }

    public function resolvePayload(): array
    {
        return is_array($this->payload) ? $this->payload : [];
    }

    public function actionTypeLabel(): string
    {
        return match ($this->resolveActionType()) {
            self::ACTION_SEND_MEDIA => 'Enviar mídia',
            self::ACTION_UPDATE_GROUP_NAME => 'Trocar título',
            self::ACTION_UPDATE_GROUP_DESCRIPTION => 'Trocar descrição',
            self::ACTION_UPDATE_GROUP_IMAGE => 'Trocar foto',
            default => 'Enviar texto',
        };
    }

    public function actionSummary(): string
    {
        $payload = $this->resolvePayload();
        $fallback = trim((string) ($this->mensagem ?? ''));

        return match ($this->resolveActionType()) {
            self::ACTION_SEND_MEDIA => $this->buildSendMediaSummary($payload, $fallback),
            self::ACTION_UPDATE_GROUP_NAME => $this->fieldSummary($payload, 'group_name', $fallback),
            self::ACTION_UPDATE_GROUP_DESCRIPTION => $this->fieldSummary($payload, 'group_description', $fallback),
            self::ACTION_UPDATE_GROUP_IMAGE => $this->fieldSummary($payload, 'group_image_url', $fallback),
            default => $this->buildSendTextSummary($payload, $fallback),
        };
    }

    public function toEditorPayload(): array
    {
        $payload = $this->resolvePayload();
        $actionType = $this->resolveActionType();

        return [
            'id' => (int) $this->id,
            'action_type' => $actionType,
            'dispatch_type' => (string) ($this->dispatch_type ?? 'now'),
            'status' => (string) ($this->status ?? ''),
            'scheduled_for_input' => $this->scheduled_for?->copy()->format('Y-m-d\\TH:i'),
            'text' => $this->fieldSummary($payload, 'text', trim((string) ($this->mensagem ?? ''))),
            'media_type' => trim((string) ($payload['media_type'] ?? '')),
            'media_url' => trim((string) ($payload['media_url'] ?? '')),
            'caption' => trim((string) ($payload['caption'] ?? '')),
            'group_name' => trim((string) ($payload['group_name'] ?? '')),
            'group_description' => trim((string) ($payload['group_description'] ?? '')),
            'group_image_url' => trim((string) ($payload['group_image_url'] ?? '')),
            'mention_all' => $this->payloadBoolean($payload, 'mention_all'),
        ];
    }

    private function fieldSummary(array $payload, string $field, string $fallback): string
    {
        $value = trim((string) ($payload[$field] ?? ''));
        if ($value !== '') {
            return $value;
        }

        return $fallback !== '' ? $fallback : '-';
    }

    private function buildSendMediaSummary(array $payload, string $fallback): string
    {
        $type = trim((string) ($payload['media_type'] ?? ''));
        $url = trim((string) ($payload['media_url'] ?? ''));
        $caption = trim((string) ($payload['caption'] ?? ''));

        $parts = [];
        if ($type !== '') {
            $parts[] = "[$type]";
        }
        if ($url !== '') {
            $parts[] = $url;
        }

        $summary = trim(implode(' ', $parts));
        if ($summary === '') {
            $summary = $fallback;
        }
        if ($summary === '') {
            $summary = '-';
        }

        if ($caption !== '') {
            $summary .= ' ' . Str::limit("($caption)", 120);
        }
        if ($this->payloadBoolean($payload, 'mention_all')) {
            $summary .= ' [@todos]';
        }

        return $summary;
    }

    private function buildSendTextSummary(array $payload, string $fallback): string
    {
        $summary = $this->fieldSummary($payload, 'text', $fallback);
        if ($this->payloadBoolean($payload, 'mention_all')) {
            return $summary . ' [@todos]';
        }

        return $summary;
    }

    private function payloadBoolean(array $payload, string $key): bool
    {
        $value = $payload[$key] ?? false;

        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            return $value === 1;
        }

        if (is_string($value)) {
            return in_array(strtolower(trim($value)), ['1', 'true', 'on', 'yes'], true);
        }

        return false;
    }
}
