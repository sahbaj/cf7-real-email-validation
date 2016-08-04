
<h2>Never Bounce API Settings</h2>

<form method="post" action="options.php">
	<?php
		settings_fields( 'cf7cfv_keys_section' );
		do_settings_sections( 'cf7cfv_api_options' );
		submit_button();
	?>
</form>
