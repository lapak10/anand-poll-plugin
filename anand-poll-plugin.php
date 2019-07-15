<?php defined( 'ABSPATH' ) or die( 'No script kiddies please!' );
/**
* Plugin Name: Anand WP Poll
* Plugin URI: https://www.example.com
* Description: This is a sample poll plugin for Command Media. Submitted by anand.kmk@gmail.com
* Version: 1.0
* Author: Anand - anand.kmk@gmail.com
* Author URI: https://example.com
**/

date_default_timezone_set ( get_option('timezone_string') );

add_filter('enter_title_here', 'my_title_place_holder' , 20 , 2 );
    function my_title_place_holder($title , $post){

        if( $post->post_type == 'cst_poll' ){
            $my_title = "Enter Poll Question here..";
            return $my_title;
        }

        return $title;

}

if( isset( $_POST['poll_answer'] ) AND isset( $_POST['poll_id'] ) ){
    Anand_Poll_Widget :: save_answer(  $_POST['poll_id'] , $_POST['poll_answer'] );
}



function anand_register_cst_poll() {

	/**
	 * Create cst_poll post type.
	 */

	$labels = array(
		"name" => __( "Anand - Polls", "twentynineteen" ),
        "singular_name" => __( "poll", "twentynineteen" ),
        "add_new_item" => __( "Add New Poll", "twentynineteen" ),
        
	);

	$args = array(
		"label" => __( "Anand - Polls", "twentynineteen" ),
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
        'menu_icon'           => 'dashicons-chart-bar',
	);

	register_post_type( "cst_poll", $args );
}

add_action( 'init', 'anand_register_cst_poll' );

/**
 * generate metabox and fields
 */


function anand_poll_fields() {
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
	echo '<label>First Option</label> <input type="text" name="poll_option_one" value="' . esc_textarea( $option_one )  . '" ><br>';
	echo '<label >Second Option</label> <input type="text" name="poll_option_two" value="' . esc_textarea( $option_two )  . '" ><br>';
	
}

function anand_add_metabox() {
	add_meta_box(
		'anand_poll_fields',
		'Poll Options',
		'anand_poll_fields',
		'cst_poll',
		'normal',
		'high'
	);
}


add_action( 'add_meta_boxes', 'anand_add_metabox' );


/**
 * Save the metabox data
 */
