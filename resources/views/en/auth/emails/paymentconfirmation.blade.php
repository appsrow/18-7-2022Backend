@include('en.auth.emails.email_header')

<div style="color:#555555;font-family:Trebuchet MS, Lucida Grande, Lucida Sans Unicode, Lucida Sans, Tahoma, sans-serif;line-height:1.5;padding-top:10px;padding-right:40px;padding-bottom:10px;padding-left:40px;">
    <div style="line-height: 1.5; font-size: 12px; font-family: Trebuchet MS, Lucida Grande, Lucida Sans Unicode, Lucida Sans, Tahoma, sans-serif; color: #555555; mso-line-height-alt: 18px;">
        <p style="line-height: 1.5; word-break: break-word; text-align: center; font-family: Trebuchet MS, Lucida Grande, Lucida Sans Unicode, Lucida Sans, Tahoma, sans-serif; font-size: 16px; mso-line-height-alt: 24px; margin: 0;"><span style="font-size: 16px;">
                <span style="color: #252f5a;">Hello <?php if (!empty($company_name)) {
                                                        echo $company_name;
                                                    } ?> <br>Your "<?php if (!empty($campaign_name)) {
                                                                                                                                echo $campaign_name;
                                                                                                                            } ?>" Campaign has been created successfully.<br> We have received payment €<?php if (!empty($grand_total)) {
                                                                                                                                                                                                                                                                    echo $grand_total;
                                                                                                                                                                                                                                                                } ?>(inclusive tax).<br> For more details <a href="<?php echo env("EMAIL_WEBSITE_URL", null); ?>/brand/login">Sign In</a> and check.</span></span></p>
        <p style="line-height: 1.5; word-break: break-word; text-align: center; font-family: Trebuchet MS, Lucida Grande, Lucida Sans Unicode, Lucida Sans, Tahoma, sans-serif; font-size: 16px; padding-top: 5px; mso-line-height-alt: 24px; margin: 0;"><span style="font-size: 16px;font-weight: 600;"><span style="color: #252f5a;">Team, Dropforcoin </span></span></p>
    </div>
</div>

@include('en.auth.emails.email_footer')