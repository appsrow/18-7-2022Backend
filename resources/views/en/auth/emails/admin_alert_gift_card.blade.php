@include('en.auth.emails.email_header')

<span style="font-size: 16px;">The available gift card codes are less than <?php echo (isset($limit_avaialble_cards) && !empty($limit_avaialble_cards)) ? $limit_avaialble_cards : "limit"; ?>. Please insert new gift cards</span>

@include('en.auth.emails.email_footer')