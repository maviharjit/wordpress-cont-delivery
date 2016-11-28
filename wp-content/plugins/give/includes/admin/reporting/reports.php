<?php
/**
 * Admin Reports Page
 *
 * Language Changes from EDD:
 * 1. "Report Type" stays
 * 2. "Earnings" changes to "Income"
 * 3. "Donors" changes to "Donors"
 * 4. "Payment Method" stays.
 *
 * @package     Give
 * @subpackage  Admin/Reports
 * @copyright   Copyright (c) 2016, WordImpress
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Reports Page
 *
 * Renders the reports page contents.
 *
 * @since 1.0
 * @return void
 */
function give_reports_page() {
	$current_page = admin_url( 'edit.php?post_type=give_forms&page=give-reports' );
	$active_tab   = isset( $_GET['tab'] ) ? $_GET['tab'] : 'reports';
	?>
	<div class="wrap">

		<h1 class="screen-reader-text"><?php esc_html_e( 'Give Reports', 'give' ); ?></h1>

		<h2 class="nav-tab-wrapper">
			<a href="<?php echo esc_url( add_query_arg( array(
				'tab'              => 'reports',
				'settings-updated' => false
			), $current_page ) ); ?>" class="nav-tab <?php echo $active_tab == 'reports' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Reports', 'give' ); ?></a>
			<?php if ( current_user_can( 'export_give_reports' ) ) { ?>
				<a href="<?php echo esc_url( add_query_arg( array(
					'tab'              => 'export',
					'settings-updated' => false
				), $current_page ) ); ?>" class="nav-tab <?php echo $active_tab == 'export' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Export', 'give' ); ?></a>
			<?php } ?>
			<a href="<?php echo esc_url( add_query_arg( array(
				'tab'              => 'logs',
				'settings-updated' => false
			), $current_page ) ); ?>" class="nav-tab <?php echo $active_tab == 'logs' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Logs', 'give' ); ?></a>
			<a href="<?php echo esc_url( add_query_arg( array(
				'tab'              => 'tools',
				'settings-updated' => false
			), $current_page ) ); ?>" class="nav-tab <?php echo $active_tab == 'tools' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Tools', 'give' ); ?></a>
			<?php do_action( 'give_reports_tabs' ); ?>
		</h2>

		<?php
		do_action( 'give_reports_page_top' );
		do_action( 'give_reports_tab_' . $active_tab );
		do_action( 'give_reports_page_bottom' );
		?>
	</div><!-- .wrap -->
	<?php
}

/**
 * Default Report Views
 *
 * @since 1.0
 * @return array $views Report Views
 */
function give_reports_default_views() {
	$views = array(
		'earnings' => esc_html__( 'Income', 'give' ),
		'forms'    => give_get_forms_label_plural(),
		'donors'   => esc_html__( 'Donors', 'give' ),
		'gateways' => esc_html__( 'Payment Methods', 'give' )
	);

	$views = apply_filters( 'give_report_views', $views );

	return $views;
}

/**
 * Default Report Views
 *
 * Checks the $_GET['view'] parameter to ensure it exists within the default allowed views.
 *
 * @param string $default Default view to use.
 *
 * @since 1.0
 * @return string $view Report View
 *
 */
function give_get_reporting_view( $default = 'earnings' ) {

	if ( ! isset( $_GET['view'] ) || ! in_array( $_GET['view'], array_keys( give_reports_default_views() ) ) ) {
		$view = $default;
	} else {
		$view = $_GET['view'];
	}

	return apply_filters( 'give_get_reporting_view', $view );
}

/**
 * Renders the Reports page
 *
 * @since 1.0
 * @return void
 */
function give_reports_tab_reports() {
	$current_view = 'earnings';
	$views        = give_reports_default_views();

	if ( isset( $_GET['view'] ) && array_key_exists( $_GET['view'], $views ) ) {
		$current_view = $_GET['view'];
	}

	do_action( 'give_reports_view_' . $current_view );
}

add_action( 'give_reports_tab_reports', 'give_reports_tab_reports' );

/**
 * Renders the Reports Page Views Drop Downs
 *
 * @since 1.0
 * @return void
 */
