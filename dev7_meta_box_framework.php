<?php
/**
 * Dev7studios Meta Box Framework
 * 
 * @author Gilbert Pellegrom
 * @link https://github.com/Dev7studios/Dev7studios-Meta-Box-Framework
 * @version 1.0.3
 * @license MIT
 */

if( !class_exists( 'Dev7_Meta_Box_Framework' ) ) {

	class Dev7_Meta_Box_Framework {
	
	    function __construct() 
	    {	
	        add_action( 'admin_init', array(&$this, 'admin_init') );
	        add_action( 'add_meta_boxes', array(&$this, 'add_meta_boxes') );
	        add_action( 'pre_post_update', array(&$this, 'meta_box_save') );
	    }
	    
	    function admin_init() {
		    do_action( 'dev7_meta_boxes' );
	    }
	    
	    function add_meta_boxes() {
			global $dev7_meta_boxes;
			
			
			if( !is_array($dev7_meta_boxes) )
				return;

			foreach( $dev7_meta_boxes as $meta_box ){
				if( is_array($meta_box['pages']) ){
					foreach( $meta_box['pages'] as $page ){
						add_meta_box( $meta_box['id'].'_mb', $meta_box['title'], array(&$this, 'meta_box_output'), $page, $meta_box['context'], $meta_box['priority'], array('dev7_meta_box' => $meta_box) );
					}
				} else {
					add_meta_box( $meta_box['id'], $meta_box['title'], array(&$this, 'meta_box_output'), $meta_box['pages'], $meta_box['context'], $meta_box['priority'], array('dev7_meta_box' => $meta_box) );	
				}
		    }
		}
		
		function meta_box_save( $post_id ) {
		
			if ( !current_user_can( 'edit_page', $post_id ) || !current_user_can( 'edit_post', $post_id ) ) return;
			
			if ( !isset( $_POST['dev7_meta_box_nonce'] ) || !wp_verify_nonce( $_POST['dev7_meta_box_nonce'], plugin_basename( __FILE__ ) ) )
				return;
			
			global $dev7_meta_boxes;
			
			if( !is_array($dev7_meta_boxes) ) return;
				
			foreach( $dev7_meta_boxes as $meta_box ){
				if( isset($meta_box['fields']) && is_array($meta_box['fields']) ){
					foreach( $meta_box['fields'] as $field ){
						if( isset($field['id']) ){
							if( $field['type'] == 'checkboxes' && isset($field['choices']) ){
						    	foreach( $field['choices'] as $ckey=>$cval ){
						    		if( isset($_POST[$field['id'] .'_'. $ckey]) ){
						    			update_post_meta( $post_id, $field['id'] .'_'. $ckey, $_POST[$field['id'] .'_'. $ckey] );
						    		}
						    	}
						    } else {
								if( isset($_POST[$field['id']]) ){
									update_post_meta( $post_id, $field['id'], $_POST[$field['id']] );
								}
							}
						}
					}
				}
			}
		}
		
		function meta_box_output( $post, $args ) {
			global $dev7_meta_boxes;
			if( !is_array($dev7_meta_boxes) )
				return;
				
			wp_nonce_field( plugin_basename( __FILE__ ), 'dev7_meta_box_nonce' );
			
			foreach( $dev7_meta_boxes as $meta_box ){
				if( isset($args['args']['dev7_meta_box']['id']) && $args['args']['dev7_meta_box']['id'] == $meta_box['id'] ){
					if( isset($meta_box['fields']) && is_array($meta_box['fields']) ){
					
						?>
					
						<style>
							#poststuff .postbox[id*="_mb"] .inside {
								margin: 0;
								padding: 0;
							}
							table.meta {
								border-collapse: collapse;
								width: 100%;
								margin: 0;
							}
							table.meta th, table.meta td {
								padding: 1em;
								text-align: left;
								vertical-align: top;
							}
							table.meta tr + tr th, table.meta tr + tr td {
								border-top: 1px solid #ddd;
							}
							table.meta th {
								width: 25%;
								background: #eee;
							}
							table.meta input[type="text"], table.meta textarea {
								width: 100%;
							}
							table.meta textarea {
								height: 150px;
								resize: vertical;
							}
						</style>
					
						<?php

						echo '<table class="meta">';

						foreach( $meta_box['fields'] as $field ){
							if( isset($field['id']) && isset($field['type']) ){
								$value = get_post_meta( $post->ID, $field['id'], true );
								if( $value === false && isset($field['std']) ) $value = $field['std'];
								
								if( $field['type'] == 'checkboxes' && isset($field['choices']) ){
									$value = array();
					    		    foreach( $field['choices'] as $ckey=>$cval ){
					    		    	$value[$field['id'] .'_'. $ckey] = get_post_meta( $post->ID, $field['id'] .'_'. $ckey, true );
					    		    }
					    		}
								
								echo '<tr>';
								
								if( isset($field['label']) && $field['label'] ){
									echo '<th><label for="'. $field['id'] .'">'. $field['label'] .'</label></th> ';
									echo '<td>';
								} else {
									echo '<td colspan="2">';
								}
								
			
								switch( $field['type'] ){
					    		    case 'text':
					    		        $value = esc_attr(stripslashes($value));
					    		        echo '<input type="text" name="'. $field['id'] .'" id="'. $field['id'] .'" value="'. $value .'" />';
					    		        break;
					    		    case 'textarea':
					    		        $value = esc_html(stripslashes($value));
					    		        echo '<textarea name="'. $field['id'] .'" id="'. $field['id'] .'">'. $value .'</textarea>';
					    		        break;
					    		    case 'select':
					    		        $value = esc_html(esc_attr($value));
					    		        if( isset($field['choices']) ){
					    		        	echo '<select name="'. $field['id'] .'" id="'. $field['id'] .'">';
						    		        foreach( $field['choices'] as $ckey=>$cval ){
						        		        echo '<option value="'. $ckey .'"'. (($ckey == $value) ? ' selected="selected"' : '') .'>'. $cval .'</option>';
						    		        }
											echo '</select>';
					    		        }
					    		        break;
					    		    case 'radio':
					    		        $value = esc_html(esc_attr($value));
					    		        if( isset($field['choices']) ){
						    		        foreach( $field['choices'] as $ckey=>$cval ){
						        		        echo '<label><input type="radio" name="'. $field['id'] .'" id="'. $field['id'] .'_'. $ckey .'" value="'. $ckey .'"'. (($ckey == $value) ? ' checked="checked"' : '') .' /> '. $cval .'</label><br />';
						    		        }
					    		        }
					    		        break;
					    		    case 'checkbox':
					    		        $value = esc_attr(stripslashes($value));
					    		        echo '<input type="hidden" name="'. $field['id'] .'" value="0" />';
					    		        echo '<label><input type="checkbox" name="'. $field['id'] .'" id="'. $field['id'] .'" value="1"'. (($value) ? ' checked="checked"' : '') .' /> '.$field['desc'].'</label>';
					    		        break;
					    		    case 'checkboxes':
					    		    	if( isset($field['choices']) ){
						    		        foreach( $field['choices'] as $ckey=>$cval ){
						    		        	$val = '';
						    		            if(isset($value[$field['id'] .'_'. $ckey])) $val = $value[$field['id'] .'_'. $ckey];
						    		            elseif(is_array($field['std']) && in_array($ckey, $field['std'])) $val = $ckey;
						    		            $val = esc_html(esc_attr($val));
						        		        echo '<input type="hidden" name="'. $field['id'] .'_'. $ckey .'" value="0" />';
						        		        echo '<label><input type="checkbox" name="'. $field['id'] .'_'. $ckey .'" id="'. $field['id'] .'_'. $ckey .'" value="'. $ckey .'"'. (($ckey == $val) ? ' checked="checked"' : '') .' /> '. $cval .'</label><br />';
						    		        }
					    		        }
					    		        break;
									default:
					        		    break;
								}
								
								if( isset($field['desc']) && $field['desc'] && 'checkbox' != $field['type'] ){
									echo '<p class="description">'. $field['desc'] .'</p>';
								}
								
								echo '</td></tr>';
								
							}
						} // end foreach field
						
						echo '</table>';
						
					}
				}
			}
		}
	    
	}
	
	new Dev7_Meta_Box_Framework();
	
}

if( !function_exists( 'dev7_add_meta_box' ) ) {

	function dev7_add_meta_box( $meta_box ) {
		global $dev7_meta_boxes;
		
		if( !is_array($dev7_meta_boxes) ) $dev7_meta_boxes = array();
				
		$dev7_meta_boxes[] = $meta_box;
	}

}

// simple way to return the value
function get_metabox( $key = "" ) {

	return get_post_meta( get_the_ID(), $key, true );

}

