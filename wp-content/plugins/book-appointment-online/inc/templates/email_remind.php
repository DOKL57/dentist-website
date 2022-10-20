<?php 
/**
 * @author    Ozplugin <client@oz-plugin.ru>
 * @link      http://www.oz-plugin.ru/
 * @copyright 2018 Ozplugin
 * @ver 3.1.0
 * Email reminder
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
        <table cellspacing="0" cellpadding="0" border="0" align="center" bgcolor="#ffffff" width="600" style="margin: auto;" class="email-container">
            
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
								<h3 class="h3" style="font-family: sans-serif;color: #fff;font-size: 36px;text-align: center;margin: auto;vertical-align: middle;display: table-cell;"><?php _e('%name%, your appointment will start soon', 'book-appointment-online'); ?></h3>
							</div>
						</div>
					</div>  
				</td>
            </tr>
            <!-- Hero Image, Flush : END -->

            <!-- 1 Column Text : BEGIN -->
            <tr>
                <td style="padding: 20px; text-align: center; font-family: sans-serif; font-size: 15px; mso-height-rule: exactly; line-height: 20px; color: #555555;">
					<h1 style="line-height:1;"><?php _e('%name%, your appointment will start at %date% %time%', 'book-appointment-online'); ?></h1>
                    <?php _e('Details of your appointment are presented below.', 'book-appointment-online'); ?>
                </td>
            </tr>
			<!---order start-->
			<tr>
				<td style="padding:20px;">
			<table width="100%" cellspacing="0" cellpadding="0" border="0">
	<tbody>
		<tr>
			<th class="column-top" style="font-size:0pt; line-height:0pt; padding:0; margin:0; font-weight:normal; vertical-align:top; Margin:0" width="270" valign="top">
			<table width="100%" cellspacing="0" cellpadding="0" border="0">
				<tbody><tr>
					<td>
						<table width="100%" cellspacing="0" cellpadding="0" border="0" bgcolor="#6e52e5">
							<tbody><tr>
								<td class="content-spacing" style="font-size:0pt; line-height:0pt; text-align:left" width="20"></td>
								<td>
									<table class="spacer" style="font-size:0pt; line-height:0pt; text-align:center; width:100%; min-width:100%" width="100%" cellspacing="0" cellpadding="0" border="0"><tbody><tr><td class="spacer" style="font-size:0pt; line-height:0pt; text-align:center; width:100%; min-width:100%" height="10">&nbsp;</td></tr></tbody></table>

									<div class="text-1" style="color:#fff; font-family:Arial, sans-serif; min-width:auto !important; font-size:14px; line-height:20px; text-align:left">
										<strong><?php _e('Order details', 'book-appointment-online'); ?>:</strong>
									</div>
									<table class="spacer" style="font-size:0pt; line-height:0pt; text-align:center; width:100%; min-width:100%" width="100%" cellspacing="0" cellpadding="0" border="0"><tbody><tr><td class="spacer" style="font-size:0pt; line-height:0pt; text-align:center; width:100%; min-width:100%" height="10">&nbsp;</td></tr></tbody></table>

								</td>
								<td class="content-spacing" style="font-size:0pt; line-height:0pt; text-align:left" width="20"></td>
							</tr>
						</tbody></table>
						<table width="100%" cellspacing="0" cellpadding="0" border="0" bgcolor="#fafafa">
							<tbody><tr>
								<td class="content-spacing" style="font-size:0pt; line-height:0pt; text-align:left" width="20"></td>
								<td>
									<table class="spacer" style="font-size:0pt; line-height:0pt; text-align:center; width:100%; min-width:100%" width="100%" cellspacing="0" cellpadding="0" border="0"><tbody><tr><td class="spacer" style="font-size:0pt; line-height:0pt; text-align:center; width:100%; min-width:100%" height="10">&nbsp;</td></tr></tbody></table>

									<div class="text" style="color:#1e1e1e; font-family:Arial, sans-serif; min-width:auto !important; font-size:14px; line-height:20px; text-align:left">
										<strong><?php _e('Service', 'book-appointment-online'); ?>: </strong>%service%<br>
										<strong><?php _e('Specialist', 'book-appointment-online'); ?>: </strong>%employee%<br>
										<strong><?php _e('Date', 'book-appointment-online'); ?>: </strong>%date% %time%<br>
										<strong><?php _e('Duration (min)', 'book-appointment-online'); ?>: </strong>%duration%<br>
									</div>
									<table class="spacer" style="font-size:0pt; line-height:0pt; text-align:center; width:100%; min-width:100%" width="100%" cellspacing="0" cellpadding="0" border="0"><tbody><tr><td class="spacer" style="font-size:0pt; line-height:0pt; text-align:center; width:100%; min-width:100%" height="15">&nbsp;</td></tr></tbody></table>

								</td>
								<td class="content-spacing" style="font-size:0pt; line-height:0pt; text-align:left" width="20"></td>
							</tr>
						</tbody></table>
					</td>
				</tr>
			</tbody></table>
		</th>
		<th class="column-top" style="font-size:0pt; line-height:0pt; padding:0; margin:0; font-weight:normal; vertical-align:top; Margin:0" width="20" valign="top">
			<table width="100%" cellspacing="0" cellpadding="0" border="0">
				<tbody><tr>
					<td><div style="font-size:0pt; line-height:0pt;" class="mobile-br-15"></div>
	</td>
				</tr>
			</tbody></table>
		</th>
		<th class="column-top" style="font-size:0pt; line-height:0pt; padding:0; margin:0; font-weight:normal; vertical-align:top; Margin:0" width="270" valign="top">
			<table width="100%" cellspacing="0" cellpadding="0" border="0">
				<tbody><tr>
					<td>
						<table width="100%" cellspacing="0" cellpadding="0" border="0" bgcolor="#6e52e5">
							<tbody><tr>
								<td class="content-spacing" style="font-size:0pt; line-height:0pt; text-align:left" width="20"></td>
								<td>
									<table class="spacer" style="font-size:0pt; line-height:0pt; text-align:center; width:100%; min-width:100%" width="100%" cellspacing="0" cellpadding="0" border="0"><tbody><tr><td class="spacer" style="font-size:0pt; line-height:0pt; text-align:center; width:100%; min-width:100%" height="10">&nbsp;</td></tr></tbody></table>

									<div class="text-1" style="color:#fff; font-family:Arial, sans-serif; min-width:auto !important; font-size:14px; line-height:20px; text-align:left">
										<strong><?php _e('Order number', 'book-appointment-online'); ?>:</strong> <span style="color: #fff;">%ID%</span>
									</div>
									<table class="spacer" style="font-size:0pt; line-height:0pt; text-align:center; width:100%; min-width:100%" width="100%" cellspacing="0" cellpadding="0" border="0"><tbody><tr><td class="spacer" style="font-size:0pt; line-height:0pt; text-align:center; width:100%; min-width:100%" height="10">&nbsp;</td></tr></tbody></table>

								</td>
								<td class="content-spacing" style="font-size:0pt; line-height:0pt; text-align:left" width="20"></td>
							</tr>
						</tbody></table>
						<table class="spacer" style="font-size:0pt; line-height:0pt; text-align:center; width:100%; min-width:100%" width="100%" cellspacing="0" cellpadding="0" border="0"><tbody><tr><td class="spacer" style="font-size:0pt; line-height:0pt; text-align:center; width:100%; min-width:100%" height="20">&nbsp;</td></tr></tbody></table>


						<table width="100%" cellspacing="0" cellpadding="0" border="0" bgcolor="#6e52e5">
							<tbody><tr>
								<td class="content-spacing" style="font-size:0pt; line-height:0pt; text-align:left" width="20"></td>
								<td>
									<table class="spacer" style="font-size:0pt; line-height:0pt; text-align:center; width:100%; min-width:100%" width="100%" cellspacing="0" cellpadding="0" border="0"><tbody><tr><td class="spacer" style="font-size:0pt; line-height:0pt; text-align:center; width:100%; min-width:100%" height="10">&nbsp;</td></tr></tbody></table>

									<div class="text-1" style="color:#fff; font-family:Arial, sans-serif; min-width:auto !important; font-size:14px; line-height:20px; text-align:left">
										<strong><?php _e('Your data', 'book-appointment-online'); ?>:</strong>
									</div>
									<table class="spacer" style="font-size:0pt; line-height:0pt; text-align:center; width:100%; min-width:100%" width="100%" cellspacing="0" cellpadding="0" border="0"><tbody><tr><td class="spacer" style="font-size:0pt; line-height:0pt; text-align:center; width:100%; min-width:100%" height="10">&nbsp;</td></tr></tbody></table>

								</td>
								<td class="content-spacing" style="font-size:0pt; line-height:0pt; text-align:left" width="20"></td>
							</tr>
						</tbody></table>
						<table width="100%" cellspacing="0" cellpadding="0" border="0" bgcolor="#fafafa">
							<tbody><tr>
								<td class="content-spacing" style="font-size:0pt; line-height:0pt; text-align:left" width="20"></td>
								<td>
									<table class="spacer" style="font-size:0pt; line-height:0pt; text-align:center; width:100%; min-width:100%" width="100%" cellspacing="0" cellpadding="0" border="0"><tbody><tr><td class="spacer" style="font-size:0pt; line-height:0pt; text-align:center; width:100%; min-width:100%" height="10">&nbsp;</td></tr></tbody></table>

									<div class="text" style="color:#1e1e1e; font-family:Arial, sans-serif; min-width:auto !important; font-size:14px; line-height:20px; text-align:left">
										<strong>%name%</strong> %phone%
									</div>
									<table class="spacer" style="font-size:0pt; line-height:0pt; text-align:center; width:100%; min-width:100%" width="100%" cellspacing="0" cellpadding="0" border="0"><tbody><tr><td class="spacer" style="font-size:0pt; line-height:0pt; text-align:center; width:100%; min-width:100%" height="15">&nbsp;</td></tr></tbody></table>

								</td>
								<td class="content-spacing" style="font-size:0pt; line-height:0pt; text-align:left" width="20"></td>
							</tr>
						</tbody></table>
					</td>
				</tr>
			</tbody></table>
			</th>
		</tr>
	</tbody>
</table>
</td>
</tr>
            <tr>
                <td style="padding: 20px; text-align: center; font-family: sans-serif; font-size: 15px; mso-height-rule: exactly; line-height: 20px; color: #555555;">
					<h2 style="font-size: 22px;line-height:1;"><?php _e('Please, contact us, if you have any questions about your appointment.', 'book-appointment-online'); ?></h2>
					<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</p>
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