function give_report_views() {
	$views        = give_reports_default_views();
	$current_view = isset( $_GET['view'] ) ? $_GET['view'] : 'earnings';
	do_action( 'give_report_view_actions_before' );
	?>
	<form id="give-reports-filter" method="get">
		<select id="give-reports-view" name="view">
			<option value="-1"><?php esc_html_e( 'Report Type', 'give' ); ?></option>
			<?php foreach ( $views as $view_id => $label ) : ?>
				<option value="<?php echo esc_attr( $view_id ); ?>" <?php selected( $view_id, $current_view ); ?>><?php echo $label; ?></option>
			<?php endforeach; ?>
		</select>

		<?php do_action( 'give_report_view_actions' ); ?>

		<input type="hidden" name="post_type" value="give_forms"/>
		<input type="hidden" name="page" value="give-reports"/>
		<?php submit_button( esc_html__( 'Show', 'give' ), 'secondary', 'submit', false ); ?>
	</form>
	<?php
	do_action( 'give_report_view_actions_after' );
}

/**
 * Renders the Reports Give Form Table
 *
 * @since 1.0
 * @uses  Give_Form_Reports_Table::prepare_items()
 * @uses  Give_Form_Reports_Table::display()
 * @return void
 */
function give_reports_forms_table() {

	if ( isset( $_GET['form-id'] ) ) {
		return;
	}

	include( dirname( __FILE__ ) . '/class-form-reports-table.php' );

	$give_table = new Give_Form_Reports_Table();
	$give_table->prepare_items();
	$give_table->display();
}

add_action( 'give_reports_view_forms', 'give_reports_forms_table' );

/**
 * Renders the detailed report for a specific give form
 *
 * @since 1.0
 * @return void
 */
function give_reports_form_details() {
	if ( ! isset( $_GET['form-id'] ) ) {
		return;
	}
	?>
	<div class="tablenav top reports-forms-details-wrap">
		<div class="actions bulkactions">
			<?php give_report_views(); ?>
			&nbsp;
			<button onclick="history.go(-1);" class="button-secondary"><?php esc_html_e( 'Go Back', 'give' ); ?></button>
		</div>
	</div>
	<?php
	give_reports_graph_of_form( absint( $_GET['form-id'] ) );
}

add_action( 'give_reports_view_forms', 'give_reports_form_details' );

/**
 * Renders the Reports Donors Table
 *
 * @since 1.0
 * @uses  Give_Donor_Reports_Table::prepare_items()
 * @uses  Give_Donor_Reports_Table::display()
 * @return void
 */
function give_reports_donors_table() {
	include( dirname( __FILE__ ) . '/class-donor-reports-table.php' );

	$give_table = new Give_Donor_Reports_Table();
	$give_table->prepare_items();
	?>
	<div class="wrap give-reports-donors-wrap">
		<?php do_action( 'give_logs_donors_table_top' ); ?>
		<form id="give-donors-filter" method="get" action="<?php echo admin_url( 'edit.php?post_type=give_forms&page=give-reports&view=donors' ); ?>">
			<?php
			$give_table->search_box( esc_html__( 'Search', 'give' ), 'give-donors' );
			$give_table->display();
			?>
			<input type="hidden" name="post_type" value="give_forms"/>
			<input type="hidden" name="page" value="give-reports"/>
			<input type="hidden" name="view" value="donors"/>
		</form>
		<?php do_action( 'give_logs_donors_table_bottom' ); ?>
	</div>
	<?php
}

add_action( 'give_reports_view_donors', 'give_reports_donors_table' );


/**
 * Renders the Gateways Table
 *
 * @since 1.3
 * @uses  Give_Gateway_Reports_Table::prepare_items()
 * @uses  Give_Gateway_Reports_Table::display()
 * @return void
 */
function give_reports_gateways_table() {
	include( dirname( __FILE__ ) . '/class-gateways-reports-table.php' );

	$give_table = new Give_Gateawy_Reports_Table();
	$give_table->prepare_items();
	$give_table->display();
}

add_action( 'give_reports_view_gateways', 'give_reports_gateways_table' );


/**
 * Renders the Reports Earnings Graphs
 *
 * @since 1.0
 * @return void
 */
function give_reports_earnings() {
	?>
	<div class="tablenav top reports-table-nav">
		<h3 class="alignleft reports-earnings-title"><span><?php esc_html_e( 'Income Over Time', 'give' ); ?></span></h3>

		<div class="alignright actions reports-views-wrap"><?php give_report_views(); ?></div>
	</div>
	<?php
	give_reports_graph();
}

add_action( 'give_reports_view_earnings', 'give_reports_earnings' );


/**
 * Renders the 'Export' tab on the Reports Page
 *
 * @since 1.0
 * @return void
 */
