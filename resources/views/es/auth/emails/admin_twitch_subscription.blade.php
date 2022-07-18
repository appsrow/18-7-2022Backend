@include('es.auth.emails.email_header')

<div style="color:#555555;font-family:Trebuchet MS, Lucida Grande, Lucida Sans Unicode, Lucida Sans, Tahoma, sans-serif;line-height:1.5;padding-top:10px;padding-right:40px;padding-bottom:10px;padding-left:40px;">
    <div style="line-height: 1.5; font-size: 12px; font-family: Trebuchet MS, Lucida Grande, Lucida Sans Unicode, Lucida Sans, Tahoma, sans-serif; color: #555555; mso-line-height-alt: 18px;">
        <p style="line-height: 1.5; word-break: break-word; text-align: center; font-family: Trebuchet MS, Lucida Grande, Lucida Sans Unicode, Lucida Sans, Tahoma, sans-serif; font-size: 16px; mso-line-height-alt: 24px; margin: 0;"><span style="font-size: 16px;">
                <span style="color: #252f5a;">Felicidades! </span></span></p>
    </div>
</div>

<div style="color:#555555;font-family:Trebuchet MS, Lucida Grande, Lucida Sans Unicode, Lucida Sans, Tahoma, sans-serif;line-height:1.5;padding-top:10px;padding-right:40px;padding-bottom:10px;padding-left:40px;">
    <div style="line-height: 1.5; font-size: 12px; font-family: Trebuchet MS, Lucida Grande, Lucida Sans Unicode, Lucida Sans, Tahoma, sans-serif; color: #555555; mso-line-height-alt: 18px;">
        <p style="line-height: 1.5; word-break: break-word; text-align: center; font-family: Trebuchet MS, Lucida Grande, Lucida Sans Unicode, Lucida Sans, Tahoma, sans-serif; font-size: 16px; mso-line-height-alt: 24px; margin: 0;"><span style="font-size: 16px;">
                <span style="color: #252f5a;"> La solicitud de suscripción a Twitch la realiza el usuario.<br><br>

                    A continuación encontrará los detalles de la sulicitud. Por favor, recuerde realizar la acción requerida por su parte.<br></br>

                    Nombre de usuario : <?php echo (isset($user_twitch_id) && !empty($user_twitch_id)) ? $user_twitch_id : ""; ?> (<?php echo (isset($user_email) && !empty($user_email)) ? $user_email : ""; ?>)<br>
                    Nombre del transmisor : <?php echo (isset($streamer_name) && !empty($streamer_name)) ? $streamer_name : ""; ?><br>
                    Monedas deducidas : <?php echo (isset($redeem_coins) && !empty($redeem_coins)) ? $redeem_coins : 0; ?><br>
                </span>
                <br>Gracias y Saludos,
                <br>Equipo DropforCoin
        </p>
    </div>
</div>

@include('es.auth.emails.email_footer')