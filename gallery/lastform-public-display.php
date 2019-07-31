<?php
/**
 * Provide a public-facing view for the plugin
 *
 * This file is used to markup the public-facing aspects of the plugin.
 *
 * @link       http://meydjer.com
 * @since      1.0.0
 *
 * @package    Lastform
 * @subpackage Lastform/public/partials
 */

// Load form display class.

require_once( GFCommon::get_base_path() . '/form_display.php' );

// Get form ID.
$form_id = absint( get_query_var('lastform') );

// Get form object.
global $lf_gform_processed;

$resume_link_sent = isset($_POST['gform_send_resume_link']) && $_POST['gform_send_resume_link'] == $form_id;

if ($lf_gform_processed || $resume_link_sent) {
	$lead = GFFormsModel::get_current_lead();

	if ($lf_gform_processed) {
		$form = $lf_gform_processed;
		if ($form['confirmation']['type'] == 'message')
			$form['confirmation']['message'] = GFFormDisplay::get_confirmation_message( $form['confirmation'], $form, $lead );
	} else {
		$form = GFFormsModel::get_form_meta( $form_id );
		$form = GFFormDisplay::update_confirmation( $form, $lead, 'form_save_email_sent' );
	}

	$json_fields = array();

	// Check validation
	$is_valid = true;
	foreach ($form['fields'] as $field) {
		$json_field = new stdClass();
		$json_field->{'id'} = $field->id;
		if ($field->failed_validation) {
			$is_valid = false;
			$message  = !empty($field->validation_message) ? $field->validation_message : esc_html__( 'Invalid value', 'gravityforms' );
			$json_field->{'validationMessage'} = $message;
			$json_fields[] = $json_field;
		}

	}

	// JSON response
	$response = new stdClass();

	// Invalid
	if (!$is_valid) {
		$response->code   = 'invalid';
		$response->fields = $json_fields;
	}

	// Confirmation
	else if ($form['confirmation']) {
		$confirmation = $form['confirmation'];
		$filtered_confirmation = gf_apply_filters( array( 'gform_confirmation', $form_id ), $confirmation, $form, $lead, true );
		if ( ! is_array( $filtered_confirmation ) ) {
			$confirmation['message'] = GFCommon::gform_do_shortcode( $confirmation['message'] ); //enabling shortcodes
		} else if(!empty($filtered_confirmation['redirect'])) {
			$confirmation['type'] = 'redirect';
			$confirmation['url'] = $filtered_confirmation['redirect'];
		}
		$response->code = 'confirmation';
		$response->type = $confirmation['type'];
		switch ($response->type) {
			case 'message':
				$response->message = $confirmation['message'];
				$response->event   = isset($confirmation['event']) ? $confirmation['event'] : 'std';
				// Save and Continue form
				if (in_array($response->event, array('form_saved', 'form_save_email_sent'))) {
					$submission_info             = isset( GFFormDisplay::$submission[ $form_id ] )
						? GFFormDisplay::$submission[ $form_id ]
						: false;
					$options                     = Lastform_Public::get_save_email_input_options($response->message);
					$token                       = $submission_info['resume_token'];
					$email                       = rgpost( 'gform_resume_email' );
					$response->message           = str_replace('{save_email_input}', '{lastform_save_email_input}', $response->message);
					$response->message           = GFFormDisplay::replace_save_variables($response->message, $form, $token, @$email);
				}
				if ($response->event == 'form_saved') {
					$response->token             = $token;
					$response->buttonText        = $options['button_text'];
					$response->validationMessage = $options['validation_message'];
				}
				break;
			case 'page':
				$response->url = get_permalink($confirmation['pageId']);
				break;
			case 'redirect':
				$response->url = $confirmation['url'];
				break;
		}
		if ($confirmation['queryString']) {
			$query_string  = GFCommon::replace_variables( trim($confirmation['queryString']), $form, $lead, false, true, true, 'text' );
			$response->url = $response->url . '?' . $query_string;
		}
	}

	die(json_encode($response));
} else {
	$form = GFFormsModel::get_form_meta( $form_id );
}

