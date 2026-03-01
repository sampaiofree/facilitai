<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Cliente;
use App\Models\AssistantLead;
use App\Models\SequenceChat;
use App\Models\ScheduledMessage;
use App\Models\Tag;
use App\Models\WhatsappCloudConversationWindow;
use App\Models\ClienteLeadCustomField;

class ClienteLead extends Model
{
    use HasFactory;

    protected $table = 'cliente_lead';

    protected $fillable = [
        'cliente_id',
        'bot_enabled',
        'phone',
        'name',
        'info',
    ];

    protected $casts = [
        'bot_enabled' => 'boolean',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function assistantLeads()
    {
        return $this->hasMany(AssistantLead::class, 'lead_id');
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'cliente_lead_tag')->withTimestamps();
    }

    public function sequenceChats()
    {
        return $this->hasMany(SequenceChat::class, 'cliente_lead_id');
    }

    public function scheduledMessages()
    {
        return $this->hasMany(ScheduledMessage::class, 'cliente_lead_id');
    }

    public function whatsappCloudConversationWindows()
    {
        return $this->hasMany(WhatsappCloudConversationWindow::class, 'cliente_lead_id');
    }

    public function customFieldValues()
    {
        return $this->hasMany(ClienteLeadCustomField::class, 'cliente_lead_id');
    }

    public function whatsappCloudCampaignItems()
    {
        return $this->hasMany(WhatsappCloudCampaignItem::class, 'cliente_lead_id');
    }
}
