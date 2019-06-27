<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
/**
 * Hints Class
 *
 * Provides hints to admins about GD and its settings.
 *
 * @version 2.0.0
 * @since 2.0.0.6x
 */
class GeoDir_Hints {

	public function __construct() {

		add_filter('geodir_notifications',array($this,'templates'));
	}

	public function templates($notifications){

		if(geodir_is_page('post_type') || geodir_is_page('archive')){
			// info
			$notifications['gd-hint-archive-template'] = array(
				'type'  =>  'info',
				'note'  =>  self::archive_template(),
				'dismissible'  =>  true
			);
		}elseif(geodir_is_page('single')){
			$notifications['gd-hint-single-template'] = array(
				'type'  =>  'info',
				'note'  =>  self::single_template(),
				'dismissible'  =>  true
			);
		}elseif(geodir_is_page('search')){
			$notifications['gd-hint-search-template'] = array(
				'type'  =>  'info',
				'note'  =>  self::search_template(),
				'dismissible'  =>  true
			);
		}elseif(geodir_is_page('location')){
			$notifications['gd-hint-location-template'] = array(
				'type'  =>  'info',
				'note'  =>  self::location_template(),
				'dismissible'  =>  true
			);
		}

		return $notifications;
	}

	/**
	 * Format the hints.
	 *
	 * @param $hints
	 * @param string $docs_url
	 * @param string $video_url
	 * @param string $feedback_id
	 *
	 * @return string
	 */
	public function format_hints($hints, $docs_url = '',$video_url = '',$feedback_id = ''){
		$text = '';

		if(is_array($hints)){
			$text .= "<ul class='gd-hints-list' style='margin: 0;padding: 0 2em;'>";
			foreach($hints as $hint){
				$text .= "<li>".$hint."</li>";
			}
			$text .= "</ul>";
		}else{
			$text .= $hints;
		}

		$feedback_url = $feedback_id ? "https://wpgeodirectory.com/support/forum/geodirectory-core-plugin-forum/general-discussion/?feedback=$feedback_id#new-post" : "https://wpgeodirectory.com/support/forum/geodirectory-core-plugin-forum/general-discussion/#new-post";

		if($text){
			$help_links = array();
			$help_links[] = "<a href='".admin_url( 'admin.php?page=gd-settings&tab=general&section=developer#enable_hints' )."'>".__("Disable hints","geodirectory")."</a>";
			$docs_url ? $help_links[] = "<i class=\"fas fa-book\"></i> <a href='$docs_url' target='_blank'>".__("Documentation","geodirectory")."</a>" : '';
			$video_url ? $help_links[] = "<i class=\"fas fa-video\"></i> <a href='$video_url' target='_blank' data-lity>".__("Video","geodirectory")."</a>" : '';
			$feedback_id ? $help_links[] = "<i class=\"fas fa-comment\"></i> <a href='$feedback_url' target='_blank'>".__("Feedback","geodirectory")."</a>" : '';
			$text = "<b>".__("Admin Hints:","geodirectory")."</b> " . implode(" | ",$help_links) . $text;
		}

		return $text;
	}


	/**
	 * Hints for archive templates.
	 *
	 * @return string
	 */
	public function archive_template(){
		$hints = array();

		$edit_page_link = get_edit_post_link();
		$hints[] = wp_sprintf(
			__("This is a archive page, its design can be changed via the page template %shere%s.","geodirectory"),
			"<a href='$edit_page_link'>",
			"</a>"
		);


		$hints[] = wp_sprintf(
			__("You can use shortcodes or blocks to build the page or some page builders such as %sBeaver Themer or Elementor Pro.%s","geodirectory"),
			"<a href='https://wpgeodirectory.com/docs-v2/integrations/builders/#theme-builders'>",
			"</a>"
		);

		$archive_item_page_id = geodir_archive_item_page_id();
		$edit_page_link = get_edit_post_link($archive_item_page_id);
		$hints[] = wp_sprintf(
			__("Individual post loop content can be changed in the archive item page template %shere%s.","geodirectory"),
			"<a href='$edit_page_link'>",
			"</a>"
		);

		$dummy_data_link = admin_url("admin.php?page=gd-settings&tab=general&section=dummy_data");
		$hints[] = wp_sprintf(
			__("You can add or remove dummy data %shere%s.","geodirectory"),
			"<a href='$dummy_data_link'>",
			"</a>"
		);

		return self::format_hints(
			$hints,
			"https://wpgeodirectory.com/docs-v2/templates/archive/", // documentation url
			"https://www.youtube.com/watch?v=o8zgcNwNKyY", // video documentation url
			"archive_template" // feedback id
		);
	}

