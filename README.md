# Contact Form 7 Real E-mail Validation

<strong>Description</strong><br>

An add-on for Contact Form 7 that validates the email fields on any CF7 form using the online service at <a href="https://neverbounce.com/" target="_blank">Neverbounce</a>. Please note that a valid account and some credits at Neverbounce are rquired.

When a user subit a CF7 form, CF7 sends the user's email to Neverbounce that checks if it's a valid and real address.

If NeverBounce replies that's ok, the form is submitted. If the address is not valid the user get an error about it.

**Note**
- The plugins consider *valid* addresses those that Neverbounce marked *valid* or *unknown*
- You can activate the *'Consider CatchAll emails as valid emails'* option to consider also valid the addresses marked as *catch-all*

<strong>Installation</strong><br>

1. Install using the Wordpress "Add Plugin" feature -- just search for "Contact Form 7 Never".

2. Confirm that [Contact Form 7 v4.1+](https://wordpress.org/plugins/contact-form-7/) is installed and activated. Then activate this plugin.

3. Go to NeverBounce Integration page from WP Settings menu.

4. Update the settings with the API key.

<strong>IMPORTANT NOTES</strong><br>
This plugin will work with Contact Form 7 v4.1 or higher.

<strong>USAGES</strong><br>
CF7 email field must be in correct format like [email* email-address id:email-address]
