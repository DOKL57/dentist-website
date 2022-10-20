=== Book appointment online ===
Contributors: oz-plugin
Donate link: http://oz-plugin.ru/
Tags: appointment, booking, online booking, salon beauty, stomatology, clinic, medical centers, beauty center, reservation, car services, hair salon, scheduling
Requires at least: 5.3
Tested up to: 6.0
Requires PHP: 5.6
Stable tag: 3.1.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Book appointment online - plugin for online doctor, hairdresser, stylist and other appointments.
A perfect choice for medical centers, beauty salons, hair shops, car services.

== Description  ===

Book appointment online - plugin for online doctor, hairdresser, stylist and other appointments.
A perfect choice for medical centers, beauty salons, hair shops, car services.

<strong>Main features</strong>

- Quick start
- Booking appointment "step by step"
- Automatic time booking management. Users can book an appointment only on the available time. This will help you avoid double booking
- Pretty modern design
- Customizable colors
- Responsive
- HTML Email templates
- Branch offices
- 3 different schedules for staff (custom / regular working hours / shift work (e.g 2:2, 3:1 ratio etc))
- Booking form has Type view - in popup window


<strong>Plugin frontend features</strong>

- Choice of services (with categories)
- Choice of available date and time
- Choice of form of appointment
- Email notification
- Print appointment info to PDF
- Send appointment info to Google Calenbdar, Outlook, iCal

<strong>Plugin backend features</strong>

- Appointment calendar in your site's console
- Creating a specialist: possibility to specify a name, job position; upload photo; add working hours and list of services; check appointments of a specialist
- Creating services: possibility to specify duration, price, custom text
- Viewing the appointment: checking all entries in list format or via calendar
- Add/edit clients from admin
- Email notification about an appointment (with HTML editor)

Demo: [See demo](http://demo.oz-plugin.ru/?utm_source=see_demo)

= Multilingual =

- English
- Russian

<strong>This features available only in version PRO</strong>


- PayPal, Stripe, Woocommerce integration
- Coupon discounts
- Employee profile page
- SMS, Push notifications
- SMS reminder about an appointment
- Google calendar sync
- Export appointments to CSV
- Choosing the required form fields to complete booking
- Custom form fields
- Captcha
- +5 languages (Deutsch, French, Spanish, Italian, Lithuanian)

Read more about PRO: [See PRO](http://demo.oz-plugin.ru/?utm_source=read_pro)


==  Installation  ==

1. Install plugin
2. Create services
3. Create employee (add name, image, specialty, services, worktime)
4. Set booking settings
5. Add shortcode on page [oz_template]

== Frequently Asked Questions ==

= How can I publish an appointment booking form? =

You should add shortcode [oz_template] at any place on the post/page

= Can I stylize booking form? =

If you know CSS then you can. Set "no styles" in plugin's settings.

= Can I add offline clients? =

Yes you can. In admin console hover "Clients" and select "Add client"

== Screenshots ==

1. Dashboard WP. Calendar view
2. Dashboard WP. List view
3. First step plugin. Select specialist
4. Second step. Select Date
5. Third step. Select time
6. Fourth step. Select service
7. Fifth step. Booking form
8. End appointment

==  Changelog  ==

3.1.0.1
01/02/22
UPDATED Wordpress 5.9 compatibility

3.1.0
13/01/21
ADDED Service categories
ADDED Branches for employees
ADDED New schedlue types
ADDED Sequence of steps
ADDED View option
ADDED New theme
ADDED Appointment statuses
ADDED Final message
ADDED Payment method - Locally
UPDATED Fully update plugin core and plugin settings interface


1.39
09/08/21
FIXED XSS vulnerability with Price field


1.38
23/06/21
FIXED added missing file email-marketing.php

1.37
18/06/21
ADDED HTML editor for Email notifications

16/04/21
FIXED Color settings
FIXED Divi compatibility

1.35
ADDED Color settings option

16/12/20

1.35
ADDED Color settings option

18/08/20

1.34
FIXED Wordpress 5.5 Compatibility
FIXED the schedule might not work before in some themes


21/05/20

1.33
ADDED Date and time format is set from Wordpress settings 
ADDED Redirect to URL after successful appointment option
ADDED Form fields settings
IMPROVED Manual appointment adding

16/01/20

1.32
IMPROVED Manual appointment adding


21/11/19

1.31
ADDED Form autoheight option

21/05/19

1.30
ADDED Form fields settings


21/05/19

1.29
ADDED Url to appointment from calendar
ADDED New calendar view in console
FIXED Back button on front


19/04/19

1.28
JS updated

1.27
Improved Scroll to top when click next step on mobile.
Improved Calendar header in form booking on mobile 


19/12/18

1.26 Add employees name in appointments on the dashboard calendar 


07/12/18

1.25 Fixed translation and some styles

09/11/18

1.24 Fixed JS scripts enqueue order


12/10/18

1.23 FIXED when <? tag not working as php tag



28/09/18

1.22 Add timeslot duration 


12/07/18

1.21 

Fixed some CSS styles

11/07/18

1.20 

Added unlimited staffs (employees) number

17/05/18

1.15 

- Fixed installation bug for some servers

10/05/18

1.14 

- Fixed client adding's function 
- Added constant - version of plugin
- Added some styles
- Fixed JS scripts

26/04/18

1.13 

- Added functionality to add clients in admin console 
- Update plugin's style in admin

1.11 

- At now if user choose Today for booking, he can booking only on closest or future time, not past. 
- Added some styles

13/03/18
1.10 Add column time and price in admin menu services

22/02/18
1.09 Fixed some style errors

02/02/18
1.08 	The function of determining the current time is added. Users can book appointment on the future (if today set) only - fixed
	Added extended options for selecting services from employees

29/01/18
1.07 The function of determining the current time is added. Users can book appointment on the future (if today set) only

24/01/18
1.06 PHP 5.4 support

23/01/18
1.05 Added translate to some strings

22/01/18
1.04 Fix theme options by default
1.03 Fix some styles

18/01/18
1.02 Fix some styles, change admin menu order, fix shortcode echoing

10/01/18
1.01 Add prefix to function create_post_type
1.0 First version in repository