// Get form options
$options = lastform_addon()->get_form_settings( $form );


$start_button_text = (!empty($options['welcome-start-button-text'])) ? $options['welcome-start-button-text'] : esc_attr__('Start', 'lastform');

$lf_form_i18n = array(
	'multichoiceTip'       => sprintf(esc_attr__( 'Choose as many as you like and %s press ENTER %s', 'lastform' ), '<strong>', '</strong>'),
	'multichoiceTipMobile' => esc_attr__( 'Choose as many as you like', 'lastform' ),
	'hintKey'              => esc_attr_x( 'Key', 'keyboard key','lastform' ),
	'textareaTip'           => sprintf( esc_attr__( 'To add a paragraph, press %s SHIFT + ENTER %s', 'lastform' ), '<strong>', '</strong>'),
	'uploadButton'         => esc_attr__( 'Upload', 'lastform' ),
	'allowedExtensions'    => esc_attr__( 'Allowed file extensions:', 'lastform' ),
	'rejectedFiles'        => esc_attr__( 'Rejected files:', 'lastform' ),
	'pressEnter'           => sprintf(esc_attr__( 'press %s ENTER %s', 'lastform' ), '<strong>', '</strong>'),
	'checkboxTip'          => esc_html__('Choose as many as you like' , 'lastform'),
	'multiselectTip'       => sprintf( esc_attr__( 'Press %s SHIFT + ENTER %s and choose as many as you like', 'lastform' ), '<strong>', '</strong>'),
	'progressPercentage'   => esc_attr__('$1 completed', 'lastform'),
	'progressProportional' => esc_attr__('$1 of $2 answered', 'lastform'),
	'pageProgress'         => esc_attr__('Step $1 of $2', 'lastform'),
	'submit'               => esc_attr__('Submit', 'lastform'),
	'sendEmail'            => esc_attr__('Send Email', 'lastform'),
	'selectFile'           => esc_attr__( 'Select file', 'gravityforms' ),
	'selectFiles'          => esc_attr__( 'Select files', 'gravityforms' ),
	'dropFilesHere'        => esc_html__( 'Drop files here or', 'gravityforms' ),
	'today'                => esc_html__( 'Today', 'gravityforms' ),
	'or'                   => esc_attr__('or', 'lastform'),
	'prev'                 => esc_attr__('Previous', 'lastform'),
	'next'                 => esc_attr__('Next', 'lastform'),
	'select'               => esc_attr__('Select', 'lastform'),
	'yes'                  => esc_attr__( 'Yes', 'gravityforms' ),
	'no'                   => esc_attr__( 'No', 'gravityforms' ),
	'price'                => esc_html__( 'Price', 'gravityforms' ),
	'quantity'             => esc_html__( 'Quantity', 'gravityforms' ),
);

$lf_form_i18n['errors'] = array(
	'reviewIsNeeded'     => esc_attr__('Some fields need to be reviewed.', 'lastform'),
	'required'           => esc_attr__('You forgot to fill out this field.', 'lastform'),
	'reviewFields'       => esc_attr__('Review Fields', 'lastform'),
	'noDuplicates'       => esc_attr__('It already exists.', 'lastform'),
	'invalidUrl'         => esc_attr__('Enter a valid Website URL, like $1', 'lastform'),
	'rangeNotBetween'    => esc_attr__('Enter a value between $1 and $2.', 'lastform'),
	'rangeBelowExpected' => esc_attr__('Enter a value greater than or equal to $1.', 'lastform'),
	'rangeAboveExpected' => esc_attr__('Enter a value less than or equal to $1.', 'lastform'),
	'invalidEmail'       => esc_html__('Please enter a valid email address.', 'gravityforms'),
	'emailsDoNotMatch'   => esc_html__( 'Your emails do not match.', 'gravityforms' ),
	'maxReached'         => esc_html__( 'Maximum number of files reached' , 'gravityforms' ),
	'fileExceedsLimit'   => esc_html__( 'File exceeds size limit' , 'gravityforms' ),
	'invalid_file'       => esc_html__( 'There was an problem while verifying your file.' ),
	'illegal_extension'  => esc_html__( 'Sorry, this file extension is not permitted for security reasons.' ),
	'illegal_type'       => esc_html__( 'Sorry, this file type is not permitted for security reasons.' ),
	'unknown_error'      => esc_html__( 'There was a problem while saving the file on the server' , 'gravityforms' ),
);

