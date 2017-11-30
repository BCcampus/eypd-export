<?php
/**
 * Plugin Name:     Excel Export
 * Plugin URI:      https://github.com/BCcampus/excel-export
 * Description:     Export event and user data
 * Author:          Alex Paredes
 * Text Domain:     excel-export
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         Excel_Export
 */

namespace BCcampus\Excel;

/**
 * Load dependencies
 */
require_once __DIR__ . '/vendor/autoload.php';

/**
 * Adds the 'Export to Excel' button on users and events admin screens
 */
function export_button() {
	// only show the export button if PHPExcel exists
	if ( class_exists( 'PHPExcel' ) ) {
		// add export button only on the event and users screen
		$screen      = get_current_screen();
		$allowed     = array( 'edit-event', 'users' );
		$unique_name = '';
		if ( ! in_array( $screen->id, $allowed ) ) {
			return;
		}
		if ( $screen->id == 'users' ) {
			$unique_name = 'users_export';
		} elseif ( $screen->id == 'edit-event' ) {
			$unique_name = 'events_export';
		}
		?>
		<script type="text/javascript">
			jQuery(document).ready(function ($) {
				$('.tablenav.top .clear, .tablenav.bottom .clear').before('<form action="#" method="POST"><input type="hidden" id="wp_excel_export" name="<?php echo $unique_name; ?>" value="1" /><input class="button button-primary export_button" style="margin-top:3px;" type="submit" value="<?php esc_attr_e( 'Export to Excel' );?>" /></form>');
			});
		</script>
		<?php
	}
}

add_action( 'admin_footer', __NAMESPACE__ . '\export_button' );

/**
 * Gets and exports the user and event data
 */