function give_reports_tab_export() {
	?>
	<div id="give-dashboard-widgets-wrap">
		<div id="post-body">
			<div id="post-body-content">

				<?php do_action( 'give_reports_tab_export_content_top' ); ?>

				<table class="widefat export-options-table give-table">
					<thead>
					<tr>
						<th class="row-title"><?php esc_html_e( 'Export Type', 'give' ); ?></th>
						<th><?php esc_html_e( 'Export Options', 'give' ); ?></th>
					</tr>
					</thead>
					<tbody>
					<?php do_action( 'give_reports_tab_export_table_top' ); ?>
					<tr class="give-export-pdf-sales-earnings">
						<td class="row-title">
							<h3><span><?php esc_html_e( 'Export PDF of Donations and Income', 'give' ); ?></span></h3>

							<p><?php esc_html_e( 'Download a PDF of Donations and Income reports for all forms for the current year.', 'give' ); ?></p>
						</td>
						<td>
							<a class="button" href="<?php echo wp_nonce_url( add_query_arg( array( 'give-action' => 'generate_pdf' ) ), 'give_generate_pdf' ); ?>"><?php esc_html_e( 'Generate PDF', 'give' ); ?></a>
						</td>
					</tr>
					<tr class="alternate give-export-sales-earnings">
						<td class="row-title">
							<h3><span><?php esc_html_e( 'Export Income and Donation Stats', 'give' ); ?></span></h3>

							<p><?php esc_html_e( 'Download a CSV of income and donations over time.', 'give' ); ?></p>
						</td>
						<td>
							<form method="post">
								<?php
									printf(
										/* translators: 1: start date dropdown 2: end date dropdown */
										esc_html__( '%1$s to %2$s', 'give' ),
										Give()->html->year_dropdown( 'start_year' ) . ' ' . Give()->html->month_dropdown( 'start_month' ),
										Give()->html->year_dropdown( 'end_year' )   . ' ' . Give()->html->month_dropdown( 'end_month' )
									);
								?>
								<input type="hidden" name="give-action" value="earnings_export"/>
								<input type="submit" value="<?php esc_attr_e( 'Generate CSV', 'give' ); ?>" class="button-secondary"/>
							</form>
						</td>
					</tr>
					<tr class="give-export-payment-history">
						<td class="row-title">
							<h3><span><?php esc_html_e( 'Export Donation History', 'give' ); ?></span></h3>

							<p><?php esc_html_e( 'Download a CSV of all donations recorded.', 'give' ); ?></p>
						</td>
						<td>
							<form id="give-export-payments" class="give-export-form" method="post">
								<?php
								$args = array(
									'id'          => 'give-payment-export-start',
									'name'        => 'start',
									'placeholder' => esc_attr__( 'Start date', 'give' )
								);
								echo Give()->html->date_field( $args ); ?>
								<?php
								$args = array(
									'id'          => 'give-payment-export-end',
									'name'        => 'end',
									'placeholder' => esc_attr__( 'End date', 'give' )
								);
								echo Give()->html->date_field( $args ); ?>
								<select name="status">
									<option value="any"><?php esc_html_e( 'All Statuses', 'give' ); ?></option>
									<?php
									$statuses = give_get_payment_statuses();
									foreach ( $statuses as $status => $label ) {
										echo '<option value="' . $status . '">' . $label . '</option>';
									}
									?>
								</select>
								<?php wp_nonce_field( 'give_ajax_export', 'give_ajax_export' ); ?>
								<input type="hidden" name="give-export-class" value="Give_Batch_Payments_Export"/>
								<span>
									<input type="submit" value="<?php esc_attr_e( 'Generate CSV', 'give' ); ?>" class="button-secondary"/>
									<span class="spinner"></span>
								</span>
							</form>

						</td>
					</tr>
					<tr class="alternate give-export-donors">
						<td class="row-title">
							<h3><span><?php esc_html_e( 'Export Donors in CSV', 'give' ); ?></span></h3>

							<p><?php esc_html_e( 'Download an export of donors for all donation forms or only those who have given to a particular form.', 'give' ); ?></p>
						</td>
						<td>
							<form method="post" id="give_donor_export" class="give-export-form">

									<?php
									$args = array(
										'name'   => 'forms',
										'id'     => 'give_customer_export_form',
										'chosen' => true
									);
									echo Give()->html->forms_dropdown( $args ); ?>
								
								<input type="submit" value="<?php esc_attr_e( 'Generate CSV', 'give' ); ?>" class="button-secondary"/>

								<div id="export-donor-options-wrap" class="give-clearfix">
									<p><?php esc_html_e( 'Export Columns', 'give' ); ?>:</p>
									<ul id="give-export-option-ul">
										<li>
											<label for="give-export-fullname"><input type="checkbox" checked name="give_export_option[full_name]" id="give-export-fullname"><?php esc_html_e( 'Name', 'give' ); ?>
											</label>
										</li>
										<li>
											<label for="give-export-email"><input type="checkbox" checked name="give_export_option[email]" id="give-export-email"><?php esc_html_e( 'Email', 'give' ); ?>
											</label>
										</li>
										<li>
											<label for="give-export-address"><input type="checkbox" checked name="give_export_option[address]" id="give-export-address"><?php esc_html_e( 'Address', 'give' ); ?>
											</label>
										</li>
										<li>
											<label for="give-export-userid"><input type="checkbox" checked name="give_export_option[userid]" id="give-export-userid"><?php esc_html_e( 'User ID', 'give' ); ?>
											</label>
										</li>
										<li>
											<label for="give-export-first-donation-date"><input type="checkbox" checked name="give_export_option[date_first_donated]" id="give-export-first-donation-date"><?php esc_html_e( 'First Donation Date', 'give' ); ?>
											</label>
										</li>
										<li>
											<label for="give-export-donation-number"><input type="checkbox" checked name="give_export_option[donations]" id="give-export-donation-number"><?php esc_html_e( 'Number of Donations', 'give' ); ?>
											</label>
										</li>
										<li>
											<label for="give-export-donation-sum"><input type="checkbox" checked name="give_export_option[donation_sum]" id="give-export-donation-sum"><?php esc_html_e( 'Total Donated', 'give' ); ?>
											</label>
										</li>
									</ul>
								</div>
								<?php wp_nonce_field( 'give_ajax_export', 'give_ajax_export' ); ?>
								<input type="hidden" name="give-export-class" value="Give_Batch_Customers_Export"/>
								<input type="hidden" name="give-action" value="email_export"/>
							</form>
						</td>
					</tr>
					<?php do_action( 'give_reports_tab_export_table_bottom' ); ?>
					</tbody>
				</table>

				<?php do_action( 'give_reports_tab_export_content_bottom' ); ?>

			</div>
			<!-- .post-body-content -->
		</div>
		<!-- .post-body -->
	</div><!-- #give-dashboard-widgets-wrap -->
	<?php
}