$lf_form = Lastform::camel_case_keys($form);

$lf_form['i18n']          = $lf_form_i18n;
$lf_form['ajaxurl']       = admin_url( 'admin-ajax.php' );
$lf_form['gfUploadUrl']   = home_url( '?gf_page=' . GFCommon::get_upload_page_slug() );
$lf_form['wpnonce']       = wp_create_nonce('is_duplicate-'.$form_id);
$lf_form['renderWelcome'] = true;
$lf_form['state'] = GFFormDisplay::get_state($form, array());

if (rgget('gf_token')) {
	$incomplete_submission_info = GFFormsModel::get_incomplete_submission_values( rgget('gf_token') );

	if ( $incomplete_submission_info['form_id'] == $form_id ) {
		$submission_details_json                  = $incomplete_submission_info['submission'];
		$submission_details                       = json_decode( $submission_details_json, true );
		$partial_entry                            = $submission_details['partial_entry'];
		$submitted_values                         = $submission_details['submitted_values'];
	}
}

if (!empty($lf_form['pagination'])) {
	$lf_form['pagination']['pageObjects'] = array();
}

$has_address_field     = false;
$has_int_address_field = false;
$has_us_address_field  = false;
$has_ca_address_field  = false;
$captcha_lang          = '';

foreach ($lf_form['fields'] as $field_key => $field) {

	if (class_exists('IP2LocationTags')) {
		$ip2 = new IP2LocationTags;

		$lf_form['fields'][$field_key]->label        = $ip2->parse_content($field->label);
		$lf_form['fields'][$field_key]->description  = $ip2->parse_content($field->description);
		$lf_form['fields'][$field_key]->placeholder  = $ip2->parse_content($field->placeholder);
		$lf_form['fields'][$field_key]->defaultValue = $ip2->parse_content($field->defaultValue);
	}

	if ($lf_form['fields'][$field_key]->type == 'page') {
		$lf_form['pagination']['pageObjects'][] = $lf_form['fields'][$field_key];
		unset($lf_form['fields'][$field_key]);
		continue;
	}

	$type     = $lf_form['fields'][$field_key]['type'];
	$value    = $lf_form['fields'][$field_key]['value'];
	$field_id = $lf_form['fields'][$field_key]['id'];

	$lf_form['fields'][$field_key]->label        = GFCommon::replace_variables_prepopulate($field->label);
	$lf_form['fields'][$field_key]->description  = GFCommon::replace_variables_prepopulate($field->description);
	$lf_form['fields'][$field_key]->placeholder  = GFCommon::replace_variables_prepopulate($field->placeholder);
	$lf_form['fields'][$field_key]->defaultValue = GFCommon::replace_variables_prepopulate($field->defaultValue);

	// Address Field found?
	if ($type == 'address') {
		$has_address_field = true;
		if ($field['enableCopyValuesOption']) {
			$inputs = $field['inputs'];

			array_unshift($inputs, array(
				'id'    => $field_id.'_copy_values_activated',
				'label' => $field['copyValuesOptionLabel'],
				'type'  => 'checkbox'
			));

			$lf_form['fields'][$field_key]['inputs'] = $inputs;
		}
		switch ($field['addressType']) {
			case 'international':
				$has_int_address_field = true;
				break;
			case 'us':
				$has_us_address_field  = true;
				// State for US
				$inputs = $field['inputs'];
				foreach ($inputs as $input_index => $input) {
					if (Lastform_Helper::check_sub_id($input['id'], 4))
						$inputs[$input_index]['label'] = esc_html__( 'State', 'gravityforms' );
				}
				$lf_form['fields'][$field_key]['inputs'] = $inputs;
				break;
			case 'canadian':
				$has_ca_address_field  = true;
				// Province for CA
				$inputs = $field['inputs'];
				foreach ($inputs as $input_index => $input) {
					if (Lastform_Helper::check_sub_id($input['id'], 4))
						$inputs[$input_index]['label'] = esc_html__( 'Province', 'gravityforms' );
				}
				$lf_form['fields'][$field_key]['inputs'] = $inputs;
				break;
			default:
				break;
		}
	}

	// Default value
	$default_value      = $lf_form['fields'][$field_key]['defaultValue'];
	$url_param_value    = @$_GET[$lf_form['fields'][$field_key]['inputName']];
	if (!empty($submitted_values))
		$saved_value        = rgar( $submitted_values, $field->id );
	$allows_prepopulate = $lf_form['fields'][$field_key]['allowsPrepopulate'];
	$the_value          = null;

	if (!empty($saved_value)) {
		$the_value = $saved_value;
	} else if ($allows_prepopulate) {
		$the_value = GFFormsModel::get_field_value( $field );
	} else {
		$the_value = $default_value;
	}

	$lf_form['fields'][$field_key]['value'] = $the_value;

	// Index conditional logic relationships
	$conditional_logic = $lf_form['fields'][$field_key]['conditionalLogic'];
	if (!empty($conditional_logic)) {
		foreach ($conditional_logic['rules'] as $rule) {
			$field_id = explode('.', $rule['fieldId']);
			$field_id = $field_id[0];
			if(@!in_array($field->id, $lf_form['conditionalLogicIndex'][$field_id])) {
				$lf_form['fields'][$field_key]->logicDependentFields = $field->id;
			}
		}
	}

	if ($type == 'fileupload' && !empty($submission_details['files'])) {
		foreach ($submission_details['files'] as $input_name => $files) {
			$input_id = explode('_', $input_name);
			$input_id = $input_id[1];
			if ($input_id == $lf_form['fields'][$field_key]['id']) {
				$lf_form['fields'][$field_key]['value'] = $files;
				break;
			}
		}
	}

	// Set Field inputs initial values
	if ($type == 'fileupload' || $type == 'post_image') {
		if (!$field['maxFileSize']) {
			$lf_form['fields'][$field_key]['maxFileSize'] = wp_max_upload_size() / 1048576;
		}
		if (!$field['multipleFiles']) {
			$lf_form['fields'][$field_key]['maxFiles'] = 1;
		}
		if ($field['allowedExtensions']) {
			$extensions = explode(',', $field['allowedExtensions']);
			$mimes      = array();
			foreach ($extensions as $ext) {
				$mimes[] = Lastform_Helper::get_mime_type($ext);
			}
			$lf_form['fields'][$field_key]['accept'] = implode(',', $mimes);
		}
	}

	// Set Field inputs initial values
	if ($type == 'captcha') {
		$public_key = get_option( 'rg_gforms_captcha_public_key' );
		if ($public_key) {
			$lf_form['captchaPublicKey'] = $public_key;
		}
		if ($field['captchaLanguage'])
			$captcha_lang = "window.recaptchaOptions={lang:'{$field['captchaLanguage']}'};";
		if (!$field['captchaTheme'])
			$lf_form['fields'][$field_key]['captchaTheme'] = 'light';
	}

	// Set Field inputs initial values
	$is_name  = $type == 'name';
	$is_email_with_confirmation = $type == 'email' && $lf_form['fields'][$field_key]['emailConfirmEnabled'];
	if ($is_name || $is_email_with_confirmation) {
		$inputs = $lf_form['fields'][$field_key]['inputs'];
		foreach ($inputs as $input_key => $input_value) {
			if (empty($input_value['value']) && !empty($input_value['defaultValue']))
				$inputs[$input_key]['value'] = $input_value['defaultValue'];
		}
		$lf_form['fields'][$field_key]['inputs'] = $inputs;
	}

	if ($type == 'name' && !empty($lf_form['fields'][$field_key]['value'])) {
		$inputs = $lf_form['fields'][$field_key]['inputs'];
		foreach ($inputs as $input_key => $input_value) {
			$input = $inputs[$input_key];
			$input_id = $input_value['id'];
			if (substr($input_id, -1) == 2) {
				foreach ($input['choices'] as $choice_key => $choice_value) {
					if ($choice_value['value'] == $lf_form['fields'][$field_key]['value'][$input_id]) {
						$inputs[$input_key]['choices'][$choice_key]['isSelected'] = 1;
						break;
					}
				}
			} else {
				$inputs[$input_key]['value'] = $lf_form['fields'][$field_key]['value'][$input_id];
			}
		}
		$lf_form['fields'][$field_key]['inputs'] = $inputs;
	}


	if ($type == 'email' && is_array($lf_form['fields'][$field_key]['value'])) {
		$inputs = $lf_form['fields'][$field_key]['inputs'];
		$field_value = $lf_form['fields'][$field_key]['value'];
		foreach ($field_value as $key => $value) {
			$inputs[$key]['value'] = $value;
		}
		$lf_form['fields'][$field_key]['inputs'] = $inputs;
	}

	// Set Field choices initial values
	if ($type == 'select' || $field['inputType'] == 'select') {
		$placeholder = $lf_form['fields'][$field_key]['placeholder'];
		$choices     = $lf_form['fields'][$field_key]['choices'];
		if ($placeholder) {
			array_unshift($choices, array(
				'value' => $placeholder,
				'text'  => $placeholder
			));
			$lf_form['fields'][$field_key]['value']   = $placeholder;
			$lf_form['fields'][$field_key]['choices'] = $choices;
		} else {
			foreach ($choices as $choice) {
				if ($choice['isSelected']) {
					$lf_form['fields'][$field_key]['value'] = $choice['value'];
					break;
				}
			}
		}
	}

	// Set Field choices initial values
	if ($type == 'date') {
		$value = $lf_form['fields'][$field_key]['value'];
		$value = str_replace('--', '', $value);
		if (empty($lf_form['fields'][$field_key]['dateFormat'])) {
			$lf_form['fields'][$field_key]['dateFormat'] = 'mdy';
		}
		if (!empty($value)) {
			if (is_array($value)) {
				switch ( $lf_form['fields'][$field_key]['dateFormat'] ) {
					case 'dmy' :
					case 'dmy_dash' :
					case 'dmy_dot' :
						$new_value = $value[2] . '-' . $value[1] . '-' . $value[0] ;
						break;
					case 'ymd_slash' :
					case 'ymd_dash' :
					case 'ymd_dot' :
						$new_value = $value[0] . '-' . $value[1] . '-' . $value[2] ;
						break;
					// case 'mdy' :
					default :
						$new_value = $value[2] . '-' . $value[0] . '-' . $value[1] ;
						break;
				}
			} else {
				$value = explode('/', $value);
				$new_value = $value[2] . '-' . $value[0] . '-' . $value[1] ;
			}
			$lf_form['fields'][$field_key]['value'] = $new_value;
		} else {
			if ($lf_form['fields'][$field_key]['inputs']) {
				$inputs = $lf_form['fields'][$field_key]['inputs'];
				$date_values = array($inputs[2]['defaultValue'], $inputs[0]['defaultValue'], $inputs[1]['defaultValue']);
				$new_value = implode('-', $date_values);
				if (intval($new_value)) $lf_form['fields'][$field_key]['value'] = $new_value;
			}
		}
	}

	// Categories
	if ( $type == 'post_category' ) {
		if (!$the_value) $the_value = 1;
		$lf_form['fields'][$field_key]['value'] = $the_value;
		$lf_form['fields'][$field_key] = GFCommon::add_categories_as_choices( $field, $the_value);
	}

	// Phone
	if ( $type == 'phone' ) {
		$phone_formats = GF_Fields::get( 'phone' )->get_phone_formats( $form_id );
		$phone_format  = $lf_form['fields'][$field_key]['phoneFormat'];
		$lf_form['fields'][$field_key]['inputMask'] = $phone_formats[$phone_format]['mask'];
	}

	// Set Field choices initial values
	if (($type == 'multiselect' || $field['inputType'] == 'multiselect') && empty($the_value)) {
		$new_value = array();
		$choices   = $lf_form['fields'][$field_key]['choices'];
		foreach ($choices as $choice) {
			if ($choice['isSelected']) {
				$new_value[] = $choice['value'];
			}
		}
		$lf_form['fields'][$field_key]['value'] = $new_value;
	}

	// Set other choice field for radios
	if ($type == 'radio' || $field['inputType'] == 'radio') {
		$choices = $lf_form['fields'][$field_key]['choices'];

		foreach ($choices as $choice_key => $choice_value) {
			if ($choice_value['value'] == $lf_form['fields'][$field_key]['value']) {
				$choices[$choice_key]['isSelected'] = 1;
			}
			if ($choices[$choice_key]['isSelected']) {
				$lf_form['fields'][$field_key]['value'] = $choices[$choice_key]['value'];
			}
		}

		$lf_form['fields'][$field_key]['choices'] = $choices;

		if ($lf_form['fields'][$field_key]['enableOtherChoice']) {
			$lf_form['fields'][$field_key]['otherValue'] = '';

			$choices[] = array(
				'text'       => esc_html__( 'Other', 'gravityforms' ),
				'value'      => 'gf_other_choice',
				'isSelected' => '',
				'price'      => ''
			);

			$lf_form['fields'][$field_key]['choices'] = $choices;
		}
	}


	// Set other choice field for radios
	if (($type == 'checkbox' || $field['inputType'] == 'checkbox') && is_array($lf_form['fields'][$field_key]['value'])) {
		$choices = $lf_form['fields'][$field_key]['choices'];
		$inputs = $lf_form['fields'][$field_key]['inputs'];
		$field_value = $lf_form['fields'][$field_key]['value'];

		foreach ($field_value as $key => $value) {
			if (!empty($value)) {
				foreach ($inputs as $input_key => $input_value) {
					if ($input_value['id'] == $key) {
						$choices[$input_key]['isSelected'] = 1;
						break;
					}
				}
			}
		}

		$lf_form['fields'][$field_key]['choices'] = $choices;
	}

	switch ($type) {
		case 'address':
		case 'name':
			$lf_form['fields'][$field_key]['isComplex'] = 1;
			break;
	}

	// Set initial List field value
	if ($type == 'list' && empty($the_value)) {
		$row = array();
		if ($lf_form['fields'][$field_key]['enableColumns']) {
			$choices = $lf_form['fields'][$field_key]['choices'];
			$columns = array();
			foreach ($choices as $choice) {
				$columns[$choice['value']] = '';
			}
			$row[] = $columns;
		} else {
			$row[] = '';
		}
		$lf_form['fields'][$field_key]['value'] = $row;
	}
}

