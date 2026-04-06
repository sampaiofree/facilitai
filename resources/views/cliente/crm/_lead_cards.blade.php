@foreach($leads as $lead)
    @include('cliente.crm._lead_card', ['lead' => $lead, 'crmPipeline' => $crmPipeline])
@endforeach
