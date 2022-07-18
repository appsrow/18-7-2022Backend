@include('en.auth.emails.email_header')

<div style="color:#555555;font-family:Trebuchet MS, Lucida Grande, Lucida Sans Unicode, Lucida Sans, Tahoma, sans-serif;line-height:1.5;padding-top:10px;padding-right:40px;padding-bottom:10px;padding-left:40px;">
    <div style="line-height: 1.5; font-size: 12px; font-family: Trebuchet MS, Lucida Grande, Lucida Sans Unicode, Lucida Sans, Tahoma, sans-serif; color: #555555; mso-line-height-alt: 18px;">
        <p style="line-height: 1.5; word-break: break-word; text-align: center; font-family: Trebuchet MS, Lucida Grande, Lucida Sans Unicode, Lucida Sans, Tahoma, sans-serif; font-size: 16px; mso-line-height-alt: 24px; margin: 0;"><span style="font-size: 16px;">
                <span style="color: #252f5a;">You are receiving this email because we received a password reset request for your account.</span></span></p>
    </div>
</div>
<div align="center" class="button-container" style="padding-top:15px;padding-right:10px;padding-bottom:15px;padding-left:10px;">
    <a href="<?php if (!empty($reset_confirm_url)) {
                    echo $reset_confirm_url;
                }  ?>" style="-webkit-text-size-adjust: none; text-decoration: none; display: inline-block; color: #ffffff; background-color: #8abfb1; border-radius: 60px; -webkit-border-radius: 60px; -moz-border-radius: 60px; width: auto; width: auto; border-top: 1px solid #8abfb1; border-right: 1px solid #8abfb1; border-bottom: 1px solid #8abfb1; border-left: 1px solid #8abfb1; padding-top: 5px; padding-bottom: 5px; font-family: Trebuchet MS, Lucida Grande, Lucida Sans Unicode, Lucida Sans, Tahoma, sans-serif; text-align: center; mso-border-alt: none; word-break: keep-all;" target="_blank">
        <span style="padding-left:30px;padding-right:30px;font-size:16px;display:inline-block;">
            <span style="font-size: 16px; line-height: 2; word-break: break-word; mso-line-height-alt: 32px;">
                <strong> Reset Password </strong>
            </span>
        </span>
    </a>
</div>
<div style="color:#555555;font-family:Trebuchet MS, Lucida Grande, Lucida Sans Unicode, Lucida Sans, Tahoma, sans-serif;line-height:1.5;padding-top:10px;padding-right:40px;padding-bottom:10px;padding-left:40px;">
    <div style="line-height: 1.5; font-size: 12px; font-family: Trebuchet MS, Lucida Grande, Lucida Sans Unicode, Lucida Sans, Tahoma, sans-serif; color: #555555; mso-line-height-alt: 18px;">
        <p style="line-height: 1.5; word-break: break-word; text-align: center; font-family: Trebuchet MS, Lucida Grande, Lucida Sans Unicode, Lucida Sans, Tahoma, sans-serif; font-size: 16px; mso-line-height-alt: 24px; margin: 0;"><span style="font-size: 16px;">
                <span style="color: #252f5a;">If you did not request a password reset, no further action is required.</span></span></p>
        <p style="line-height: 1.5; word-break: break-word; text-align: center; font-family: Trebuchet MS, Lucida Grande, Lucida Sans Unicode, Lucida Sans, Tahoma, sans-serif; font-size: 16px; padding-top: 5px; mso-line-height-alt: 24px; margin: 0;"><span style="font-size: 16px;font-weight: 600;"><span style="color: #252f5a;">Regards,<br>Team, Dropforcoin </span></span></p>
    </div>
</div>



@include('en.auth.emails.email_footer')