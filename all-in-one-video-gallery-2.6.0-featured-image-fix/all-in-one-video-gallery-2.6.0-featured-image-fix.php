<?php namespace BillGatesDepopulation\Com\AllInOneVideoGalleryFixes;
/**
 * Plugin Name: All-in-One Video Gallery Fixes
 * Description: Adding featured image support to All-in-One Video Gallery plugin 2.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

add_action( 'save_post', 'BillGatesDepopulation\Com\AllInOneVideoGalleryFixes\save_meta_data', 11, 2 );

function save_meta_data( $post_id, $post ) {
  if(AIOVG_PLUGIN_VERSION != '2.6.0') {
    return $post_id;
  }

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

      // $image_id = get_post_meta( $post_id, '_thumbnail_id', true );
      // $thumbnail_url = '';
      // if ($image_id) {
      //   $thumbnail_url = get_post_meta( $image_id, '_wp_attached_file', true );
      // }
      $image_url    = get_post_meta( $post_id, 'image', true );
      // if ( ! $image_id || $image_url != $thumbnail_url ) {
        Generate_Featured_Image( $image_url, $post_id );
      // }
    }
  }

  return $post_id;
}

function Generate_Featured_Image( $image_url, $post_id  ){
  $upload_dir = wp_upload_dir();
  $image_data = file_get_contents($image_url);
  $filename = basename($image_url);
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