function export() {

	if ( ! empty( $_POST['users_export'] ) || ! empty( $_POST['events_export'] ) ) {

		if ( current_user_can( 'manage_options' ) ) {

			// Create a new PHPExcel object
			$objPHPExcel = new \PHPExcel();

			// User data
			if ( isset( $_POST['users_export'] ) ) {

				// User args
				$args = array(
					'order'   => 'ASC',
					'orderby' => 'display_name',
					'fields'  => 'all',
				);

				// User Query
				$wp_users   = get_users( $args );
				$cell_count = 1;

				// Set up column labels
				$objPHPExcel->getActiveSheet()->SetCellValue( 'A1', esc_html__( 'First Name' ) );
				$objPHPExcel->getActiveSheet()->SetCellValue( 'B1', esc_html__( 'Last Name' ) );
				$objPHPExcel->getActiveSheet()->SetCellValue( 'C1', esc_html__( 'Email' ) );
				$objPHPExcel->getActiveSheet()->SetCellValue( 'D1', esc_html__( 'User Role' ) );
				$objPHPExcel->getActiveSheet()->SetCellValue( 'E1', esc_html__( 'Nickname' ) );
				$objPHPExcel->getActiveSheet()->SetCellValue( 'F1', esc_html__( 'Last Activity' ) );
				$objPHPExcel->getActiveSheet()->SetCellValue( 'G1', esc_html__( 'Phone' ) );
				$objPHPExcel->getActiveSheet()->SetCellValue( 'H1', esc_html__( 'Website' ) );
				$objPHPExcel->getActiveSheet()->SetCellValue( 'I1', esc_html__( 'Registration Date' ) );
				$objPHPExcel->getActiveSheet()->SetCellValue( 'J1', esc_html__( 'Certification Expires' ) );

				// Get the data we want from each user
				foreach ( $wp_users as $user ) {
					$cell_count ++;

					$user_meta         = get_user_meta( $user->ID );
					$first_name        = ( isset( $user_meta['first_name'][0] ) && $user_meta['first_name'][0] != '' ) ? $user_meta['first_name'][0] : '';
					$last_name         = ( isset( $user_meta['last_name'][0] ) && $user_meta['last_name'][0] != '' ) ? $user_meta['last_name'][0] : '';
					$nickname          = ( isset( $user_meta['nickname'][0] ) && $user_meta['nickname'][0] != '' ) ? $user_meta['nickname'][0] : '';
					$role              = implode( ',', $user->roles );
					$email             = $user->user_email;
					$activity          = ( isset( $user_meta['last_activity'][0] ) && $user_meta['last_activity'][0] != '' ) ? $user_meta['last_activity'][0] : '';
					$phone             = ( isset( $user_meta['dbem_phone'][0] ) && $user_meta['dbem_phone'][0] != '' ) ? $user_meta['dbem_phone'][0] : '';
					$url               = $user->user_url;
					$registration_date = $user->user_registered;
					$cert_expire       = ( isset( $user_meta['eypd_cert_expire'][0] ) && $user_meta['eypd_cert_expire'][0] != '' ) ? $user_meta['eypd_cert_expire'][0] : '';

					// Add the user data to the appropriate column
					$objPHPExcel->setActiveSheetIndex( 0 );
					$objPHPExcel->getActiveSheet()->SetCellValue( 'A' . $cell_count . '', $first_name );
					$objPHPExcel->getActiveSheet()->SetCellValue( 'B' . $cell_count . '', $last_name );
					$objPHPExcel->getActiveSheet()->SetCellValue( 'C' . $cell_count . '', $email );
					$objPHPExcel->getActiveSheet()->SetCellValue( 'D' . $cell_count . '', $role );
					$objPHPExcel->getActiveSheet()->SetCellValue( 'E' . $cell_count . '', $nickname );
					$objPHPExcel->getActiveSheet()->SetCellValue( 'F' . $cell_count . '', $activity );
					$objPHPExcel->getActiveSheet()->SetCellValue( 'G' . $cell_count . '', $phone );
					$objPHPExcel->getActiveSheet()->SetCellValue( 'H' . $cell_count . '', $url );
					$objPHPExcel->getActiveSheet()->SetCellValue( 'I' . $cell_count . '', $registration_date );
					$objPHPExcel->getActiveSheet()->SetCellValue( 'J' . $cell_count . '', $cert_expire );

				}

				// Set document properties
				$objPHPExcel->getProperties()->setTitle( esc_html__( 'Users' ) );
				$objPHPExcel->getProperties()->setSubject( esc_html__( 'all users' ) );
				$objPHPExcel->getProperties()->setDescription( esc_html__( 'Export of all users' ) );

				// Rename sheet
				$objPHPExcel->getActiveSheet()->setTitle( esc_html__( 'Users' ) );

				// Rename file
				header( 'Content-Disposition: attachment;filename="users.xlsx"' );

			}

			// Event data
			if ( isset( $_POST['events_export'] ) ) {

				// Event args
				$args = array(
					'post_type'      => 'event',
					'posts_per_page' => - 1,
					'offset'         => 0,
				);

				// Event Query
				$posts      = get_posts( $args );
				$cell_count = 1;

				// Set up column labels
				$objPHPExcel->getActiveSheet()->SetCellValue( 'A1', esc_html__( 'Event Title' ) );
				$objPHPExcel->getActiveSheet()->SetCellValue( 'B1', esc_html__( 'Owner' ) );
				$objPHPExcel->getActiveSheet()->SetCellValue( 'C1', esc_html__( 'Status' ) );
				$objPHPExcel->getActiveSheet()->SetCellValue( 'D1', esc_html__( 'Published date' ) );
				$objPHPExcel->getActiveSheet()->SetCellValue( 'E1', esc_html__( 'Start date' ) );
				$objPHPExcel->getActiveSheet()->SetCellValue( 'F1', esc_html__( 'End date' ) );
				$objPHPExcel->getActiveSheet()->SetCellValue( 'G1', esc_html__( 'Start time' ) );
				$objPHPExcel->getActiveSheet()->SetCellValue( 'H1', esc_html__( 'End time' ) );
				$objPHPExcel->getActiveSheet()->SetCellValue( 'I1', esc_html__( 'Presenter' ) );
				$objPHPExcel->getActiveSheet()->SetCellValue( 'J1', esc_html__( 'Registration Contact E-mail' ) );
				$objPHPExcel->getActiveSheet()->SetCellValue( 'K1', esc_html__( 'Location ID' ) );
				$objPHPExcel->getActiveSheet()->SetCellValue( 'L1', esc_html__( 'Event ID' ) );

				// Get the data we want from each event
				foreach ( $posts as $post ) {
					$cell_count ++;

					$title     = $post->post_title;
					$author_id = $post->post_author;
					$author      = get_the_author_meta( 'display_name', $author_id );
					$status      = $post->post_status;
					$date_pub    = $post->post_date;
					$start       = $post->_event_start_date;
					$end         = $post->_event_end_date;
					$start_time  = $post->_event_start_time;
					$end_time    = $post->_event_end_time;
					$presenter   = $post->{'Presenter(s)'};
					$reg_email   = $post->{'Registration Contact Email'};
					$location_id = $post->_location_id;
					$event_id    = $post->_event_id;

					// Add the event data to the appropriate column
					$objPHPExcel->getActiveSheet()->SetCellValue( 'A' . $cell_count . '', $title );
					$objPHPExcel->getActiveSheet()->SetCellValue( 'B' . $cell_count . '', $author );
					$objPHPExcel->getActiveSheet()->SetCellValue( 'C' . $cell_count . '', $status );
					$objPHPExcel->getActiveSheet()->SetCellValue( 'D' . $cell_count . '', $date_pub );
					$objPHPExcel->getActiveSheet()->SetCellValue( 'E' . $cell_count . '', $start );
					$objPHPExcel->getActiveSheet()->SetCellValue( 'F' . $cell_count . '', $end );
					$objPHPExcel->getActiveSheet()->SetCellValue( 'G' . $cell_count . '', $start_time );
					$objPHPExcel->getActiveSheet()->SetCellValue( 'H' . $cell_count . '', $end_time );
					$objPHPExcel->getActiveSheet()->SetCellValue( 'I' . $cell_count . '', $presenter );
					$objPHPExcel->getActiveSheet()->SetCellValue( 'J' . $cell_count . '', $reg_email );
					$objPHPExcel->getActiveSheet()->SetCellValue( 'K' . $cell_count . '', $location_id );
					$objPHPExcel->getActiveSheet()->SetCellValue( 'L' . $cell_count . '', $event_id );

				}

				// Set document properties
				$objPHPExcel->getProperties()->setTitle( esc_html__( 'Events' ) );
				$objPHPExcel->getProperties()->setSubject( esc_html__( 'all events' ) );
				$objPHPExcel->getProperties()->setDescription( esc_html__( 'Export of all events' ) );

				// Rename sheet
				$objPHPExcel->getActiveSheet()->setTitle( esc_html__( 'Events' ) );

				// Rename file
				header( 'Content-Disposition: attachment;filename="events.xlsx"' );

			}

			// Set column data auto width
			for ( $col = 'A'; $col !== 'E'; $col ++ ) {
				$objPHPExcel->getActiveSheet()->getColumnDimension( $col )->setAutoSize( true );
			}
		}

		header( 'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' );
		header( 'Cache-Control: max-age=0' );

		// Save Excel file
		$objWriter = \PHPExcel_IOFactory::createWriter( $objPHPExcel, 'Excel2007' );
		$objWriter->save( 'php://output' );

		exit();
	}
}

add_action( 'admin_init', __NAMESPACE__ . '\export' );