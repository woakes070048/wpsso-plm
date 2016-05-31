<?php
/*
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl.txt
 * Copyright 2014-2016 Jean-Sebastien Morisset (http://surniaulula.com/)
 */

if ( ! defined( 'ABSPATH' ) ) 
	die( 'These aren\'t the droids you\'re looking for...' );

if ( ! class_exists( 'WpssoPlmSubmenuPlmGeneral' ) && class_exists( 'WpssoAdmin' ) ) {

	class WpssoPlmSubmenuPlmGeneral extends WpssoAdmin {

		public function __construct( &$plugin, $id, $name, $lib, $ext ) {
			$this->p =& $plugin;
			$this->menu_id = $id;
			$this->menu_name = $name;
			$this->menu_lib = $lib;
			$this->menu_ext = $ext;

			if ( $this->p->debug->enabled )
				$this->p->debug->mark();
		}

		protected function add_meta_boxes() {
			add_meta_box( $this->pagehook.'_contact', 
				_x( 'Addresses / Contact Information', 'metabox title', 'wpsso-plm' ), 
					array( &$this, 'show_metabox_contact' ), $this->pagehook, 'normal' );

			add_meta_box( $this->pagehook.'_general',
				_x( 'Place / Location Settings', 'metabox title', 'wpsso-plm' ), 
					array( &$this, 'show_metabox_general' ), $this->pagehook, 'normal' );
		}

		public function show_metabox_contact() {
			$metabox = 'contact';

			echo '<table class="sucom-setting" style="padding-bottom:0;">';
			foreach ( apply_filters( $this->p->cf['lca'].'_'.$metabox.'_general_rows', 
				$this->get_table_rows( $metabox, 'general' ), $this->form ) as $row )
					echo '<tr>'.$row.'</tr>';
			echo '</table>';

			$tabs = apply_filters( $this->p->cf['lca'].'_'.$metabox.'_tabs', array( 
				'address' => 'Addresses',
			) );

			$table_rows = array();
			foreach ( $tabs as $key => $title )
				$table_rows[$key] = apply_filters( $this->p->cf['lca'].'_'.$metabox.'_'.$key.'_rows', 
					$this->get_table_rows( $metabox, $key ), $this->form );
			$this->p->util->do_metabox_tabs( $metabox, $tabs, $table_rows );
		}

		public function show_metabox_general() {
			$metabox = 'plm';
			echo '<table class="sucom-setting">';
			foreach ( apply_filters( $this->p->cf['lca'].'_'.$metabox.'_general_rows', 
				$this->get_table_rows( $metabox, 'general' ), $this->form ) as $row )
					echo '<tr>'.$row.'</tr>';
			echo '</table>';
		}

		protected function get_table_rows( $metabox, $key ) {
			$table_rows = array();
			switch ( $metabox.'-'.$key ) {
				case 'plm-general':

					if ( ! $aop = $this->p->check->aop( 'wpssoplm', true, $this->p->is_avail['aop'] ) )
						$table_rows[] = '<td colspan="2">'.
							$this->p->msgs->get( 'pro-feature-msg', 
								array( 'lca' => 'wpssoplm' ) ).'</td>';

					$checkboxes = '';
					foreach ( $this->p->util->get_post_types() as $post_type )
						$checkboxes .= '<p>'.( $aop ? $this->form->get_checkbox( 'plm_add_to_'.$post_type->name ) :
							$this->form->get_no_checkbox( 'plm_add_to_'.$post_type->name ) ).' '.
							$post_type->label.' '.( empty( $post_type->description ) ? 
								'' : '('.$post_type->description.')' ).'</p>';

					$table_rows['plm_add_to'] = $this->form->get_th_html( _x( 'Show Tab on Post Types',
						'option label', 'wpsso-plm' ), '', 'plm_add_to' ).
					( $aop ? '<td>' : '<td class="blank">' ).$checkboxes.'</td>';

					break;

				case 'contact-general':

					$custom_names = array( 'none' => '' );
					$address_names = WpssoPlmAddress::get_names( $this->p->options );
					$address_names = WpssoUtil::array_merge_recursive_distinct( $custom_names, $address_names );

					$table_rows['plm_addr_for_home'] = $this->form->get_th_html( _x( 'Address for Non-static Homepage',
						'option label', 'wpsso-plm' ), '', 'plm_addr_for_home' ).
					'<td>'.$this->form->get_select( 'plm_addr_for_home', $address_names,
						'full_name', '', true, false, true ).'</td>';
		
					$table_rows['plm_addr_def_country'] = $this->form->get_th_html( _x( 'Address Default Country',
						'option label', 'wpsso-plm' ), '', 'plm_addr_def_country' ).
					'<td>'.$this->form->get_select_country( 'plm_addr_def_country',
					 	'', '', false, $this->p->options['plm_addr_def_country'] ).'</td>';

					break;

				case 'contact-address':

					$half_hours = SucomUtil::get_hours_range( 0, 86400, 60 * 30, '' );	// $format = ''
					$business_types = $this->p->schema->get_schema_types_select( $this->p->cf['head']['schema_type']['place']['local.business'], false );
					$address_names = WpssoPlmAddress::get_names( $this->p->options );
					list( $first_id, $next_id ) = WpssoPlmAddress::get_first_next_ids( $address_names );
					$address_names[$next_id] = WpssoPlmConfig::$cf['form']['plm_address']['new'];

					// check to make sure the selected id exists
					// if not, then unset to use the default
					if ( isset( $this->form->options['plm_addr_id'] ) ) {
						$id = $this->form->options['plm_addr_id'];
						// test if the address name is missing or blank
						if ( ! isset( $this->form->options['plm_addr_name_'.$id] ) ||
							trim( $this->form->options['plm_addr_name_'.$id] ) === '' )
								unset( $this->form->options['plm_addr_id'] );
					}

					// default can also be 'custom' in the social settings metabox
					$this->form->defaults['plm_addr_id'] = 0;

					$table_rows['plm_addr_id'] = $this->form->get_th_html( _x( 'Edit an Address',
						'option label', 'wpsso-plm' ), '', 'plm_addr_id' ).
					'<td colspan="3">'.$this->form->get_select( 'plm_addr_id', $address_names,
						'full_name', '', true, false, true, 'unhide_rows' ).'</td>';
		
					foreach ( $address_names as $id => $name ) {
						$tr_addr_id = '<!-- address id '.$id.' -->'.
							'<tr class="plm_addr_id plm_addr_id_'.$id.'" style="display:none">';
		
						$table_rows['plm_addr_name_'.$id] = "\n".$tr_addr_id.$this->form->get_th_html( _x( 'Address Name',
							'option label', 'wpsso-plm' ), '', 'plm_addr_name' ). 
						'<td colspan="3">'.$this->form->get_input( 'plm_addr_name_'.$id, 'full_name required' ).' '.
							_x( 'leave blank to delete an address', 'option comment', 'wpsso-plm' ).'</td>';
					}

					$table_rows['subsection_schema_place'] = '<th></th><td class="subsection" colspan="3"><h4>'.
						_x( 'Pinterest Rich Pin / Schema Place', 'metabox title', 'wpsso-plm' ).'</h4></td>';
		
					foreach ( $address_names as $id => $name ) {
						$tr_addr_id = '<!-- address id '.$id.' -->'.
							'<tr class="plm_addr_id plm_addr_id_'.$id.'" style="display:none">';
		
						$table_rows['plm_addr_streetaddr_'.$id] = $tr_addr_id.$this->form->get_th_html( _x( 'Street Address',
							'option label', 'wpsso-plm' ), '', 'plm_addr_streetaddr' ). 
						'<td colspan="3">'.$this->form->get_input( 'plm_addr_streetaddr_'.$id, 'wide' ).'</td>';
		
						$table_rows['plm_addr_po_box_number_'.$id] = $tr_addr_id.$this->form->get_th_html( _x( 'P.O. Box Number',
							'option label', 'wpsso-plm' ), '', 'plm_addr_po_box_number' ). 
						'<td colspan="3">'.$this->form->get_input( 'plm_addr_po_box_number_'.$id ).'</td>';
		
						$table_rows['plm_addr_city_'.$id] = $tr_addr_id.$this->form->get_th_html( _x( 'City',
							'option label', 'wpsso-plm' ), '', 'plm_addr_city' ). 
						'<td colspan="3">'.$this->form->get_input( 'plm_addr_city_'.$id ).'</td>';
		
						$table_rows['plm_addr_state_'.$id] = $tr_addr_id.$this->form->get_th_html( _x( 'State / Province',
							'option label', 'wpsso-plm' ), '', 'plm_addr_state' ). 
						'<td colspan="3">'.$this->form->get_input( 'plm_addr_state_'.$id ).'</td>';
		
						$table_rows['plm_addr_zipcode_'.$id] = $tr_addr_id.$this->form->get_th_html( _x( 'Zip / Postal Code',
							'option label', 'wpsso-plm' ), '', 'plm_addr_zipcode' ). 
						'<td colspan="3">'.$this->form->get_input( 'plm_addr_zipcode_'.$id ).'</td>';
		
						$this->form->defaults['plm_addr_country_'.$id] = $this->p->options['plm_addr_def_country'];	// set default value
						$table_rows['plm_addr_country_'.$id] = $tr_addr_id.$this->form->get_th_html( _x( 'Country',
							'option label', 'wpsso-plm' ), '', 'plm_addr_country' ). 
						'<td colspan="3">'.$this->form->get_select_country( 'plm_addr_country_'.$id ).'</td>';
					}

					$table_rows['subsection_og_location'] = '<th></th><td class="subsection" colspan="3"><h4>'.
						_x( 'Facebook / Open Graph Location', 'metabox title', 'wpsso-plm' ).'</h4></td>';
		
					foreach ( $address_names as $id => $name ) {
						$tr_addr_id = '<!-- address id '.$id.' -->'.
							'<tr class="plm_addr_id plm_addr_id_'.$id.'" style="display:none">';
		
						$table_rows['plm_addr_latitude_'.$id] = $tr_addr_id.$this->form->get_th_html( _x( 'Latitude',
							'option label', 'wpsso-plm' ), '', 'plm_addr_latitude' ). 
						'<td colspan="3">'.$this->form->get_input( 'plm_addr_latitude_'.$id, 'required' ).'</td>';
		
						$table_rows['plm_addr_longitude_'.$id] = $tr_addr_id.$this->form->get_th_html( _x( 'Longitude',
							'option label', 'wpsso-plm' ), '', 'plm_addr_longitude' ). 
						'<td colspan="3">'.$this->form->get_input( 'plm_addr_longitude_'.$id, 'required' ).'</td>';
		
						$table_rows['plm_addr_altitude_'.$id] = $tr_addr_id.$this->form->get_th_html( _x( 'Altitude in Meters',
							'option label', 'wpsso-plm' ), '', 'plm_addr_altitude' ). 
						'<td colspan="3">'.$this->form->get_input( 'plm_addr_altitude_'.$id ).'</td>';
					}

					$table_rows['subsection_schema_localbusiness'] = '<th></th><td class="subsection" colspan="3"><h4>'.
						_x( 'Schema Local Business', 'metabox title', 'wpsso-plm' ).'</h4></td>';
		
					foreach ( $address_names as $id => $name ) {
						$tr_addr_id = '<!-- address id '.$id.' -->'.
							'<tr class="plm_addr_id plm_addr_id_'.$id.'" style="display:none">';
		
						$this->form->defaults['plm_addr_business_type_'.$id] = WpssoPlmConfig::$cf['form']['plm_addr_opts']['plm_addr_business_type'];
						$table_rows['plm_addr_business_type_'.$id] = $tr_addr_id.$this->form->get_th_html( _x( 'Local Business Type',
							'option label', 'wpsso-plm' ), '', 'plm_addr_business_type' ). 
						'<td colspan="3">'.$this->form->get_select( 'plm_addr_business_type_'.$id,
							$business_types, 'schema_type' ).'</td>';
		
						$row_number = 1;
						foreach ( $this->p->cf['form']['weekdays'] as $day => $label ) {
							if ( $row_number === 1 )
								$th_cell = $tr_addr_id.$this->form->get_th_html( _x( 'Business Hours',
									'option label', 'wpsso-plm' ), '', 'plm_addr_days' );
							else $th_cell = $tr_addr_id.'<th></th>';
		
							$this->form->defaults['plm_addr_day_'.$day.'_'.$id] = '0';		// set default value
							$this->form->defaults['plm_addr_day_'.$day.'_open_'.$id] = '09:00';	// set default value
							$this->form->defaults['plm_addr_day_'.$day.'_close_'.$id] = '17:00';	// set default value
		
							$table_rows['plm_addr_day_'.$day.'_'.$id] = $th_cell.
								'<td class="short">'.$this->form->get_checkbox( 'plm_addr_day_'.$day.'_'.$id ).' '.$label.'</td>'.
								'<td>Opens at '.$this->form->get_select( 'plm_addr_day_'.$day.'_open_'.$id,
									$half_hours, 'medium', '', true ).'</td>'.
								'<td>Closes at '.$this->form->get_select( 'plm_addr_day_'.$day.'_close_'.$id,
									$half_hours, 'medium', '', true ).'</td>';
							$row_number++;
						}
		
						$table_rows['plm_addr_season_dates_'.$id] = $tr_addr_id.$this->form->get_th_html( _x( 'Seasonal Business Dates',
							'option label', 'wpsso-plm' ), '', 'plm_addr_season_dates' ). 
						'<td colspan="3">Open from '.$this->form->get_input_date( 'plm_addr_season_from_date_'.$id ).
							' through '.$this->form->get_input_date( 'plm_addr_season_to_date_'.$id ).'</td>';
		
						$table_rows['plm_addr_menu_url_'.$id] = $tr_addr_id.$this->form->get_th_html( _x( 'Food Establishment Menu URL',
							'option label', 'wpsso-plm' ), '', 'plm_addr_menu_url' ). 
						'<td colspan="3">'.$this->form->get_input( 'plm_addr_menu_url_'.$id, 'wide' ).'</td>';
		
						$table_rows['plm_addr_accept_res_'.$id] = $tr_addr_id.$this->form->get_th_html( _x( 'Accepts Reservations',
							'option label', 'wpsso-plm' ), '', 'plm_addr_accept_res' ). 
						'<td colspan="3">'.$this->form->get_checkbox( 'plm_addr_accept_res_'.$id ).'</td>';
					}
					break;
			}
			return $table_rows;
		}
	}
}

?>
