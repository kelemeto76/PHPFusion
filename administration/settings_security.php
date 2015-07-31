<?php
/*-------------------------------------------------------+
| PHP-Fusion Content Management System
| Copyright (C) PHP-Fusion Inc
| https://www.php-fusion.co.uk/
+--------------------------------------------------------+
| Filename: settings_security.php
| Author: Paul Beuk (muscapaul)
+--------------------------------------------------------+
| This program is released as free software under the
| Affero GPL license. You can redistribute it and/or
| modify it under the terms of this license which you
| can read by viewing the included agpl.txt or online
| at www.gnu.org/licenses/agpl.html. Removal of this
| copyright header is strictly prohibited without
| written permission from the original author(s).
+--------------------------------------------------------*/
require_once "../maincore.php";
pageAccess('S9');
require_once THEMES."templates/admin_header.php";
include LOCALE.LOCALESET."admin/settings.php";
// maybe people will delete off user fields.
$captcha_locale_file = LOCALE.LOCALESET."user_fields/user_grecaptcha.php";
if (file_exists($captcha_locale_file)) {
	include $captcha_locale_file;
}
add_breadcrumb(array('link'=>ADMIN."settings_security.php".$aidlink, 'title'=>$locale['security_settings']));
$available_captchas = array();
if ($temp = opendir(INCLUDES."captchas/")) {
	while (FALSE !== ($file = readdir($temp))) {
		if ($file != "." && $file != ".." && is_dir(INCLUDES."captchas/".$file)) {
			$available_captchas[$file] = $locale[$file];
		}
	}
}
if (isset($_POST['savesettings'])) {
	$error = 0; // there is no need for this.

	if (!defined('FUSION_NULL')) {

		// Custom stuff
		$privacy_policy = addslash(preg_replace("(^<p>\s</p>$)", "", $_POST['privacy_policy']));
		$maintenance_message = addslash(descript($_POST['maintenance_message']));
		// Save settings after validation
		$StoreArray = array(
						"captcha" => form_sanitizer($_POST['captcha'], "", "captcha"),
						"privacy_policy" => $privacy_policy,
						"flood_interval" => form_sanitizer($_POST['flood_interval'], 15, "flood_interval"),
						"flood_autoban" => form_sanitizer($_POST['flood_autoban'], 1, "flood_autoban"),
						"maintenance_level" => form_sanitizer($_POST['maintenance_level'], 102, "maintenance_level"),
						"maintenance" => form_sanitizer($_POST['maintenance'], 0, "maintenance"),
						"maintenance_message" => form_sanitizer($_POST['maintenance_message'], "", "maintenance_message"),
						"bad_words_enabled" => form_sanitizer($_POST['bad_words_enabled'], 0, "bad_words_enabled"),
						"bad_words" => form_sanitizer($_POST['bad_words'], "", "bad_words"),
						"bad_word_replace" => form_sanitizer($_POST['bad_word_replace'], "", "bad_word_replace"),
			);

		// Validate extra fields
		if ($StoreArray['captcha'] == "grecaptcha") {
			// appends captcha settings
			$StoreArray += array(
				"recaptcha_public" => form_sanitizer($_POST['recaptcha_public'], "", "recaptcha_public"),
				"recaptcha_private" => form_sanitizer($_POST['recaptcha_private'], "", "recaptcha_private"),
				"recaptcha_theme" => form_sanitizer($_POST['recaptcha_theme'], "", "recaptcha_theme"),
				"recaptcha_time" => form_sanitizer($_POST['recaptcha_time'], "", "recaptcha_time"),
			);
		}

		if ($defender->safe()) {
			foreach($StoreArray as $key => $value) {
				$result = null;
				if ($defender->safe()) {
					$Array = array(
						"settings_name" => $key,
						"settings_value" => $value,
					);
					dbquery_insert(DB_SETTINGS, $Array, 'update', array("primary_key"=>"settings_name"));
				}
				print_p($key.'-'.$value);
			}
			addNotice('success', $locale['900']);
		} else {
			// send message your settings was not safe. :)
			addNotice('danger', $locale['901']);
			addNotice('danger', $locale['696']);
			addNotice('danger', $locale['900']);
		}
		//redirect(FUSION_SELF.$aidlink);
	}
}

/**
 * Upgrade for Beta Testers
 */
$recaptcha_upgrade = dbcount("(settings_name)", DB_SETTINGS, "settings_name = 'recaptcha_time'");
if (!$recaptcha_upgrade) {
	$newSettings = array(
		"settings_name" => "recaptcha_time",
		"settings_value" => 5,
	);
	dbquery_insert(DB_SETTINGS, $newSettings, "save",  array("primary_key"=>"settings_name", "keep_session"=>true));
	addNotice("success", "System reCaptcha is now upgraded to v2");
}

