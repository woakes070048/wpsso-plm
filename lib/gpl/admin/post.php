<?php
/*
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl.txt
 * Copyright 2014-2016 Jean-Sebastien Morisset (http://surniaulula.com/)
 */

if ( ! defined( 'ABSPATH' ) ) 
	die( 'These aren\'t the droids you\'re looking for...' );

if ( ! class_exists( 'WpssoPlmGplAdminPost' ) ) {

	class WpssoPlmGplAdminPost {

		public function __construct( &$plugin ) {
			$this->p =& $plugin;
			$this->p->util->add_plugin_filters( $this, array( 
				'post_social_settings_tabs' => 2,	// $tabs, $mod
				'post_plm_rows' => 4,			// $table_rows, $form, $head, $mod
			), 200 );
		}

		public function filter_post_social_settings_tabs( $tabs, $mod ) {
			if ( empty( $this->p->options['plm_add_to_'.$mod['post_type']] ) )
				return $tabs;
			$new_tabs = array();
			foreach ( $tabs as $key => $val ) {
				$new_tabs[$key] = $val;
				if ( $key === 'media' )
					$new_tabs['plm'] = _x( 'Place / Location',
						'metabox tab', 'wpsso-plm' );
			}
			return $new_tabs;
		}

		public function filter_post_plm_rows( $table_rows, $form, $head, $mod ) {

			$half_hours = SucomUtil::get_hours_range( 0, 86400, 60 * 30, '' );	// $format = ''
			$business_types = $this->p->schema->get_schema_types_select( $this->p->cf['head']['schema_type']['place']['local.business'], false );
			$address_names = array( 'custom' => WpssoPlmConfig::$cf['form']['plm_address']['custom'] );

			$table_rows[] = '<td colspan="4" align="center">'.
				$this->p->msgs->get( 'pro-feature-msg', array( 'lca' => 'wpssoplm' ) ).'</td>';

			$table_rows['plm_addr_id'] = $form->get_th_html( _x( 'Select an Address',
				'option label', 'wpsso-plm' ), 'medium', 'post-plm_addr_id' ).
			'<td class="blank" colspan="3">'.$form->get_no_select( 'plm_addr_id', $address_names,
				'full_name', '', true ).'</td>';

			$table_rows['subsection_schema_place'] = '<td></td><td class="subsection" colspan="3"><h4>'.
				_x( 'Pinterest Rich Pin / Schema Place', 'metabox title', 'wpsso-plm' ).'</h4></td>';

			$table_rows['plm_addr_streetaddr'] = $form->get_th_html( _x( 'Street Address',
				'option label', 'wpsso-plm' ), 'medium', 'plm_addr_streetaddr' ). 
			'<td class="blank" colspan="3">'.$form->get_no_input_value( '', 'wide' ).'</td>';

			$table_rows['plm_addr_po_box_number'] = $form->get_th_html( _x( 'P.O. Box Number',
				'option label', 'wpsso-plm' ), 'medium', 'plm_addr_po_box_number' ). 
			'<td class="blank" colspan="3">'.$form->get_no_input_value().'</td>';

			$table_rows['plm_addr_city'] = $form->get_th_html( _x( 'City',
				'option label', 'wpsso-plm' ), 'medium', 'plm_addr_city' ). 
			'<td class="blank" colspan="3">'.$form->get_no_input_value().'</td>';

			$table_rows['plm_addr_state'] = $form->get_th_html( _x( 'State / Province',
				'option label', 'wpsso-plm' ), 'medium', 'plm_addr_state' ). 
			'<td class="blank" colspan="3">'.$form->get_no_input_value().'</td>';

			$table_rows['plm_addr_zipcode'] = $form->get_th_html( _x( 'Zip / Postal Code',
				'option label', 'wpsso-plm' ), 'medium', 'plm_addr_zipcode' ). 
			'<td class="blank" colspan="3">'.$form->get_no_input_value().'</td>';

			$table_rows['plm_addr_country'] = $form->get_th_html( _x( 'Country', 
				'option label', 'wpsso-plm' ), 'medium', 'plm_addr_country' ). 
			'<td class="blank"colspan="3">'.$form->get_no_select_country( 'plm_addr_country' ).'</td>';

			$table_rows['subsection_og_location'] = '<td></td><td class="subsection" colspan="3"><h4>'.
				_x( 'Facebook / Open Graph Location', 'metabox title', 'wpsso-plm' ).'</h4></td>';

			$table_rows['plm_addr_latitude'] = $form->get_th_html( _x( 'Latitude',
				'option label', 'wpsso-plm' ), 'medium', 'plm_addr_latitude' ). 
			'<td class="blank" colspan="3">'.$form->get_no_input( '', 'required' ).'</td>';

			$table_rows['plm_addr_longitude'] = $form->get_th_html( _x( 'Longitude',
				'option label', 'wpsso-plm' ), 'medium', 'plm_addr_longitude' ). 
			'<td class="blank" colspan="3">'.$form->get_no_input( '', 'required' ).'</td>';

			$table_rows['plm_addr_altitude'] = $form->get_th_html( _x( 'Altitude in Meters',
				'option label', 'wpsso-plm' ), 'medium', 'plm_addr_altitude' ). 
			'<td class="blank" colspan="3">'.$form->get_no_input().'</td>';

			$table_rows['subsection_schema_localbusiness'] = '<td></td><td class="subsection" colspan="3"><h4>'.
				_x( 'Schema Local Business', 'metabox title', 'wpsso-plm' ).'</h4></td>';

			$table_rows['plm_addr_business_type'] = $form->get_th_html( _x( 'Local Business Type',
				'option label', 'wpsso-plm' ), 'medium', 'plm_addr_business_type' ). 
			'<td class="blank" colspan="3">'.$form->get_no_select( 'plm_addr_business_type',
				$business_types, 'schema_type', '', true ).'</td>';

			$row_number = 1;
			foreach ( $this->p->cf['form']['weekdays'] as $day => $label ) {
				if ( $row_number === 1 )
					$th_cell = $form->get_th_html( _x( 'Business Hours',
						'option label', 'wpsso-plm' ), 'medium', 'plm_addr_days' );
				else $th_cell = '<td></td>';

				$table_rows['plm_addr_day_'.$day] = $th_cell.
					'<td class="blank short">'.$form->get_no_checkbox( 'plm_addr_day_'.$day ).' '.$label.'</td>'.
					'<td class="blank">Opens at '.$form->get_no_select( 'plm_addr_day_'.$day.'_open',
						$half_hours, 'medium', '', true ).'</td>'.
					'<td class="blank">Closes at '.$form->get_no_select( 'plm_addr_day_'.$day.'_close',
						$half_hours, 'medium', '', true ).'</td>';

				$row_number++;
			}

			$table_rows['plm_addr_season_dates'] = $form->get_th_html( _x( 'Seasonal Business Dates',
				'option label', 'wpsso-plm' ), 'medium', 'plm_addr_season_dates' ). 
			'<td class="blank" colspan="3">Open from '.$form->get_no_input_date().
				' through '.$form->get_no_input_date().'</td>';

			$table_rows['plm_addr_menu_url'] = $form->get_th_html( _x( 'Food Menu URL',
				'option label', 'wpsso-plm' ), 'medium', 'plm_addr_menu_url' ). 
			'<td class="blank" colspan="3">'.$form->get_no_input_value( '', 'wide' ).'</td>';

			$table_rows['plm_addr_accept_res'] = $form->get_th_html( _x( 'Accepts Reservations',
				'option label', 'wpsso-plm' ), 'medium', 'plm_addr_accept_res' ). 
			'<td class="blank" colspan="3">'.$form->get_no_checkbox( 'plm_addr_accept_res' ).'</td>';

			return $table_rows;
		}
	}
}

?>
