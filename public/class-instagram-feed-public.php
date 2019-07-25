<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://github.com/Clark-Nikdel-Powell/instagram-feed
 * @since      1.0.0
 *
 * @package    Instagram_Feed
 * @subpackage Instagram_Feed/public
 */

use EchoDelta\Walker_Social_Nav_Menu;

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Instagram_Feed
 * @subpackage Instagram_Feed/public
 * @author     Your Name <email@example.com>
 */
class Instagram_Feed_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $plugin_name The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $version The current version of this plugin.
	 */
	private $version;

	private $access_token_fieldname;
	private $hashtag_fieldname;
	private $images_check_cache_key;
	private $images_cache_key;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param string $plugin_name The name of the plugin.
	 * @param string $version The version of this plugin.
	 * @param string $access_token_field_name Fieldname for the access token.
	 * @param string $images_check_cache_key The cache key for whether it's time to check for new images.
	 * @param string $images_cache_key The cache key of image data from Instagram.
	 *
	 * @since    1.0.0
	 */
	public function __construct( $plugin_name, $version, $access_token_field_name, $hashtag_field_name, $images_check_cache_key, $images_cache_key ) {

		$this->plugin_name            = $plugin_name;
		$this->version                = $version;
		$this->access_token_fieldname = $access_token_field_name;
		$this->hashtag_fieldname      = $hashtag_field_name;
		$this->images_check_cache_key = $images_check_cache_key;
		$this->images_cache_key       = $images_cache_key;
	}

	/**
	 * make_api_call
	 *
	 * Make an API call to Instagram. Test the response before returning and caching new images.
	 * If something goes wrong, email the site admin so that someone is aware of the issue.
	 *
	 * @param $count
	 *
	 * @return array|bool|mixed
	 */
	private function make_api_call( $tag = '', $later_than_id = '' ) {
		// If it's been a while since we grabbed new photos from the API, let's check for new ones.
		$api_url = 'https://api.instagram.com/v1/users/self/media/recent/?access_token=' . get_option( $this->access_token_fieldname ) . '&count=20'; // Always grab the last 20. Depending on the hashtag, we may not be able to use all 20.

		if ( ! empty( $later_than_id ) ) {
			$api_url .= '&max_id=' . $later_than_id;
		}

		$connection_c = curl_init(); // initializing
		curl_setopt( $connection_c, CURLOPT_URL, $api_url ); // API URL to connect
		curl_setopt( $connection_c, CURLOPT_RETURNTRANSFER, 1 ); // return the result, do not print
		curl_setopt( $connection_c, CURLOPT_TIMEOUT, 20 );
		$json_return = curl_exec( $connection_c ); // connect and get json data
		curl_close( $connection_c ); // close connection

		$images_raw = json_decode( $json_return ); // decode

		// Run a code check and make sure we have images before setting the cache.
		if ( ! isset( $images_raw->meta->code ) || ! isset( $images_raw->data ) ) {
			wp_mail( get_bloginfo( 'admin_email' ), 'Problem with Instagram Feed API call', 'The meta and/or data was not set.' );

			return false;
		}

		if ( 200 !== intval( $images_raw->meta->code ) ) {
			wp_mail( get_bloginfo( 'admin_email' ), 'Problem with Instagram Feed API call', 'We were expecting a 200 response code, but got ' . $images_raw->meta->code . ' instead.' );

			return false;
		}

		if ( 20 !== count( $images_raw->data ) ) {
			wp_mail( get_bloginfo( 'admin_email' ), 'Problem with Instagram Feed API call', 'We were expecting 20 images, but got ' . count( $images_raw->data ) . ' instead.' );
		}

		$images_unfiltered = $images_raw->data;

		// Filter the images by tag if it's been passed in.
		if ( '' !== $tag ) {
			foreach ( $images_unfiltered as $image_key => $image ) {
				if ( ! in_array( $tag, $image->tags, true ) ) {
					unset( $images_unfiltered[ $image_key ] );
				}
			}
			$images = array_values( $images_unfiltered );
		} else {
			$images = $images_unfiltered;
		}

		$saved_images = get_transient( $this->images_cache_key );
		if ( false !== $saved_images ) {

			$saved_images_ids = [];
			foreach ( $saved_images as $image ) {
				$saved_images_ids[] = $image->id;
			}

			$images_reversed = array_reverse( $images ); // First, reverse the array so that we're looping oldest to newest. This way, we can prepend the images to the current images array and it'll still be oldest to newest.
			foreach ( $images_reversed as $image ) {
				if ( in_array( $image->id, $saved_images_ids, true ) ) {
					continue; // This keeps us from adding the same image twice.
				}
				array_unshift( $saved_images, $image );
			}
		} else {
			$saved_images = $images;
		}

		// Sort the images before returning.
		usort( $saved_images, array( $this, 'reorder_images_by_created_time' ) );

		// Now check the size of the array.
		$max_images = apply_filters( 'instagram_feed_max_cached_images', 100 );
		if ( count( $saved_images ) >= $max_images ) {
			$saved_images = array_slice( $saved_images, 0, $max_images );
		}

		// Set the images cache and return the fresh images from the API.
		// This cache never expires. We only update it after checking to make sure that the API call was successful.
		set_transient( $this->images_cache_key, $saved_images, 0 );

		return $saved_images;
	}

	/**
	 * reorder_images_by_created_time
	 *
	 * Instagram can't be trusted to sort images by post date. By default, it sends back images based on which image was last edited.
	 *
	 */
	public function reorder_images_by_created_time( $imageA, $imageB ) {
		return strcmp( $imageB->created_time, $imageA->created_time );
	}

	/**
	 * get_images
	 *
	 * Get the images from cache or the Instagram API.
	 *
	 * @param int $count
	 *
	 * @return array|bool|mixed
	 */
	public function get_images() {
		$tag = apply_filters( 'instagram_feed_images_tag', get_option( $this->hashtag_fieldname ) );

		$images                = [];
		$can_we_use_old_images = get_transient( $this->images_check_cache_key );
		$images_transient      = get_transient( $this->images_cache_key );

		if ( false === $can_we_use_old_images || false === $images_transient ) {
			$api_response = $this->make_api_call( $tag );

			if ( false !== $api_response ) {
				$images = $api_response;
			}

			$count = apply_filters( 'instagram_feed_images_count', 20 );
			if ( count( $images ) < $count ) { // If we don't have enough images, we can try to dig farther back to find more.
				$api_calls_count = 0;
				$max_api_calls   = apply_filters( 'instagram_feed_max_api_calls', 10 );
				while ( count( $images ) < $count ) { // We do this as a while loop instead of a for loop so that we can break early if we get enough images.

					if ( $api_calls_count === $max_api_calls ) {
						break;
					}

					$last_image             = end( $images );
					$later_than_id          = $last_image->id;
					$recursive_api_response = $this->make_api_call( $tag, $later_than_id );

					if ( false !== $recursive_api_response ) {
						$images = $recursive_api_response;
					}
					$api_calls_count ++;
				}
			}

			// Reset the cache check
			set_transient( $this->images_check_cache_key, true, HOUR_IN_SECONDS * 3 );
		} else {

			// If it hasn't been 3 hours, then we'll use the images from cache.
			$images = $images_transient;
		}

		return $images;
	}

	public function add_shortcode() {
		add_shortcode( 'instagram_feed', array( $this, 'shortcode_output' ) );
	}

	public function shortcode_output() {

		$images = $this->get_images();

		// If we don't have images, hide this entire block.
		if ( empty( $images ) ) {
			return false;
		}

		ob_start();
		do_action( 'instagram_feed_before' );
		?>
        <div class="ig-feed">
		<?php do_action( 'instagram_feed_prepend' ); ?>
		<?php
		$attr         = new \stdClass();
		$attr->tag    = 'a';
		$attr->rel    = 'nofollow';
		$attr->target = '_blank';
		$attr->class  = 'ig-feed__cell';
		$attr         = apply_filters( 'instagram_feed_image_wrapper_attributes', $attr );
		if ( 'a' !== $attr->tag ) {
			unset( $attr->rel );
			unset( $attr->target );
		}
		$count = apply_filters( 'instagram_feed_images_count', 20 );
		?>
		<?php for ( $i = 0; $i < $count; $i ++ ) { ?>
			<?php
			if ( ! isset( $images[ $i ] ) ) { // Break if we've run out of images.
				break;
			}

			$image = $images[ $i ];

			// Set up wrapper attributes
			if ( 'a' === $attr->tag ) {
				$attr->href = esc_url( $image->link );
			}
			$attributes = [];
			foreach ( $attr as $key => $value ) {
				if ( empty( $value ) || 'tag' === $key ) {
					continue;
				}
				$attributes[] = esc_html( $key ) . '="' . esc_attr( $value ) . '"';
			}

			// Set up image attributes
			$thumbnail       = $image->images->thumbnail;
			$thumbnail_src   = esc_url( $thumbnail->url );
			$thumbnail_width = $thumbnail->width;

			$low_resolution       = $image->images->low_resolution;
			$low_resolution_src   = esc_url( $low_resolution->url );
			$low_resolution_width = $low_resolution->width;

			$standard_resolution       = $image->images->standard_resolution;
			$standard_resolution_src   = esc_url( $standard_resolution->url );
			$standard_resolution_width = $standard_resolution->width;

			$img_attr         = new \stdClass();
			$img_attr->class  = 'ig-feed__image';
			$img_attr->src    = esc_attr( $thumbnail_src );
			$img_attr->srcset = esc_attr( $thumbnail_src . ' ' . $thumbnail_width . 'w, ' . $low_resolution_src . ' ' . $low_resolution_width . 'w, ' . $standard_resolution_src . ' ' . $standard_resolution_width . 'w' );
			$img_attr->alt    = esc_attr( htmlentities( $image->caption->text ) );

			$img_attr = apply_filters( 'instagram_feed_image_attributes', $img_attr, $image );

			$image_attributes = [];
			foreach ( $img_attr as $key => $value ) {
				if ( empty( $value ) ) {
					continue;
				}
				$image_attributes[] = esc_html( $key ) . '="' . esc_attr( $value ) . '"';
			}

			if ( empty( $image_attributes ) ) { // Just a small sanity check here.
				continue;
			}
			?>
            <<?php echo $attr->tag . ' ' . ( ! empty( $attributes ) ? implode( ' ', $attributes ) : '' ); ?>>
            <img <?php echo( ! empty( $image_attributes ) ? implode( ' ', $image_attributes ) : '' ); ?>/>
            </<?php echo $attr->tag; ?>>
		<?php } ?>
		<?php do_action( 'instagram_feed_append' ); ?>
        </div>
		<?php
		do_action( 'instagram_feed_after' );
		$output = ob_get_clean();

		return $output;
	}
}
