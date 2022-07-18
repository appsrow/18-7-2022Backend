@include('es.auth.emails.email_header')

<span style="font-size: 16px;">Los códigos de tarjetas de regalo disponibles son menos de<?php echo (isset($limit_avaialble_cards) && !empty($limit_avaialble_cards)) ? $limit_avaialble_cards : "limit"; ?>. Por favor inserte nuevas tarjetas de regalo</span>

@include('es.auth.emails.email_footer')