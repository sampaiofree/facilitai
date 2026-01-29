<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class Instance extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'credential_id',
        'name',
        'model',
        'evolution_api_key',
        'default_assistant_id',
        'proxy_ip',
        'proxy_port',
        'proxy_username',
        'proxy_password',
        'proxy_provider',
        'status',
        'expires_at',
        'agenda_id',

    ];
    
    /**
     * Get the user that owns the Instance
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function agenda()
    {
        return $this->belongsTo(Agenda::class);
    }

     public function credential()
    {
        return $this->belongsTo(Credential::class);
    }

    public function payment()
    {
        // Uma instância tem apenas um pagamento
        return $this->hasOne(Payment::class);
    }

    public function defaultAssistant()
    {
        return $this->belongsTo(Assistant::class, 'default_assistant_id', 'id');
    }

    public function defaultAssistantByOpenAi()
    {
        return $this->belongsTo(Assistant::class, 'default_assistant_id', 'openai_assistant_id');
    }

    public function getAssistenteAttribute()
    {
        return $this->defaultAssistant 
            ?? $this->defaultAssistantByOpenAi;
    }

    /**
     * Obtém todos os registros de uso de tokens para esta instância.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function tokenUsage()
    {
        return $this->hasMany(TokensOpenAI::class);
    }

    /**
     * Chats vinculados a esta instância.
     */
    public function chats()
    {
        // Chat model removed; relacionamento suspenso.
        return null;
    }

    /**
     * Calcula as métricas de uso (tokens e conversas) para um determinado período.
     *
     * @param string|null $startDate A data de início no formato 'Y-m-d'.
     * @param string|null $endDate A data de fim no formato 'Y-m-d'.
     * @return array Um array com 'total_tokens' e 'unique_conversations'.
     */
    public function getUsageMetrics(?string $startDate = null, ?string $endDate = null): array
    {
        // 1. Define o período padrão se nenhuma data for fornecida
        // Se não houver data, pega os últimos 7 dias.
        if (is_null($startDate) && is_null($endDate)) {
            $endDate = Carbon::now()->endOfDay();
            $startDate = Carbon::now()->subDays(6)->startOfDay();
        } else {
            // Garante que as datas sejam objetos Carbon para manipulação segura
            $startDate = $startDate ? Carbon::parse($startDate)->startOfDay() : null;
            $endDate = $endDate ? Carbon::parse($endDate)->endOfDay() : null;
        }

        // 2. Cria a consulta base, já filtrando pela instância atual
        $query = $this->tokenUsage()->where('instance_id', $this->id);

        // 3. Aplica os filtros de data à consulta
        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        // 4. Executa as agregações (soma e contagem)
        // Clonamos a query para poder executar duas agregações separadas
        $totalTokens = (clone $query)->sum('tokens');
        $uniqueConversations = (clone $query)->distinct('contact')->count('contact');
        
        // 5. Retorna os resultados em um array limpo
        return [
            'total_tokens' => (int) $totalTokens,
            'unique_conversations' => (int) $uniqueConversations,
        ];
    }



}
