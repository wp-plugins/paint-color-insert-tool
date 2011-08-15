<?php
/*
  Plugin Name: Paint Color Inserter Tool
  Plugin URI: http://www.myperfectcolor.com/
  Description: Insert a button into the editor which opens a popup window letting users insert images and links of paint color matches from over 100 paint brands. Includes all colors/schemes and products from MyPerfectColor.com. Very useful time saving plugin for bloggers who write about interior design, architecture and color.
  Author: MyPerfectColor
  Version: 1.1
  Author URI: http://www.myperfectcolor.com/
 */

/**
* @package Paint Color Inserter Tool
* @copyright Copyright (C) 2011 My Perfect Color. All rights reserved.
* @version 1.0
*/ 

$siteURL = 'http://www.myperfectcolor.com/';
$imageHost = "http://images.myperfectcolor.com/";

$imageSizes = array('thumbnail' => '-0', 'medium' => '-1', 'large' => '-2');
$colorComment = '<!-- Colors inserted via the MyPerfectColor Paint Color Inserter -->';
$schemeComment = '<!-- Scheme inserted via the MyPerfectColor Paint Color Inserter -->';
$productComment = '<!-- Product inserted via the MyPerfectColor Paint Color Inserter -->';

if (!defined('ABSPATH'))
	die("Can't load this file directly");

class PaintColorInserter {

	function __construct() {
		add_action('admin_init', array($this, 'action_admin_init'));
	}
	
	function action_admin_init() {
		if (current_user_can('edit_posts')) {
			add_filter('mce_buttons', array($this, 'filter_mce_button'));
			add_filter('mce_external_plugins', array($this, 'filter_mce_plugin'));
		}
	}

	function filter_mce_button($buttons) {
		array_push($buttons, '|', 'paint_color_inserter_button');
		return $buttons;
	}

	function filter_mce_plugin($plugins) {
		$plugins['paint_color_inserter'] = plugin_dir_url(__FILE__) . 'paintColorInserterPlugin.js';
		return $plugins;
	}

}

$paintColorInserter = new PaintColorInserter();

wp_register_script( 'pci_settings',plugin_dir_url(__FILE__) . 'pci_settings.js');
wp_localize_script( 'pci_settings', 'settings', array('pci_email' => get_option('pci_email'),'plugin_dir' => plugin_dir_url(__FILE__)));
wp_enqueue_script( 'pci_settings' );

wp_register_script( 'pci_pagination',plugin_dir_url(__FILE__) . 'pagination.js');
wp_enqueue_script( 'pci_pagination');

add_shortcode('mpc-paint-color-insert-tool', 'mpc_paint_color_inserter_shortcode');
add_shortcode('mpc-paint-color-inserter', 'mpc_paint_color_inserter_shortcode');
add_action('admin_menu', 'paint_color_inserter_menu');

function paint_color_inserter_menu() {
	add_options_page('Paint Color Insert Tool Options', 'Paint Color Insert Tool', 'manage_options', 'mpc-paint-color-inserter', 'paint_color_inserter_options');
}

function paint_color_inserter_options() {

	if (!current_user_can('manage_options')) {
		wp_die(__('You do not have sufficient permissions to access this page.'));
	}

	// variables for the field and option names
	$opt_name = 'pci_email';
	$hidden_field_name = 'pci_submit_hidden';
	$data_field_name = 'pci_email';

	// Read in existing option value from database
	$opt_val = get_option($opt_name);

	// See if the user has posted us some information
	// If they did, this hidden field will be set to 'Y'
	if (isset($_POST[$hidden_field_name]) && $_POST[$hidden_field_name] == 'Y') {

		// Read their posted value
		$opt_val = $_POST[$data_field_name];


		if(is_email($opt_val)){

			// Save the posted value in the database
			update_option($opt_name, $opt_val);
			// Put a settings updated message on the screen
			?><div class="updated"><p><strong><?php _e('Settings saved.', 'menu-test'); ?></strong></p></div><?php
		}else{
			?><div class="error"><p><strong><?php _e('The email is not valid.', 'menu-test'); ?></strong></p></div><?php
		}
	}

	// Now display the settings editing screen

	echo '<div class="wrap">';
	// header
	echo "<h2>" . __('Paint Color Insert Tool Plugin Settings', 'menu-test') . "</h2>";

	// settings form
	?>

		<form name="form1" method="post" action="">
			<input type="hidden" name="<?php echo $hidden_field_name; ?>" value="Y">

			<p><?php _e("Email:", 'menu-test'); ?>
				<input type="text" name="<?php echo $data_field_name; ?>" value="<?php echo $opt_val; ?>" size="20">
			</p><hr />

			<p class="submit">
				<input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes') ?>" />
			</p>

		</form>
		</div>
	<?php
}