	/**
	 * Hints for search templates.
	 *
	 * @return string
	 */
	public function search_template(){
		$hints = array();

		$edit_page_link = get_edit_post_link();
		$hints[] = wp_sprintf(
			__("This is the search page, its design can be changed via the page template %shere%s.","geodirectory"),
			"<a href='$edit_page_link'>",
			"</a>"
		);


		$hints[] = wp_sprintf(
			__("You can use shortcodes or blocks to build the page or some page builders such as %sBeaver Themer or Elementor Pro.%s","geodirectory"),
			"<a href='https://wpgeodirectory.com/docs-v2/integrations/builders/#theme-builders'>",
			"</a>"
		);

		$archive_item_page_id = geodir_archive_item_page_id();
		$edit_page_link = get_edit_post_link($archive_item_page_id);
		$hints[] = wp_sprintf(
			__("Individual post loop content can be changed in the archive item page template %shere%s.","geodirectory"),
			"<a href='$edit_page_link'>",
			"</a>"
		);

		return self::format_hints(
			$hints,
			"https://wpgeodirectory.com/docs-v2/places/search/", // documentation url
			"https://www.youtube.com/watch?v=o8zgcNwNKyY", // video documentation url
			"search_template" // feedback id
		);
	}

	/**
	 * Hints for archive templates.
	 *
	 * @return string
	 */
	public function single_template(){
		$hints = array();

		$edit_page_link = get_edit_post_link(geodir_details_page_id());
		$hints[] = wp_sprintf(
			__("This is a details page, its design can be changed via the page template %shere%s.","geodirectory"),
			"<a href='$edit_page_link'>",
			"</a>"
		);


		$hints[] = wp_sprintf(
			__("You can use shortcodes or blocks to build the page or some page builders such as %sBeaver Themer or Elementor Pro.%s","geodirectory"),
			"<a href='https://wpgeodirectory.com/docs-v2/integrations/builders/#theme-builders'>",
			"</a>"
		);

		$post_type = geodir_get_current_posttype();
		$tabs_link = admin_url( 'edit.php?post_type='.$post_type.'&page='.$post_type.'-settings&tab=cpt-tabs');
		$hints[] = wp_sprintf(
			__("The tabs output can be changed via drag and drop %shere%s. %sDocumentation%s","geodirectory"),
			"<a href='$tabs_link'>",
			"</a>",
			"<a href='https://wpgeodirectory.com/docs-v2/places/tabs/'>",
			"</a>"
		);

		return self::format_hints(
			$hints,
			"https://wpgeodirectory.com/docs-v2/templates/details/", // documentation url
			"https://www.youtube.com/watch?v=o8zgcNwNKyY", // video documentation url
			"single_template" // feedback id
		);
	}

	/**
	 * Hints for archive templates.
	 *
	 * @return string
	 */
	public function location_template(){
		$hints = array();

		$edit_page_link = get_edit_post_link();
		$hints[] = wp_sprintf(
			__("This is the location page, its design can be changed via the page template %shere%s.","geodirectory"),
			"<a href='$edit_page_link'>",
			"</a>"
		);


		$hints[] = wp_sprintf(
			__("You can use shortcodes or blocks to build the page or %smost page builders%s","geodirectory"),
			"<a href='https://wpgeodirectory.com/docs-v2/integrations/builders/'>",
			"</a>"
		);

		$hints[] = wp_sprintf(
			__("If you have the %slocation manager addon%s installed this page will be the root for all locations and the items on the page such as map and listings will be filtered for that location.","geodirectory"),
			"<a href='https://wpgeodirectory.com/downloads/location-manager/' target='_blank'>",
			"</a>"
		);




		return self::format_hints(
			$hints,
			"https://wpgeodirectory.com/docs-v2/templates/location/", // documentation url
			"https://www.youtube.com/watch?v=o8zgcNwNKyY", // video documentation url
			"location_template" // feedback id
		);
	}
}