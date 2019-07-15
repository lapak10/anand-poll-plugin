<?php defined( 'ABSPATH' ) or die( 'No script kiddies please!' );
/**
* Plugin Name: Anand WP Poll
* Plugin URI: https://www.example.com
* Description: This is a sample poll plugin for Command Media. Submitted by anand.kmk@gmail.com
* Version: 1.0
* Author: Anand - anand.kmk@gmail.com
* Author URI: https://example.com
**/
function nd_register_cst_poll() {

	/**
	 * Create cst_poll post type.
	 */

	$labels = array(
		"name" => __( "Polls", "twentynineteen" ),
		"singular_name" => __( "poll", "twentynineteen" ),
	);

	$args = array(
		"label" => __( "Polls", "twentynineteen" ),
		"labels" => $labels,
		"description" => "",
		"public" => true,
		"publicly_queryable" => true,
		"show_ui" => true,
		"delete_with_user" => false,
		"show_in_rest" => true,
		"rest_base" => "",
		"rest_controller_class" => "WP_REST_Posts_Controller",
		"has_archive" => false,
		"show_in_menu" => true,
		"show_in_nav_menus" => true,
		"exclude_from_search" => false,
		"capability_type" => "post",
		"map_meta_cap" => true,
		"hierarchical" => false,
		"rewrite" => array( "slug" => "cst_poll", "with_front" => true ),
		"query_var" => true,
		"supports" => array( "title"),
	);

	register_post_type( "cst_poll", $args );
}

add_action( 'init', 'nd_register_cst_poll' );

/**
 * generate metabox and fields
 */


function nd_poll_fields() {
	global $post;
	// Nonce field to validate form request came from current site
	wp_nonce_field( basename( __FILE__ ), 'poll_fields' );
	// Get the location data if it's already been entered
	$active_date = get_post_meta( $post->ID, 'poll_active_date', true );
	$option_one = get_post_meta( $post->ID, 'poll_option_one', true );
	$option_two = get_post_meta( $post->ID, 'poll_option_two', true );
	// Output the field
	echo '<style> </style>';
	echo '<label>Active Date </label><input type="date" name="poll_active_date" value="' . esc_textarea( $active_date )  . '"><br>';
	echo '<label>Option One </label><input type="text" name="poll_option_one" value="' . esc_textarea( $option_one )  . '" ><br>';
	echo '<label>Option Two </label><input type="text" name="poll_option_two" value="' . esc_textarea( $option_two )  . '" ><br>';
	
}

function nd_add_metabox() {
	add_meta_box(
		'nd_poll_fields',
		'Poll Options',
		'nd_poll_fields',
		'cst_poll',
		'normal',
		'high'
	);
}


add_action( 'add_meta_boxes', 'nd_add_metabox' );


/**
 * Save the metabox data
 */
function nd_save_poll_options( $post_id, $post ) {
	// Return if the user doesn't have edit permissions.
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return $post_id;
	}
	// Verify this came from the our screen and with proper authorization,
	// because save_post can be triggered at other times.
	if ( ! isset( $_POST['poll_active_date'] )  || ! isset( $_POST['poll_option_one'] )  || ! isset( $_POST['poll_option_two'] )  || ! wp_verify_nonce( $_POST['poll_fields'], basename(__FILE__) ) ) {
		return $post_id;
	}
	// Now that we're authenticated, time to save the data.
	// This sanitizes the data from the field and saves it into an array $events_meta.
	$poll_meta['poll_active_date'] = esc_textarea( $_POST['poll_active_date'] );
	$poll_meta['poll_option_one'] = esc_textarea( $_POST['poll_option_one'] );
	$poll_meta['poll_option_two'] = esc_textarea( $_POST['poll_option_two'] );
	
	// Cycle through the $events_meta array.
	// Note, in this example we just have one item, but this is helpful if you have multiple.
	foreach ( $poll_meta as $key => $value ) :
		// Don't store custom data twice
		if ( 'revision' === $post->post_type ) {
			return;
		}
		if ( get_post_meta( $post_id, $key, false ) ) {
			// If the custom field already has a value, update it.
			update_post_meta( $post_id, $key, $value );
		} else {
			// If the custom field doesn't have a value, add it.
			add_post_meta( $post_id, $key, $value);
		}
		if ( ! $value ) {
			// Delete the meta key if there's no value
			delete_post_meta( $post_id, $key );
		}
	endforeach;
}
add_action( 'save_post', 'nd_save_poll_options', 1, 2 );




// register widget

class Poll_Widget extends WP_Widget {
	// class constructor
	public function __construct() {
		$widget_ops = array( 
		'classname' => 'nd_poll_widget',
		'description' => 'Poll widget by Anand',
	);
	parent::__construct( 'nd_poll_widget', 'Poll Widget by ND', $widget_ops );
	}
	
	// output the widget content on the front-end
	public function widget( $args, $instance ) {
	echo $args['before_widget'];
	if ( ! empty( $instance['title'] ) ) {
		echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ) . $args['after_title'];
	}

	if( ! empty( $instance['selected_posts'] ) && is_array( $instance['selected_posts'] ) ){ 

		$selected_posts = get_posts( array( 'post__in' => $instance['selected_posts'] ) );
		?>
		<ul>
		<?php foreach ( $selected_posts as $post ) { ?>
			<li><a href="<?php echo get_permalink( $post->ID ); ?>">
			<?php echo $post->post_title; ?>
			</a></li>		
		<?php } ?>
		</ul>
		<?php 
		
	}else{
		echo esc_html__( 'No polls for today!', 'text_domain' );	
	}

	echo $args['after_widget'];
}

	// output the option form field in admin Widgets screen
	public function form( $instance ) {
	$title = ! empty( $instance['title'] ) ? $instance['title'] : esc_html__( 'Title', 'text_domain' );
	?>
	<p>
	<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>">
	<?php esc_attr_e( 'Title:', 'text_domain' ); ?>
	</label> 
	
	<input 
		class="widefat" 
		id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" 
		name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" 
		type="text" 
		value="<?php echo esc_attr( $title ); ?>">
	</p>
	<?php
}

	// save options
	public function update( $new_instance, $old_instance ) {
	$instance = array();
	$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';

	return $instance;
}
	
}

add_action( 'widgets_init', function(){
	register_widget( 'Poll_Widget' );
});