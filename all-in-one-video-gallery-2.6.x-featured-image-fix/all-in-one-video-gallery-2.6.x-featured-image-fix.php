<?php namespace BillGatesDepopulation\Com\AllInOneVideoGalleryFixes;
/**
 * Plugin Name: All-in-One Video Gallery Fixes
 * Description: Adding featured image support to All-in-One Video Gallery plugin 2.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

add_action('init', 'BillGatesDepopulation\Com\AllInOneVideoGalleryFixes\attach_featured', 11, 2);
add_action( 'save_post', 'BillGatesDepopulation\Com\AllInOneVideoGalleryFixes\save_meta_data', 11, 2 );

function attach_featured() {
    add_post_type_support( 'aiovg_videos', 'thumbnail' );
}
function save_meta_data( $post_id, $post ) {
  if ( ! isset( $_POST['post_type'] ) ) {
    return $post_id;
  }

  // Check this is the "aiovg_videos" custom post type
  if ( 'aiovg_videos' != $post->post_type ) {
    return $post_id;
  }

  // If this is an autosave, our form has not been submitted, so we don't want to do anything
  if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
    return $post_id;
  }

  // Check the logged in user has permission to edit this post
  if ( ! aiovg_current_user_can( 'edit_aiovg_video', $post_id ) ) {
    return $post_id;
  }

  // Check if "aiovg_video_sources_nonce" nonce is set
  if ( isset( $_POST['aiovg_video_sources_nonce'] ) ) {
    // Verify that the nonce is valid
    if ( wp_verify_nonce( $_POST['aiovg_video_sources_nonce'], 'aiovg_save_video_sources' ) ) {
      // OK to save meta data

      $image_id = get_post_meta( $post_id, '_thumbnail_id', true );
      $image_url    = get_post_meta( $post_id, 'image', true );
      if ( false == $image_id ) {
        set_featured_image( $image_url, $post_id );
      }
    }
  }

  return $post_id;
}

function set_featured_image( $image_url, $post_id  ){
  $upload_dir = wp_upload_dir();
  $image_data = file_get_contents(esc_url_raw($image_url));
  $filename = basename($image_url);

  if ( empty($image_data) ) {
    return;
  }

  if(wp_mkdir_p($upload_dir['path']))
    $file = $upload_dir['path'] . '/' . $filename;
  else
    $file = $upload_dir['basedir'] . '/' . $filename;
  file_put_contents($file, $image_data);

  $wp_filetype = wp_check_filetype($filename, null );
  $attachment = array(
      'post_mime_type' => $wp_filetype['type'],
      'post_title' => sanitize_file_name($filename),
      'post_content' => '',
      'post_status' => 'inherit'
  );
  $attach_id = wp_insert_attachment( $attachment, $file, $post_id );
  require_once(ABSPATH . 'wp-admin/includes/image.php');
  $attach_data = wp_generate_attachment_metadata( $attach_id, $file );
  $res1= wp_update_attachment_metadata( $attach_id, $attach_data );
  $res2= set_post_thumbnail( $post_id, $attach_id );
}
