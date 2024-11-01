<?php

add_action( 'widgets_init', 'VirtualPostsWidgetListing::register' );

class VirtualPostsWidgetListing extends WP_Widget {

	static function register(){

		register_widget( 'VirtualPostsWidgetListing' );

	}

	function __construct() {

		$widget_ops  = array( 'classname' => 'VirtualPostsWidgetListing', 'description' => 'List posts from cache' );
		$control_ops = array( 'id_base' => 'virtualposts-widget-listing' );
		$this->WP_Widget( 'virtualposts-widget-listing', 'Virtual Posts Listing', $widget_ops, $control_ops );

	}

	static function cmp( $a, $b )
	{
		$a = strtotime( $a['date'] );
		$b = strtotime( $b['date'] );

		if ( $a[''] == $b[''] ) {
			return 0;
		}
		return ($a < $b) ? -1 : 1;
	}

	public function widget( $args, $instance ) {
		$title = apply_filters( 'widget_title', $instance['title'] );
		$filters = apply_filters( 'widget_filters', $instance['filters'] );

		echo wp_kses_post( $args['before_widget'] );
		if ( ! empty( $title ) )
			echo wp_kses_post( $args['before_title'] . $title . $args['after_title'] );


		$allowed_feeds = array();
		$feeds = VirtualPostsSettings::get( 'feeds' );
		foreach ( $feeds as $feed ) {
			if ( is_array( $filters ) && in_array( $feed['id'], $filters ) ) {
				$allowed_feeds[] = $feed['id'];
			}
		}

		$posts = phpFastCache::get( VirtualPostsFeeds::posts_cache_key );

		if ( ! $posts ) return;

		usort( $posts, 'VirtualPostsWidgetListing::cmp' );

		foreach ( $posts as $post ) {
			error_log( print_r( $post, true ) );
			if ( in_array( $post[ 'feed_id' ], $allowed_feeds ) ) {
				?>
				<div class="virtualposts_post">
					<strong>
						<a title="<?php echo wp_kses_post( substr( strip_tags( $post['excerpt'] ) . '...', 0, 100 ) . '...' ); ?>" href="/virtualposts/<?php echo esc_attr( $post['link'] ); ?>">
							<?php echo esc_attr( $post['title'] ); ?>
						</a>
					</strong>
				</div>
				<?php
			}
		}

		echo wp_kses_post( '<hr/><br/>' );

		echo wp_kses_post( $args['after_widget'] );
	}

	public function form( $instance ) {

		if ( isset( $instance[ 'title' ] ) ) {
			$title = $instance[ 'title' ];
		}
		else {
			$title = __( 'New title', 'vpp_' );
		}

		$filters = array();
		if ( isset( $instance[ 'filters' ] ) ) {
			$filters = $instance[ 'filters' ];
		}

		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php _e( 'Title:' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>
		<?php

		$feeds = VirtualPostsSettings::get( 'feeds' );
		foreach ( $feeds as $feed ) {
			?>
			<input <?php if ( is_array( $filters ) && in_array( $feed['id'], $filters ) ) echo esc_attr( 'checked="checked"' ); ?> type="checkbox" name="<?php echo esc_attr( $this->get_field_name( 'filters' ) ); ?>[]" value="<?php echo esc_attr( $feed['id'] ); ?>"> <?php echo esc_attr( $feed['name'] ); ?><br>
			<?php
		}

	}

	public function update( $new_instance, $old_instance ) {

		//error_log( print_r( $new_instance, true ) );

		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		$instance['filters'] = ( ! empty( $new_instance['filters'] ) ) ? $new_instance['filters'] : array();
		return $instance;
	}

}
