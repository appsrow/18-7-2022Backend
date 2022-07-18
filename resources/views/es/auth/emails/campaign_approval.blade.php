@include('es.auth.emails.email_header')

<div style="color:#555555;font-family:Trebuchet MS, Lucida Grande, Lucida Sans Unicode, Lucida Sans, Tahoma, sans-serif;line-height:1.5;padding-top:10px;padding-right:40px;padding-bottom:10px;padding-left:40px;">
    <div style="line-height: 1.5; font-size: 12px; font-family: Trebuchet MS, Lucida Grande, Lucida Sans Unicode, Lucida Sans, Tahoma, sans-serif; color: #555555; mso-line-height-alt: 18px;">
        <p style="line-height: 1.5; word-break: break-word; text-align: center; font-family: Trebuchet MS, Lucida Grande, Lucida Sans Unicode, Lucida Sans, Tahoma, sans-serif; font-size: 16px; mso-line-height-alt: 24px; margin: 0;">
            <span style="font-size: 16px;">
                <span style="color: #252f5a;">
                    <?php if (!empty($is_approved) and $is_approved == "APPROVED") { ?>

                        Felicidades <?php if (!empty($company_name)) {
                                        echo $company_name;
                                    }  ?>,<br>
                        tu "<?php if (!empty($campaign_name)) {
                                echo $campaign_name;
                            } ?>" La campaña ha sido aprobada por Dropforcoin.

                    <?php } else if (!empty($is_approved) and $is_approved == "REJECTED") { ?>

                        Disculpe las molestias, <?php if (!empty($company_name)) {
                                    echo $company_name;
                                }  ?>,<br><br>

                        Tu "<?php if (!empty($campaign_name)) {
                                echo $campaign_name;
                            } ?>" La campaña ha sido rechazada por el equipo de Dropforcoin.

                        <br><br>Su campaña ha sido rechazada debido a "<?php if (!empty($note)) {
                                                                            echo $note;
                                                                        } ?>" razón.

                    <?php } ?>
                    <br><br>Gracias por elegir Dropforcoin.
                </span>
            </span>
        </p>
        <p style="line-height: 1.5; word-break: break-word; text-align: center; font-family: Trebuchet MS, Lucida Grande, Lucida Sans Unicode, Lucida Sans, Tahoma, sans-serif; font-size: 16px; padding-top: 5px; mso-line-height-alt: 24px; margin: 0;"><span style="font-size: 16px;font-weight: 600;"><span style="color: #252f5a;">Equipo, Dropforcoin </span></span></p>
    </div>
</div>

@include('es.auth.emails.email_footer')