// Re-index
$lf_form['fields'] = array_values($lf_form['fields']);

$lf_global['siteUrl'] = esc_url(home_url('/'));
$lf_global['dateMinYear'] = apply_filters( 'gform_date_min_year', '1920', $form, $this );
$lf_global['dateMaxYear'] = apply_filters( 'gform_date_max_year', date( 'Y' ) + 1, $form, $this );

if ($lf_form['lastform']['soundEffectsEnabled']) {
	$lf_global['effects'] = array(
		'type' => 'std',
		'mp3'  => Lastform::plugin_url().'public/audio/lastform-audio-sx.mp3'
	);
}

if (empty($lf_form['lastform']['welcomeEnabled']))
	$lf_form['lastform']['welcomeEnabled'] = 0;

if (empty($lf_form['save']['enabled']))
	$lf_form['save']['enabled'] = 0;

/**
 * Form Google Fonts
 */

$google_font_url = null;
if (!empty($lf_form['lastform']['googleFontCode'])) {
	$google_font_code = str_replace('+', ' ', $lf_form['lastform']['googleFontCode']);

	$google_font_url = add_query_arg( 'family', urlencode( $google_font_code ), "//fonts.googleapis.com/css" );
}

/**
 * Stripe
 */

$is_stripe_form = false;
if (class_exists('GFStripe')) {
    $stripe = GFStripe::get_instance();
    $is_stripe_form = $stripe->frontend_script_callback( $form );
}

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
  <head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, minimum-scale=1, user-scalable=no">
	<meta name="viewport" id="vp" content="initial-scale=1.0,user-scalable=no,maximum-scale=1" media="(device-height: 568px)">
	<?php if ( ! empty($form['description']) ) : ?>
		<meta name="description" content="<?php echo $form['description'] ?>">
	<?php endif ?>
	<title><?php echo apply_filters('lastform_public_display_page_title', $form['title'], $form_id) ?></title>
	<?php if (!empty($options['favicon-url'])) : ?>
		<link rel="icon" href="<?php echo $options['favicon-url'] ?>">
	<?php endif ?>
	<?php if (!empty($options['custom-css-code'])) : ?>
	<style>
		<?php echo $options['custom-css-code'] ?>
	</style>
	<?php endif ?>
	<?php if (!empty($options['custom-html-code'])) echo $options['custom-html-code'] ?>
	<?php if ($google_font_url) : ?>
		<link rel="stylesheet" href="<?php echo $google_font_url ?>">
	<?php endif ?>
	<?php if (!empty($lf_form['lastform']['welcomeImageUrl'])) : ?>
		<meta property="og:image" content="<?php echo $lf_form['lastform']['welcomeImageUrl'] ?>" />
	<?php endif ?>
	<?php if ($is_stripe_form) : ?>
		<script type='text/javascript' src="https://js.stripe.com/v2/"></script>
		<script type='text/javascript'>var stripePublishableApiKey = '<?php echo $stripe->get_publishable_api_key() ?>';</script>
	<?php endif ?>

	<?php

	// Check loggin
	if (rgar($form,'requireLogin') && !is_user_logged_in())
		wp_die($form['requireLoginMessage']);

	// Check entry limit
	$entry_limit = GFFormDisplay::validate_entry_limit( $form );
	if (!empty($entry_limit))
		wp_die($entry_limit);

	// Check form schedule
	$schedule_not_passed = GFFormDisplay::validate_form_schedule( $form );
	if (!empty($schedule_not_passed))
		wp_die($schedule_not_passed);

	?>

	<script>
		var lfIsMobile = <?php echo (int)wp_is_mobile() ?>;
		var lfForm = <?php echo json_encode($lf_form) ?>;
		var lfGlobal = <?php echo json_encode($lf_global) ?>;
		<?php
		if ($has_address_field) :
			$field_address = new GF_Field_Address;
			if ($has_int_address_field)
				echo 'var lfCountries = '.json_encode($field_address->get_countries()).';' ;
			if ($has_us_address_field)
				echo 'var lfUsStates = '.json_encode($field_address->get_us_states()).';' ;
			if ($has_ca_address_field)
				echo 'var lfCaProvinces = '.json_encode($field_address->get_canadian_provinces()).';' ;
		endif; ?>
		<?php echo $captcha_lang; ?>
		<?php GFCommon::gf_global(); ?>
	</script>

	<?php if (!empty($options['custom-js-code'])) : ?>
	<script  type="text/javascript">
		<?php echo $options['custom-js-code'] ?>
	</script>
	<?php endif ?>