function mpc_paint_color_inserter_shortcode($atts, $content = null) {

	global $colorComment;
	global $schemeComment;
	global $productComment;

	extract(shortcode_atts(array(
				'id' => 'default',
				'name' => 'default',
				'brand' => 'default',
				'title' => 'default',
				'colornumber' => 'default',
				'colorcode' => 'default',
				'type' => 'default',
				'imgsize' => 'thumbnail',
				'tab' => 'color',
				'imgcode' => 'default',
					), $atts));

	$item->id = $id;
	$item->name = $name;
	$item->title = $title;
	$item->type = $type;
	$item->tab = $tab;
	$item->imageSize = $imgsize;

	switch ($item->tab) {

		case 'scheme':
			$item->name = $title;
			$data = getSchemeReferenceLinks($item);
			$comment = $schemeComment;
			break;

		case 'product':
			$item->imageCode = $imgcode;
			$data = getProductReferenceLinks($item);
			$comment = $productComment;
			break;

		case 'color':
		default:
			$item->brand = $brand;
			$item->colorNumber = $colornumber;
			$item->colorCode = $colorcode;

			$comment = $colorComment;
			$data = getColorReferenceLinks($item);
			break;
	}


	// type tells wich type of short code is required
	return $comment . $data[$item->type];
}

function getColorReferenceLinks($color) {
	global $siteURL;
	global $imageHost;
	global $imageSizes;


	$data['url'] = $siteURL . "en/color/" . $color->id . "_";
	if (isset($color->title) && $color->title != '')
		$data['url'] .= replaceChars($color->title);
	else
		$data['url'] .= replaceChars($color->brand . " " . $color->colorNumber . " " . $color->name);

	$data['image-url'] = $imageHost . "repositories/images/colors/" . $color->colorCode . $imageSizes[$color->imageSize] . ".jpg";

	if (isset($color->title) && $color->title != '') {
		$data['title'] = '<a title="' . $color->title . ' at MyPerfectColor" href="' . $data['url'] . '">' . $color->title . ' at MyPerfectColor</a>';
		$data['image'] = '<a title="' . $color->title . ' at MyPerfectColor" href="' . $data['url'] . '"><img src="' . $data['image-url'] . '" alt="' . $color->title . ' at MyPerfectColor"></a>';
		$data['image-and-title'] = '<a title="' . $color->title . ' at MyPerfectColor" href="' . $data['url'] . '"><img src="' . $data['image-url'] . '" alt="' . $color->title . ' at MyPerfectColor"></a>' . '<br/>' . '<a title="' . $color->title . ' at MyPerfectColor" href="' . $data['url'] . '">' . $color->title . ' at MyPerfectColor</a>';
	} else {
		$data['title'] = '<a title="' . $color->brand . ' ' . $color->colorNumber . ' ' . $color->name . ' at MyPerfectColor" href="' . $data['url'] . '">' . $color->brand . ' ' . $color->colorNumber . ' ' . $color->name . ' at MyPerfectColor</a>';
		$data['image'] = '<a title="' . $color->brand . ' ' . $color->colorNumber . ' ' . $color->name . ' at MyPerfectColor" href="' . $data['url'] . '"><img src="' . $data['image-url'] . '" alt="' . $color->brand . ' ' . $color->colorNumber . ' ' . $color->name . ' at MyPerfectColor"></a>';
		$data['image-and-title'] = '<a title="' . $color->brand . ' ' . $color->colorNumber . ' ' . $color->name . ' at MyPerfectColor" href="' . $data['url'] . '"><img src="' . $data['image-url'] . '" alt="' . $color->brand . ' ' . $color->colorNumber . ' ' . $color->name . ' at MyPerfectColor"></a>' . '<br/>' . '<a title="' . $color->brand . ' ' . $color->colorNumber . ' ' . $color->name . ' at MyPerfectColor" href="' . $data['url'] . '">' . $color->brand . ' ' . $color->colorNumber . ' ' . $color->name . ' at MyPerfectColor</a>';
	}

	return $data;
}

