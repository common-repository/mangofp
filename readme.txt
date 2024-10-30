=== MangoFp ===
Contributors: andresjarviste
Tags: messages, crm, leads, contact form, contact form 7
Requires at least: 5.2
Tested up to: 5.7
Requires PHP: 7.2
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Manage Contact Form 7 messages directly in WordPress like leads in the CRM system.

== Description ==
Manage Contact Form 7 messages directly in WordPress. Like leads in the CRM system.  You can manage service requests and other form submissions using a customized process that is best for your team.

All incoming form submissions (messages) are gathered in one place. Each lead is always assigned to a process step. Initially, it is **New**. Then you decide what action is needed with the lead and it can move to the step **In Progress** or **Accepted** or something else specific to your process.

Reply via email to customer requests and -orders directly from MangoFp.  Replies that move lead to the next process step can be based on pre-defined email templates. 


== Installation ==
1. Check that you have installed the plugin **Contact Form 7**.
2. You should also have at least one  Contact Form 7 form in use on your WordPress website. If you want to reply to form submissions from MangoFP,  your form(s) should contain an email field. MangoFP recognizes the email field automatically when its name is *your-name* (default for Contact Form 7)
3. Install and activate **MangoFP**
4. In Admin Area open MangoFP->Settings. On tab **Parameters** you can specify **Email field** if that is something else than *your-email*. On the tab "Define process steps" you can modify the process for management of the messages.  See detailed instructions on plugin [webpage](https://mangofp.net)
5. Open your page with a form on your webpage and submit some data.  
6. In Admin Area open MangoFP->Contacts. You should see your form submission as a new message.



== Screenshots ==
1. New form submissions as messages
2. Configure which next steps are allowed for message in any step

== Changelog ==

= 1.0.0 =
Initial version. 

== Plugin source code ==

Plugin source code is located in 3 GitHub repositories:

1. Main php code: [https://github.com/wpkonsult/mangofp.git](https://github.com/wpkonsult/mangofp.git)
2. Front end for messages management: [https://github.com/wpkonsult/mangofp.git](https://github.com/wpkonsult/mangofp.git)
3. Front end for settings and process configuration:  [https://github.com/wpkonsult/mangofp-settings-ts.git](https://github.com/wpkonsult/mangofp-settings-ts.git)
