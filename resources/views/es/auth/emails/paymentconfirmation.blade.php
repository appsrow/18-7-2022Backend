@include('es.auth.emails.email_header')

<div style="color:#555555;font-family:Trebuchet MS, Lucida Grande, Lucida Sans Unicode, Lucida Sans, Tahoma, sans-serif;line-height:1.5;padding-top:10px;padding-right:40px;padding-bottom:10px;padding-left:40px;">
    <div style="line-height: 1.5; font-size: 12px; font-family: Trebuchet MS, Lucida Grande, Lucida Sans Unicode, Lucida Sans, Tahoma, sans-serif; color: #555555; mso-line-height-alt: 18px;">
        <p style="line-height: 1.5; word-break: break-word; text-align: center; font-family: Trebuchet MS, Lucida Grande, Lucida Sans Unicode, Lucida Sans, Tahoma, sans-serif; font-size: 16px; mso-line-height-alt: 24px; margin: 0;"><span style="font-size: 16px;">
                <span style="color: #252f5a;">Hola <?php if (!empty($company_name)) {
                                                        echo $company_name;
                                                    } ?> <br>Tu "<?php if (!empty($campaign_name)) {
                                                                                                                            echo $campaign_name;
                                                                                                                        } ?>" La campaña se ha creado con éxito.<br> Nosotras hemos recibido el pago €<?php if (!empty($grand_total)) {
                                                                                                                                                                                                                                                                    echo $grand_total;
                                                                                                                                                                                                                                                                } ?>(impuesto incluido).<br> Para más detalles <a href="<?php echo env("EMAIL_WEBSITE_URL", null); ?>/brand/login">Iniciar sesión</a> y comprobar.</span></span></p>
        <p style="line-height: 1.5; word-break: break-word; text-align: center; font-family: Trebuchet MS, Lucida Grande, Lucida Sans Unicode, Lucida Sans, Tahoma, sans-serif; font-size: 16px; padding-top: 5px; mso-line-height-alt: 24px; margin: 0;"><span style="font-size: 16px;font-weight: 600;"><span style="color: #252f5a;">Equipo, Dropforcoin </span></span></p>
    </div>
</div>

@include('es.auth.emails.email_footer')