<style>
.lf-hotkey-key, .lf-hotkey-key > span {
display:none;
}
#header.transparent-header {
	z-index: 199 !important;
}
.lf-li-choice.lf-choice-with-hotkey > span {
padding-left:0rem;
}

#lastform {
	margin-top: -100px !important;
}
.disply_none {
	visibility: hidden;
}
.lf-footer {
	display: block !important;
}
</style>


<!-- Stylesheets
============================================= -->
<link href="https://fonts.googleapis.com/css?family=Encode+Sans+Expanded:200,300,400,500,600,700|Nunito:300,400,600,700|Kodchasan:200,300,400,500|Mallanna|Mukta:200,300,400,500,600,700,800|Quicksand:300,400,500,700|Sirin+Stencil|Syncopate:400,700|Varela+Round|Montserrat:100,200,300,400,500,600,700,800,900"
	rel="stylesheet" type="text/css">
<link rel="stylesheet" href="https://www.cashforcameras.com/css/bootstrap.css" type="text/css" />
<link rel="stylesheet" href="https://www.cashforcameras.com/style.css" type="text/css" />
<link rel="stylesheet" href="https://www.cashforcameras.com/css/dark.css" type="text/css" />
<link rel="stylesheet" href="https://www.cashforcameras.com/css/font-icons.css" type="text/css" />
<link rel="stylesheet" href="https://www.cashforcameras.com/css/animate.css" type="text/css" />
<link rel="stylesheet" href="https://www.cashforcameras.com/css/magnific-popup.css" type="text/css" />

