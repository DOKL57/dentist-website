<?php 
/**
 * @author    Ozplugin <client@oz-plugin.ru>
 * @link      http://www.oz-plugin.ru/
 * @copyright 2018 Ozplugin
 * @ver 3.1.0
 * Email after end of service
 */
if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<html>
<body bgcolor="#e2e0ec" width="100%" style="margin: 0; max-width:100%;">
    <center style="width: 100%; background: #e2e0ec;">

        <!-- Visually Hidden Preheader Text : BEGIN -->
        <div style="padding:5px 0;font-family: sans-serif; font-size: 14px;">
            <?php _e('(Optional) This demo Email template. You can add your own template.', 'book-appointment-online'); ?>
        </div>
        <!-- Visually Hidden Preheader Text : END -->
        
        <!-- Email Body : BEGIN -->
        <table cellspacing="0" cellpadding="0" border="0" align="center" bgcolor="#ffffff" width="600" style="margin: auto; width:600px;" class="email-container">
            
            <!-- Hero Image, Flush : BEGIN -->
            <tr>
				<td> 
					<div style="width:100%;height:200px;margin:0 auto;">  
						<div style="max-height:0;max-width:100%;width:600px;overflow: visible;">
							<div style="width:600px;height:200px;margin-top:0px;margin-left:0px;display:inline-block;text-align:center;line-height:100px;font-size:50px;">
								<img src="%sitename%/wp-content/plugins/book-appointment-online/assets/images/booked600x200.jpg" width="600" height="" alt="alt_text" border="0" align="center" style="width: 100%; max-width: 600px;">
							</div>
						</div>
						<div class="mob100" style="max-height:0;max-width:0;overflow: visible;">
							<div class="mob100" style="width:560px;height:200px;margin-top:0px;margin-left:20px;display:table;text-align:center;">
								<h3 class="h3" style="font-family: sans-serif;color: #fff;font-size: 36px;text-align: center;margin: auto;vertical-align: middle;display: table-cell;"><?php _e('%name%, thank you for visiting', 'book-appointment-online'); ?></h3>
							</div>
						</div>
					</div>  
				</td>
            </tr>
            <!-- Hero Image, Flush : END -->

            <!-- 1 Column Text : BEGIN -->
            <tr>
                <td style="padding: 40px 20px 20px; text-align: center; font-family: sans-serif; font-size: 15px; mso-height-rule: exactly; line-height: 20px; color: #555555;">
					<h1 style="line-height:1;"><?php _e('%name%, we are glad to see you among our customers', 'book-appointment-online'); ?></h1>
                    <?php _e('We look forward to new meetings. Specially for you, we have prepared a 10% discount on our new services', 'book-appointment-online'); ?>
                </td>
            </tr>
			<!---order start-->
            <tr>
				<td style="padding: 20px; text-align: center; font-family: sans-serif; font-size: 15px; mso-height-rule: exactly; line-height: 20px; color: #555555;">
					<div class="mob100" style="width:160px;padding: 10px; text-align: center; font-family: sans-serif; font-size: 15px; mso-height-rule: exactly; line-height: 20px; color: #555555;display:inline-block;">
						<img class="mob100" src="%sitename%/wp-content/plugins/<?php echo 'book-appointment-online'; ?>/assets/images/480x480_service.jpg" width="480" height="" alt="alt_text" border="0" align="center" style="width: 100%; max-width: 100%">
						<h4 style="font-size: 16px;line-height:1;"><?php _e('Special service', 'book-appointment-online'); ?> #1</h4>
						<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt.</p>
					</div>
					<div class="mob100" style="width:160px;padding: 10px; text-align: center; font-family: sans-serif; font-size: 15px; mso-height-rule: exactly; line-height: 20px; color: #555555;display:inline-block;">
						<img class="mob100" src="%sitename%/wp-content/plugins/<?php echo 'book-appointment-online'; ?>/assets/images/480x480_service.jpg" width="480" height="" alt="alt_text" border="0" align="center" style="width: 100%; max-width: 100%">
						<h4 style="font-size: 16px;line-height:1;"><?php _e('Special service', 'book-appointment-online'); ?> #2</h4>
						<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt.</p>
					</div>
					<div class="mob100" style="width:160px;padding: 10px; text-align: center; font-family: sans-serif; font-size: 15px; mso-height-rule: exactly; line-height: 20px; color: #555555;display:inline-block;">
						<img class="mob100" src="%sitename%/wp-content/plugins/<?php echo 'book-appointment-online'; ?>/assets/images/480x480_service.jpg" width="480" height="" alt="alt_text" border="0" align="center" style="width: 100%; max-width: 100%">
						<h4 style="font-size: 16px;line-height:1;"><?php _e('Special service', 'book-appointment-online'); ?> #3</h4>
						<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt.</p>
					</div>
				</td>
            </tr>
            <tr>
                <td style="padding: 20px 20px 40px; text-align: center; font-family: sans-serif; font-size: 15px; mso-height-rule: exactly; line-height: 20px; color: #555555;">
					<h2 style="font-size: 22px;line-height:1;"><?php _e('Book an appointment with a discount', 'book-appointment-online'); ?></h2>
					<br>
					<a href="%sitename%" style="background: #2dde98;padding: 12px 23px;border-radius: 50px;color: #fff;font-weight: bold;text-decoration: none;"><?php _e('Booking', 'book-appointment-online'); ?></a>
                </td>
            </tr>
<!--order end-->
        </table>
        <!-- Email Body : END -->
          
        <!-- Email Footer : BEGIN -->
        <table cellspacing="0" cellpadding="0" border="0" align="center" width="600" style="margin: auto;" class="email-container">
            <tr>
                <td style="padding: 40px 10px;width: 100%;font-size: 12px; font-family: sans-serif; mso-height-rule: exactly; line-height:18px; text-align: center; color: #888888;">
                    Company Name<br><span class="mobile-link--footer">123 Fake Street, SpringField, OR, 97477 US</span><br><span class="mobile-link--footer">(123) 456-7890</span>
                </td>
            </tr>
        </table>
        <!-- Email Footer : END -->

    </center>
</body>
</html>