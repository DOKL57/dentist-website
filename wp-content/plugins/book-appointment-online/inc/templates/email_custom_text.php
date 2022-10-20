<?php 
/**
 * @author    Ozplugin <client@oz-plugin.ru>
 * @link      http://www.oz-plugin.ru/
 * @copyright 2018 Ozplugin
 * @ver 3.1.0
 * Email custom text
 */
if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<html>
<body bgcolor="#e2e0ec" width="100%" style="margin: 0; max-width:100%;">
    <center style="width: 100%; background: #e2e0ec;">

        <!-- Visually Hidden Preheader Text : BEGIN -->
        <div style="padding:5px 0;font-family: sans-serif; font-size: 14px;">
        </div>
        <!-- Visually Hidden Preheader Text : END -->
        
        <!-- Email Body : BEGIN -->
        <table cellspacing="0" cellpadding="0" border="0" align="center" bgcolor="#ffffff" width="600" style="margin: auto;width:600px;" class="email-container">
            
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
								<h3 class="h3" style="font-family: sans-serif;color: #fff;font-size: 36px;text-align: center;margin: auto;vertical-align: middle;display: table-cell;">%email_title%</h3>
							</div>
						</div>
					</div>  
				</td>
            </tr>
            <!-- Hero Image, Flush : END -->

            <!-- 1 Column Text : BEGIN -->
            <tr>
                <td style="padding: 20px; text-align: center; font-family: sans-serif; font-size: 15px; mso-height-rule: exactly; line-height: 20px; color: #555555;">
					%email_text%
                </td>
            </tr>
        </table>
        <!-- Email Body : END -->
		
        <!-- Visually Hidden Preheader Text : BEGIN -->
        <div style="padding:5px 0;font-family: sans-serif; font-size: 14px;">
        </div>
        <!-- Visually Hidden Preheader Text : END -->

    </center>
</body>
</html>