<link rel="stylesheet" href="https://www.cashforcameras.com/css/responsive.css" type="text/css" />


	<?php Lastform_Public::print_form_inline_style($form) ?>
</head>
<body class="stretched overlay-menu disply_none">

	<!-- Document Wrapper
	============================================= -->
	<div id="wrapper" class="clearfix">

		<!-- Header
		============================================= -->
		<header id="header" class="transparent-header">

			<div id="header-wrap">

				<div class="container clearfix">

					<div id="primary-menu-trigger"><i class="icon-line-menu"></i></div>

					<!-- Logo
					============================================= -->
					<div id="logo">
						<a href="https://www.cashforcameras.com/" class="standard-logo"><img src="https://www.cashforcameras.com/images/cashforcameras-logo.png" alt="CashForCameras Logo"></a>
						<a href="https://www.cashforcameras.com/" class="retina-logo"><img src="https://www.cashforcameras.com/images/cashforcameras-logo.png" alt="CashForCameras Logo"></a>
					</div><!-- #logo end -->

					<!-- Primary Navigation
					============================================= -->
					<nav id="primary-menu">

						<ul>
							<li><a href="https://www.cashforcameras.com/">
									<div>Home</div>
								</a></li>
							<li class="current"><a href="https://www.cashforcameras.com/cash-offer-form/a/1">
									<div>Cash Offer Form</div>
								</a></li>
							<li><a href="https://www.cashforcameras.com/how-it-works">
									<div>How it Works</div>
								</a></li>
							<li><a href="https://www.cashforcameras.com/frequently-asked-questions">
									<div>Frequently Asked Questions</div>
								</a></li>
							<li><a href="https://www.cashforcameras.com/customer-reviews">
									<div>Customer Reviews</div>
								</a></li>
							<li><a href="https://www.cashforcameras.com/about-us">
									<div>About Us</div>
								</a></li>
							<li><a href="https://www.cashforcameras.com/our-environmental-mission">
									<div>Our Environmental Mission</div>
								</a></li>
							<li><a href="https://www.cashforcameras.com/contact-us">
									<div>Contact Us</div>
								</a></li>
						</ul>

						<a href="#" id="overlay-menu-close" class="d-none d-lg-block"><i class="icon-line-cross" id="x-to-close"></i></a>

					</nav><!-- #primary-menu end -->

				</div>

			</div>

		</header><!-- #header end -->
</div>

<div id="lastform" class="lastform <?php if (!empty($lf_form['cssClass'])) echo $lf_form['cssClass'] ?>"></div>

<script type='text/javascript' src="<?php echo Lastform::plugin_url() ?>public/js/lastform-public.min.js?ver=<?php echo $this->_version ?>"></script>
<script src="https://www.cashforcameras.com/js/jquery.js"></script>
<script src="https://www.cashforcameras.com/js/plugins.js"></script>

<script src="https://www.cashforcameras.com/js/functions.js"></script>


<script>

$(document).ready(function(){

	$('body').removeClass('disply_none');

	$('body').on("DOMSubtreeModified", ".Select-control", function (event) {

		var event_data  = event.target.outerHTML;
		event_data = event_data.split('<div class="');
		event_data = event_data[1].split('"')
		event_data = event_data[0]
		if (event_data == 'Select-value'){
		setTimeout(function(){
			$('.lf-nav-inner-next').trigger( "click" );
		}, 100);
	}
	});

	$( "body" ).on('change','.lf-input-select',function() {

				$('.lf-nav-inner-next').trigger( "click" );
});

});

</script>

</body>
</html>
