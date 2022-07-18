@include('en.auth.emails.email_header')

<div style="color:#555555;font-family:Trebuchet MS, Lucida Grande, Lucida Sans Unicode, Lucida Sans, Tahoma, sans-serif;line-height:1.5;padding-top:10px;padding-right:40px;padding-bottom:10px;padding-left:40px;">
    <div style="line-height: 1.5; font-size: 12px; font-family: Trebuchet MS, Lucida Grande, Lucida Sans Unicode, Lucida Sans, Tahoma, sans-serif; color: #555555; mso-line-height-alt: 18px;">
        <p style="line-height: 1.5; word-break: break-word; text-align: center; font-family: Trebuchet MS, Lucida Grande, Lucida Sans Unicode, Lucida Sans, Tahoma, sans-serif; font-size: 16px; mso-line-height-alt: 24px; margin: 0;">
            <span style="font-size: 16px;">
                <span style="color: #252f5a;">
                    <?php if (!empty($is_approved) and $is_approved == "APPROVED") { ?>

                        Congratulations <?php if (!empty($company_name)) {
                                            echo $company_name;
                                        }  ?>,<br>
                        your "<?php if (!empty($campaign_name)) {
                                    echo $campaign_name;
                                } ?>" Campaign has been approved by Dropforcoin.

                    <?php } else if (!empty($is_approved) and $is_approved == "REJECTED") { ?>

                        Sorry <?php if (!empty($company_name)) {
                                    echo $company_name;
                                }  ?>,<br><br>

                        Your "<?php if (!empty($campaign_name)) {
                                    echo $campaign_name;
                                } ?>" campaign has been rejected by Dropforcoin team.

                        <br><br>Your campaign has been rejected due to "<?php if (!empty($note)) {
                                                                            echo $note;
                                                                        } ?>" reason.

                    <?php } ?>
                    <br><br>Thank you for choosing Dropforcoin.
                </span>
            </span>
        </p>
        <p style="line-height: 1.5; word-break: break-word; text-align: center; font-family: Trebuchet MS, Lucida Grande, Lucida Sans Unicode, Lucida Sans, Tahoma, sans-serif; font-size: 16px; padding-top: 5px; mso-line-height-alt: 24px; margin: 0;"><span style="font-size: 16px;font-weight: 600;"><span style="color: #252f5a;">Team, Dropforcoin </span></span></p>
    </div>
</div>

@include('en.auth.emails.email_footer')