function getSchemeReferenceLinks($scheme) {
	global $siteURL;
	global $imageHost;
	global $imageSizes;

	$data['url'] = $siteURL . "en/scheme/" . $scheme->id . "_";

	$data['url'] .= replaceChars($scheme->name);

	$data['image-url'] = $imageHost . "repositories/images/schemes/scheme-" . $scheme->id . $imageSizes[$scheme->imageSize] . ".jpg";

	if (isset($scheme->title) && $scheme->title != '') {
		$data['title'] = '<a title="' . $scheme->title . ' at MyPerfectColor" href="' . $data['url'] . '">' . $scheme->title . ' at MyPerfectColor</a>';
		$data['image'] = '<a title="' . $scheme->title . ' at MyPerfectColor" href="' . $data['url'] . '"><img src="' . $data['image-url'] . '" alt="' . $scheme->title . ' at MyPerfectColor"></a>';
		$data['image-and-title'] = '<a title="' . $scheme->title . ' at MyPerfectColor" href="' . $data['url'] . '"><img src="' . $data['image-url'] . '" alt="' . $scheme->title . ' at MyPerfectColor"></a>' . '<br/>' . '<a title="' . $scheme->title . ' at MyPerfectColor" href="' . $data['url'] . '">' . $scheme->title . ' at MyPerfectColor</a>';
	} else {
		$data['title'] = '<a title="Scheme at MyPerfectColor" href="' . $data['url'] . '">' . $scheme->name . ' at MyPerfectColor</a>';
		$data['image'] = '<a title="Scheme at MyPerfectColor" href="' . $data['url'] . '"><img src="' . $data['image-url'] . '" alt="Scheme at MyPerfectColor"></a>';
		$data['image-and-title'] = '<a title="Scheme at MyPerfectColor" href="' . $data['url'] . '"><img src="' . $data['image-url'] . '" alt="Scheme at MyPerfectColor"></a>' . '<br/>' . '<a title="Scheme at MyPerfectColor" href="' . $data['url'] . '">' . $scheme->name . ' at MyPerfectColor</a>';
	}

	return $data;
}

function getProductReferenceLinks($product) {
	global $siteURL;
	global $imageHost;
	global $imageSizes;

	$data['url'] = $siteURL . "en/product/" . $product->id . "_" . replaceChars($product->name);
	$data['url'] .= replaceChars($scheme->name);

	$data['title'] = '<a title="' . $product->name . ' at MyPerfectColor" href="' . $data['url'] . '">' . $product->name . ' at MyPerfectColor</a>';
	if (!strpos($product->imgCode, 'no_image')) {
		$data['image-url'] = $imageHost . 'repositories/images/products/' . $product->imageCode . $imageSizes[$product->imageSize] . ".jpg";
		$data['image'] = '<a title="' . $product->name . ' at MyPerfectColor" href="' . $data['url'] . '"><img src="' . $data['image-url'] . '" alt="' . $product->name . ' at MyPerfectColor"></a>';
		$data['image-and-title'] = '<a title="' . $product->name . ' at MyPerfectColor" href="' . $data['url'] . '"><img src="' . $data['image-url'] . '" alt="' . $product->name . ' at MyPerfectColor"></a>' . '<br/>' . '<a title="' . $product->name . ' at MyPerfectColor" href="' . $data['url'] . '">' . $product->name . ' at MyPerfectColor</a>';
	}

	return $data;
}

/**
 * Replace special characters to use into URLs
 * @param $text
 * @return string
 */
function replaceChars($text) {
	$text = strip_tags($text); // if it has HTML
	$text = trim($text);
	$arr_find = array(" - ", " ", "&reg;", "®", "&trade;", "trade;", "™", "\"", "'", "á", "à", "â", "ã", "ª", "Á", "À",
		"Â", "Ã", "é", "è", "ê", "É", "È", "Ê", "í", "ì", "î", "Í",
		"Ì", "Î", "ò", "ó", "ô", "õ", "º", "Ó", "Ò", "Ô", "Õ", "ú",
		"ù", "û", "Ú", "Ù", "Û", "ç", "Ç", "Ñ", "ñ", "&",);
	$arr_replace = array(" ", "-", "", "", "", "", "", "", "", "a", "a", "a", "a", "a", "A", "A",
		"A", "A", "e", "e", "e", "E", "E", "E", "i", "i", "i", "I",
		"I", "I", "o", "o", "o", "o", "o", "O", "O", "O", "O", "u",
		"u", "u", "U", "U", "U", "c", "C", "N", "n", "");
	$text = str_replace($arr_find, $arr_replace, $text);
	return ereg_replace('[^A-Za-z0-9\_\.\-]', '', $text);
}