opentable($locale['683']);
echo "<div class='well'>".$locale['security_description']."</div>\n";
echo openform('settingsform', 'post', FUSION_SELF.$aidlink, array('max_tokens' => 1));
echo "<div class='row'>\n";
echo "<div class='col-xs-12 col-sm-8'>\n";
openside('');
echo form_select('captcha', $locale['693'], $available_captchas, fusion_get_settings('captcha'), array('inline'=>1));
echo "<div id='extDiv' ".(fusion_get_settings('captcha') !== 'grecaptcha' ? "style='display:none;'" : '').">\n";
if (!fusion_get_settings("recaptcha_public")) {
	echo "<div class='alert alert-warning col-sm-offset-3'><i class='fa fa-google fa-lg fa-fw'></i> ".$locale['no_keys']."</div>\n";
}
echo "<div class='row'>\n";
echo "<div class='hidden-xs col-sm-3 text-right'>\n";
echo thumbnail(IMAGES."grecaptcha.png", "150px");
echo "</div>\n<div class='col-xs-12 col-sm-9'>\n";
echo form_text('recaptcha_public', $locale['grecaptcha_0100'], fusion_get_settings('recaptcha_public'), array("inline"=>true, "placeholder"=>$locale['grecaptcha_placeholder_1'], "required"=>true)); // site key
echo form_text('recaptcha_private', $locale['grecaptcha_0101'], fusion_get_settings('recaptcha_private'), array("inline"=>true, "placeholder"=>$locale['grecaptcha_placeholder_2'], "required"=>true)); // secret key
echo form_select('recaptcha_theme', $locale['grecaptcha_0102'], array(
									  'light' => $locale['grecaptcha_0102a'], 'dark' => $locale['grecaptcha_0102b']
								  ),
				 fusion_get_settings('recaptcha_theme'), array("inline"=>true));
echo form_text('recaptcha_time', $locale['grecaptcha_0103'], fusion_get_settings('recaptcha_time'), array("inline"=>true, "type"=>"number", "width"=>"150px", "placeholder"=>$locale['grecaptcha_placeholder_3'], "required"=>true));
echo "</div>\n</div>\n";
echo "</div>\n";
closeside();
openside('');
$level_array = array(USER_LEVEL_ADMIN => $locale['676'], USER_LEVEL_SUPER_ADMIN => $locale['677'], USER_LEVEL_MEMBER => $locale['678']);
echo form_select('maintenance_level', $locale['675'], $level_array, fusion_get_settings('maintenance_level'), array('inline'=>1, 'width'=>'100%'));
$opts = array('1' => $locale['502'], '0' => $locale['503']);
echo form_select('maintenance', $locale['657'],  $opts, fusion_get_settings('maintenance'), array('inline'=>1, 'width'=>'100%'));
echo form_textarea('maintenance_message', $locale['658'], fusion_get_settings('maintenance_message'), array('autosize'=>1));
closeside();
openside('');
echo form_textarea('privacy_policy', $locale['820'], fusion_get_settings('privacy_policy'), array('autosize'=>1, 'form_name'=>'settingsform', 'html'=>!fusion_get_settings('tinymce_enabled') ? true : false));
closeside();
echo "</div><div class='col-xs-12 col-sm-4'>\n";
openside('');
$flood_opts = array('1' => $locale['502'], '0' => $locale['503']);
echo form_text('flood_interval', $locale['660'], fusion_get_settings('flood_interval'), array('max_length' => 2));
echo form_select('flood_autoban', $locale['680'], $flood_opts, fusion_get_settings('flood_autoban'), array('width'=>'100%'));
closeside();
openside('');
$yes_no_array = array('1' => $locale['518'], '0' => $locale['519']);
echo form_select('bad_words_enabled', $locale['659'], $yes_no_array, fusion_get_settings('bad_words_enabled'));
echo form_text('bad_word_replace', $locale['654'], fusion_get_settings('bad_word_replace'));
echo form_textarea('bad_words', $locale['651'], fusion_get_settings('bad_words'), array('placeholder'=>$locale['652'], 'autosize'=>1));
closeside();
echo "</div>\n</div>\n";
echo form_button('savesettings', $locale['750'], $locale['750'], array('class' => 'btn-success'));
echo closeform();
closetable();
add_to_jquery("
$('#captcha').bind('change', function() {
	var val = $(this).select2().val();
	if (val == 'grecaptcha') {
		$('#extDiv').slideDown('slow');
	} else {
		$('#extDiv').slideUp('slow');
	}
});
");
require_once THEMES."templates/footer.php";