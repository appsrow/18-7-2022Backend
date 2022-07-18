@include('en.auth.emails.email_header')

<div style="color:#555555;font-family:Trebuchet MS, Lucida Grande, Lucida Sans Unicode, Lucida Sans, Tahoma, sans-serif;line-height:1.5;padding-top:10px;padding-right:40px;padding-bottom:10px;padding-left:40px;">
    <div style="line-height: 1.5; font-size: 12px; font-family: Trebuchet MS, Lucida Grande, Lucida Sans Unicode, Lucida Sans, Tahoma, sans-serif; color: #555555; mso-line-height-alt: 18px;">
        <p style="line-height: 1.5; word-break: break-word; text-align: left; font-family: Trebuchet MS, Lucida Grande, Lucida Sans Unicode, Lucida Sans, Tahoma, sans-serif; font-size: 16px; mso-line-height-alt: 24px; margin: 0;">
            <span style="font-size: 16px;">
                <span style="color: #252f5a;">

                    <?php if (!empty($company_name)) {
                        echo "Hello" . $company_name;
                    } ?> <br><br>

                    Budget has been increased for the <?php if (!empty($campaign_name)) {
                                                            echo $campaign_name;
                                                        } ?> campaign.<br><br>


                    We have received a payment of €<?php if (!empty($grand_total)) {
                                                        echo $grand_total;
                                                    } ?> (inclusive tax).
                    Please find invoice in the attachment.<br><br>


                    For more details you can <a href="<?php echo env("EMAIL_WEBSITE_URL", null); ?>/brand/login" target="_blank"> Sign In </a> and check.<br><br>


                </span>
            </span>
        </p>
        <p style="line-height: 1.5; word-break: break-word; text-align: center; font-family: Trebuchet MS, Lucida Grande, Lucida Sans Unicode, Lucida Sans, Tahoma, sans-serif; font-size: 16px; padding-top: 5px; mso-line-height-alt: 24px; margin: 0;"><span style="font-size: 16px;font-weight: 600;"><span style="color: #252f5a;">Team, Dropforcoin </span></span></p>
    </div>
</div>

@include('en.auth.emails.email_footer')