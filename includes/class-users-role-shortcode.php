<?php
/**
 * UsersRoleShortcode Class.
 */
class UsersRoleShortcode {
	/**
	 * Holds the class instance.
	 *
	 * @var UsersRoleShortcode $instance.
	 */
	private static $instance = null;

	/**
	 * Main Instance
	 *
	 * Ensures that only one instance exists in memory at any one
	 * time. Also prevents needing to define globals all over the place.
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Initializes the plugin.
	 */
	private function __construct() {
		add_action( 'init', array( $this, 'init' ) );
	}

	/**
	 * Hook onto init
	 */
	public function init() {
		add_shortcode( 'users-role-table', array( $this, 'users_role_shortcode' ) );
		// Datatables style.
		wp_enqueue_style( 'datatables-css', 'https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.19/css/jquery.dataTables.css', null, '001' );
		// Ajax hook.
		add_action( 'wp_ajax_users-role-shortcode', array( $this, 'users_role_ajax' ) );
		add_action( 'wp_ajax_nopriv_users-role-shortcode', array( $this, 'users_role_ajax' ) );
	}

	/**
	 * Register shortcode hook
	 */
	public function users_role_shortcode() {
		// example: [users-role-table].

		// Check privileges (must be admin to see user list).
		if ( current_user_can( 'list_users' ) ) {
			// Use datatables library.
			wp_enqueue_script( 'datatables-library', 'https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.19/js/jquery.dataTables.min.js', array( 'jquery' ), '001', false );
			// Enqueue custom js.
			wp_enqueue_script( 'users-role-shortcode', USERS_ROLE_SHORTCODE__PLUGIN_URL . 'public/js/users-role-shortcode-public.js', array( 'jquery', 'wp-i18n' ), '001', true );
			wp_localize_script(
				'users-role-shortcode',
				'ajax_object',
				array(
					'ajax_url'   => admin_url( 'admin-ajax.php' ),
					'ajax_nonce' => wp_create_nonce( 'users-role-shortcode-nonce' ),
				)
			);
		}
		ob_start();
		?>
		<table id="users-role-shortcode" class="display compact" style="width:100%;padding-top: 20px;font-size:0.8em;">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Username', 'users-role-shortcode' ); ?></th>
					<th><?php esc_html_e( 'Display Name', 'users-role-shortcode' ); ?></th>
					<th><?php esc_html_e( 'Role', 'users-role-shortcode' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				// Check privileges (must be admin to see user list).
				if ( ! current_user_can( 'list_users' ) ) {
					?>
				<tr class="odd">
					<td valign="top" colspan="3" class="dataTables_empty" style="text-align:center;"><?php esc_html_e( 'You must have Administrator privileges to view the user list', 'users-role-shortcode' ); ?></td>
				</tr>
				<?php } ?>
			</tbody>
		</table>
		<?php
		return ob_get_clean();
	}

	/**
	 * Register ajax response
	 */
	public function users_role_ajax() {
		$request = filter_input_array( INPUT_POST );
		// initialize empty response data.
		$response_data = array(
			'draw'            => intval( $request['draw'] ),
			'recordsTotal'    => 0,
			'recordsFiltered' => 0,
			'dataTable'       => array(),
			'dataRoles'       => $this->get_roles(),
		);
		// Check nonce for security.
		if ( ! wp_verify_nonce( $request['users_role_shortcode_nonce'], 'users-role-shortcode-nonce' ) ) {
			wp_send_json( $response_data );
			wp_die();
		}
		// Check privileges (must be admin to see user list).
		if ( ! current_user_can( 'list_users' ) ) {
			wp_send_json( $response_data );
			wp_die();
		}

		$columns = array(
			0 => 'user_name',
			1 => 'display_name',
			2 => 'role',
		);

		$args = array(
			'number' => $request['length'],
			'offset' => $request['start'],
			'order'  => $request['order'][0]['dir'],
		);

		// Order by.
		$args['orderby'] = $columns[ $request['order'][0]['column'] ];
		// Search Bar.
		if ( ! empty( $request['search']['value'] ) ) {
			$args['search'] = '*' . sanitize_text_field( $request['search']['value'] . '*' );
		}
		// Role Filter.
		if ( ! empty( $request['columns'][2]['search']['value'] ) ) {
			$search_meta_value  = $request['columns'][2]['search']['value'];
			$args['meta_query'] = array(
				'relation' => 'AND',
				array(
					'key'     => 'role',
					'value'   => sanitize_text_field( $search_meta_value ),
					'compare' => 'REGEXP',
				),
			);
		}
		$user_query  = new WP_User_Query( $args );
		$query_total = $user_query->get_total();

		// Users Array.
		$users = array();

		// User Loop.
		if ( ! empty( $user_query->get_results() ) ) {
			foreach ( $user_query->get_results() as $user ) {
				$user_info = array(
					'id'           => $user->ID,
					'user_name'    => $user->user_login,
					'display_name' => $user->display_name,
					'role'         => $user->role,
				);

				// add to users.
				$users[] = $user_info;
			}
		}
		// Add users results.
		if ( ! empty( $users ) ) {
			$response_data['recordsTotal']    = intval( $query_total );
			$response_data['recordsFiltered'] = intval( $query_total );
			$response_data['dataTable']       = $users;
		}
		wp_send_json( $response_data );
		wp_die();
	}

	/**
	 * Return simple array of user roles
	 */
	public static function get_roles() {
		global $wp_roles;
		if ( ! empty( $wp_roles ) && ! empty( $wp_roles->roles ) ) {
			$roles      = $wp_roles->roles;
			$role_names = array();
			foreach ( $roles as $role ) {
				$role_names[] = $role['name'];
			}
			sort( $role_names );
			return $role_names;
		}
		return array();
	}

	/**
	 * Plugin Activation Hook
	 */
	public static function register_activation_hook() {
		// Check PHP Version and deactivate & die if it doesn't meet minimum requirements.
		if ( version_compare( PHP_VERSION, '5.6.0', '<' ) ) {
			deactivate_plugins( plugin_basename( __FILE__ ) );
			wp_die( esc_html__( 'This plugin requires PHP Version 5.6.0 or greater.', 'users-role-shortcode' ) );
		}
		// Check WP Version and deactivate & die if it doesn't meet minimum requirements.
		if ( function_exists( 'get_bloginfo' ) && version_compare( get_bloginfo( 'version' ), '5.0', '<' ) ) {
			deactivate_plugins( plugin_basename( __FILE__ ) );
			wp_die( esc_html__( 'This plugin requires WordPress version 5.0 or higher.', 'users-role-shortcode' ) );
		}
	}

	/**
	 * Plugin Deactivation Hook
	 */
	public static function register_deactivation_hook() {
		return true;
	}
}