add_action( 'give_reports_tab_export', 'give_reports_tab_export' );

/**
 * Renders the Reports page
 *
 * @since 1.0
 * @return void
 */
function give_reports_tab_logs() {

	require( GIVE_PLUGIN_DIR . 'includes/admin/reporting/logs.php' );

	$current_view = 'sales';
	$log_views    = give_log_default_views();

	if ( isset( $_GET['view'] ) && array_key_exists( $_GET['view'], $log_views ) ) {
		$current_view = $_GET['view'];
	}

	do_action( 'give_logs_view_' . $current_view );
}

add_action( 'give_reports_tab_logs', 'give_reports_tab_logs' );

/**
 * Retrieves estimated monthly earnings and sales
 *
 * @since 1.0
 * @return array
 */
function give_estimated_monthly_stats() {

	$estimated = get_transient( 'give_estimated_monthly_stats' );

	if ( false === $estimated ) {

		$estimated = array(
			'earnings' => 0,
			'sales'    => 0
		);

		$stats = new Give_Payment_Stats;

		$to_date_earnings = $stats->get_earnings( 0, 'this_month' );
		$to_date_sales    = $stats->get_sales( 0, 'this_month' );

		$current_day   = date( 'd', current_time( 'timestamp' ) );
		$current_month = date( 'n', current_time( 'timestamp' ) );
		$current_year  = date( 'Y', current_time( 'timestamp' ) );
		$days_in_month = cal_days_in_month( CAL_GREGORIAN, $current_month, $current_year );

		$estimated['earnings'] = ( $to_date_earnings / $current_day ) * $days_in_month;
		$estimated['sales']    = ( $to_date_sales / $current_day ) * $days_in_month;

		// Cache for one day
		set_transient( 'give_estimated_monthly_stats', $estimated, 86400 );
	}

	return maybe_unserialize( $estimated );
}