function anand_save_poll_options( $post_id, $post ) {
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
add_action( 'save_post', 'anand_save_poll_options', 1, 2 );




// register widget

class Anand_Poll_Widget extends WP_Widget {
	// class constructor
	public function __construct() {
		$widget_ops = array( 
		'classname' => 'anand_poll_widget',
		'description' => 'Poll widget submitted by Anand - anand.kmk@gmail.com',
	);
	parent::__construct( 'anand_poll_widget', 'Poll Widget by ANAND', $widget_ops );
    }
    
    private static function get_todays_poll_question(){

        

       return get_posts( array( 
            //assuming maximum of 1 poll can be added to any day
            'posts_per_page' => 1,
             'post_type' =>'cst_poll',
             'post_status' => array('publish'),
             
             'meta_query' => array(
             
                 array('key'=>'poll_active_date','value' => date('Y-m-d')  )
                 
             
             
             )) );
        
    }

    public function save_answer($poll_id , $poll_answer){

        if( 'option_one' === $poll_answer ) {
            $current_option_one_count = (int) get_post_meta( $poll_id , 'option_one_count' , true );

            update_post_meta(  $poll_id , 'option_one_count', ++$current_option_one_count );

        }
        if( 'option_two' === $poll_answer ) {
            $current_option_two_count = (int) get_post_meta( $poll_id , 'option_two_count' , true );
            //wp_die( json_encode( $_POST ) );
            update_post_meta(  $poll_id , 'option_two_count', ++$current_option_two_count  );

        }
        
        setcookie( "anand_poll_id_" . date("Y-m-d") , $poll_id , time() + time() + (10 * 365 * 24 * 60 * 60) , "/");
        setcookie( "anand_poll_answer_" . date("Y-m-d") , $poll_answer , time() + time() + (10 * 365 * 24 * 60 * 60) , "/");
    }

    private function get_results_array( $poll_id ){

        $option_one_count = (int) get_post_meta( $poll_id , 'option_one_count' , true );
        $option_two_count = (int) get_post_meta( $poll_id , 'option_two_count' , true );
        $total_count = $option_one_count + $option_two_count;
        $option_one_percentage = ( $option_one_count / $total_count ) * 100;
        $option_two_percentage = ( $option_two_count / $total_count ) * 100;

        return array(
            'option_one_count' =>  $option_one_count
            ,'option_two_count' => $option_two_count
            ,'total_vote_count' => $total_count
            ,'option_one_percentage' => round ( $option_one_percentage ,1) 
            ,'option_two_percentage' =>  round ( $option_two_percentage ,1) 

        );
    }

    private function print_result_form ( $poll_id , $poll_answer ){
        $poll_title = get_the_title( $poll_id );
        $option_one = ucwords( strtolower( get_post_meta( $poll_id,'poll_option_one',true ) ));
        $option_two = ucwords ( strtolower( get_post_meta( $poll_id ,'poll_option_two',true ) ) );

        $is_option_one_checked = "option_one" ===  $poll_answer ? 'checked':'';
        $is_option_two_checked = "option_two" ===  $poll_answer ? 'checked':'';

        $poll_result_array = self :: get_results_array( $poll_id );

        $option_one_percentage =  $poll_result_array["option_one_percentage"];
        $option_one_count = $poll_result_array["option_one_count"] ;

        $option_two_percentage =  $poll_result_array["option_two_percentage"];
        $option_two_count = $poll_result_array["option_two_count"] ;

        $total_count = $poll_result_array["total_vote_count"];


        echo <<<HTML
        <fieldset>
	<legend>$poll_title</legend>
	<form method="POST">
		<label>
			<input type="radio" disabled name="poll_answer" value="option_one" $is_option_one_checked />
            $option_one ( $option_one_percentage %, $option_one_count out of $total_count )
		 </label>
		<label>
			<input type="radio" disabled name="poll_answer" value="option_two" $is_option_two_checked />
            $option_two ( $option_two_percentage %, $option_two_count out of $total_count )
		</label>
        
        <br>
        
		<input type="submit" name="submit" style="background: #388e3c;" id="submit" value="Already Voted !" disabled />
		
		
	</form>
</fieldset>
HTML;

    }

	
	// output the widget content on the front-end
	public function widget( $args, $instance ) {

        $cookie_poll_id_string = "anand_poll_id_" . date("Y-m-d");
        $cookie_poll_answer_string = "anand_poll_answer_" . date("Y-m-d");

    echo $args['before_widget'];
    
	if ( ! empty( $instance['title'] ) ) {
		echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ) . $args['after_title'];
	}

	if(  empty( self :: get_todays_poll_question() ) ){ 

		echo "No Poll For Today :)";
		
    }
    

    elseif ( isset( $_POST['poll_id'] )  ) {
       
        self :: print_result_form( $_POST['poll_id'] , $_POST['poll_answer'] );
        //echo json_encode( self :: get_results_array(  $_POST['poll_id']  ) );

    }
    else if (  isset( $_COOKIE[ $cookie_poll_id_string ] ) ) {

        self :: print_result_form( $_COOKIE[ $cookie_poll_id_string ] ,  $_COOKIE[ $cookie_poll_answer_string ]   );

        //echo json_encode( self :: get_results_array(   $_COOKIE[ $today_cookie_string ]  ) );
    }
    
    
    else{
        $poll = self :: get_todays_poll_question();
        $poll_id = $poll[0]->ID;
        $poll_question = get_the_title( $poll_id );
        $option_one = ucwords( strtolower( get_post_meta( $poll_id ,'poll_option_one',true ) ));
        $option_two = ucwords ( strtolower( get_post_meta( $poll_id ,'poll_option_two',true ) ) );
       echo <<<HTML
        <fieldset>
    <legend>$poll_question</legend>
    <form method="POST">
        <label>
            <input type="radio" name="poll_answer" value="option_one" />
            $option_one
         </label>
        <label>
            <input type="radio" name="poll_answer" value="option_two" />
            $option_two
        </label>
        
        <br>
        <input type="hidden" name="poll_id"  value="$poll_id" />
        <input type="submit" name="submit" id="submit" value="Vote" />
		
		
	</form>
</fieldset>
HTML;


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
	register_widget( 'Anand_Poll_Widget' );
});