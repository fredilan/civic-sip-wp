<?php

if( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class My_List_Table extends WP_List_Table {

	function _construct() {
  		add_action( 'admin_head', array( &$this, 'admin_header' ) );
	}

	/**
 	* Retrieve civic user’s data from the database
 	*
 	* @param int $per_page
 	* @param int $page_number
 	*
 	* @return mixed
 	*/
	function get_civic_users( $per_page = 5, $page_number = 1 ) {

  		global $wpdb;
  		$sql = "SELECT * FROM {$wpdb->prefix}civic_userdata";
 	 	if ( ! empty( $_REQUEST['orderby'] ) ) {
    			$sql .= ' ORDER BY ' . esc_sql( $_REQUEST['orderby'] );
    			$sql .= ! empty( $_REQUEST['order'] ) ? ' ' . esc_sql( $_REQUEST['order'] ) : ' ASC';
 	 	}

  		$sql .= " LIMIT $per_page";
 	 	$sql .= ' OFFSET ' . ( $page_number - 1 ) * $per_page;

  		$result = $wpdb->get_results( $sql, 'ARRAY_A' );
 	 	return $result;
	}

	/**
 	* Delete a civic user record.
 	*
 	* @param int $id civic userdata ID
 	*/
	function delete_civic_user( $id ) {
  		global $wpdb;

  		$wpdb->delete(
    			"{$wpdb->prefix}civic_userdata",
    			[ 'id' => $id ],
    			[ '%d' ]
  		);
	}

	/**
 	* Returns the count of records in the database.
 	*
 	* @return null|string
 	*/
	function record_count() {
 		global $wpdb;
  		$sql = "SELECT COUNT(*) FROM {$wpdb->prefix}civic_userdata";
 		return $wpdb->get_var( $sql );
	}

	function get_bulk_actions() {
  		$actions = array(
    			'delete'    => 'Delete'
  		);
  		return $actions;
	}

	public function process_bulk_action() {

  		// Detect when a bulk action is being triggered...
  		if ( 'delete' === $this->current_action() ) {

    			// In our file that handles the request, verify the nonce.
    			$nonce = esc_attr( $_REQUEST['_wpnonce'] );

    			if ( ! wp_verify_nonce( $nonce, 'sp_delete_civic_user' ) ) {
      				die( 'Go get a life script kiddies' );
 	   		} else {
      				$this->delete_civic_user( absint( $_GET['civic_user'] ) );
      				wp_redirect( esc_url( add_query_arg() ) );
      				exit;
    			}
		}

  		// If the delete bulk action is triggered
  		if ( ( isset( $_POST['action'] ) && $_POST['action'] == 'bulk-delete' )
       		|| ( isset( $_POST['action2'] ) && $_POST['action2'] == 'bulk-delete' ) ) {

 			$delete_ids = esc_sql( $_POST['bulk-delete'] );

    			// loop over the array of record IDs and delete them
    			foreach ( $delete_ids as $id ) {
      				$this->delete_civic_user( $id );
    			}

    			wp_redirect( esc_url( add_query_arg() ) );
    			exit;
  		}
	}

	function get_columns(){
  		$columns = array(
    			'cb' => '<input type="checkbox" />',
    			'personal_email'      => 'Email Address',
    			'personal_phonenumber'      => 'Phone Number',
    			'genericid_type' => 'Type',
    			'genericid_name' => 'Name',
    			'genericid_number' => 'Number',
    			'genericid_dob' => 'Birth Date',
    			'genericid_issuance_date'    => 'Issuance Date',
    			'genericid_expiry_date'      => 'Expiry Date',
    			'genericid_country'      => 'Country',
  		);
  		return $columns;
	}

	function column_cb($item) {
        	return sprintf(
            		'<input type="checkbox" name="book[]" value="%s" />', $item['id']
        	);    
	}

	function get_sortable_columns() {
  		$sortable_columns = array(
    			'personal_email'  => array('personal_email',false),
    			'personal_phonenumber'  => array('personal_phonenumber',false),
    			'genericid_type'  => array('genericid_type',false),
    			'genericid_name'  => array('genericid_name',false),
    			'genericid_number'  => array('genericid_number',false),
    			'genericid_dob'  => array('genericid_dob',false),
    			'genericid_issuance_date' => array('genericid_issuance_date',false),
    			'genericid_expiry_date'   => array('genericid_expiry_date',false),
    			'genericid_country'   => array('genericid_country',false)
  		);
  		return $sortable_columns;
	}

	function usort_reorder( $a, $b ) {
  		// If no sort, default to ID
  		$orderby = ( ! empty( $_GET['orderby'] ) ) ? $_GET['orderby'] : 'personal_email';

  		// If no order, default to asc
  		$order = ( ! empty($_GET['order'] ) ) ? $_GET['order'] : 'asc';

  		// Determine sort order
  		$result = strcmp( $a[$orderby], $b[$orderby] );

  		// Send final sort direction to usort
  		return ( $order === 'asc' ) ? $result : -$result;
	}

	function prepare_items() {
 		$columns  = $this->get_columns();
  		$hidden   = array();
  		$sortable = $this->get_sortable_columns();
  		$this->_column_headers = array( $columns, $hidden, $sortable );
  
  		$per_page = $this->get_items_per_page( 'civic_users_per_page', 20 );
  		$current_page = $this->get_pagenum();
  		$total_items = $this->record_count();
  		$this->set_pagination_args( 
			array(
    				'total_items' => $total_items, // WE have to calculate the total number of items
    				'per_page'    => $per_page // WE have to determine how many items to show on a page
  			)
		);

  		$this->items = $this->get_civic_users( $per_page, $current_page );
  		usort( $this->items, array( &$this, 'usort_reorder' ) );
	}

	function column_personal_email($item) {
  		// create a nonce
  		$delete_nonce = wp_create_nonce( 'sp_delete_civic_user' );

  		$actions = array(
					'view' => '<a href="#TB_inline?height=800&width=1200&inlineId=civic_document_' . $item[ 'id' ] . '" class="thickbox">View Document</a><div id="civic_document_' . $item[ 'id' ] . '" style="display:none;">' . '<img src="data:image/jpeg;base64,' . base64_encode($item[ 'genericid_image' ]) . '"/>' . '</div>',
            		'delete'    => sprintf('<a href="?page=%s&action=%s&civic_user=%s&_wpnonce=%s">Delete</a>',$_REQUEST['page'],'delete',$item['id'], $delete_nonce),
        	);

  		return sprintf('%1$s %2$s', $item['personal_email'], $this->row_actions($actions) );
	}

	function column_default( $item, $column_name ) {
  		switch( $column_name ) { 
    			case 'id':
    			case 'genericid_type':
    			case 'genericid_name':
    			case 'genericid_number':
    			case 'genericid_dob':
    			case 'genericid_issuance_date':
    			case 'genericid_expiry_date':
    			case 'genericid_country':
    			case 'personal_email':
    			case 'personal_phonenumber':
      				return $item[ $column_name ];
    			default:
      				return print_r( $item, true ) ; //Show the whole array for troubleshooting purposes
  		}
	}

	function no_items() {
  		_e( 'No Civic User Data found.' );
	}
}

$option = 'per_page';

$args   = [
	'label'   => 'Civic_Users_List',
        	'default' => 5,
                'option'  => 'civic_users_per_page'
     	];

add_screen_option( $option, $args );


$myListTable = new My_List_Table();
$myListTable->prepare_items(); 
?>
<div class="wrap">
        <div style="margin:10px auto; text-align: center;">
                <svg width="446px" height="35px" viewBox="0 0 446 160" version="1.1" xmlns="http://www.w3.org/2000/svg"
                     xmlns:sketch="http://www.bohemiancoding.com/sketch/ns">
                        <g id="Page-1" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd" sketch:type="MSPage">
                                <g id="Artboard-5-Copy-5" sketch:type="MSArtboardGroup"
                                   transform="translate(-257.000000, -160.000000)">
                                        <g id="Page-1" sketch:type="MSLayerGroup" transform="translate(257.000000, 160.000000)">
                                                <path d="M88.229375,86.2274355 C94.4595702,83.1794975 98.75,76.778485 98.75,69.3750625 C98.75,59.0194375 90.355625,50.6250625 80,50.6250625 C69.644375,50.6250625 61.25,59.0194375 61.25,69.3750625 C61.25,76.7787325 65.5407168,83.1799256 71.77125,86.2277412 L71.77125,109.375 L88.229375,109.375 L88.229375,86.2274355 Z M80,140 C46.915625,140 20,113.084375 20,80 C20,46.915625 46.915625,20 80,20 C106.99875,20 129.88625,37.92625 137.394375,62.5 L158.075,62.5 C150.093125,26.735 118.170625,0 80,0 C35.816875,0 0,35.8175 0,80 C0,124.1825 35.816875,160 80,160 C118.170625,160 150.093125,133.265 158.075,97.5 L137.394375,97.5 C129.88625,122.07375 106.99875,140 80,140 Z"
                                                      id="Fill-1" fill="#3AB03E" sketch:type="MSShapeGroup"></path>
                                                <path d="M241.591688,94.271125 C239.659188,96.291125 237.388562,97.89425 234.843562,99.033625 C232.312313,100.16925 229.564188,100.74425 226.674813,100.74425 C223.781062,100.74425 221.052937,100.194875 218.564188,99.109875 C216.067313,98.02175 213.871062,96.501125 212.036063,94.5905 C210.187938,92.667375 208.692313,90.40425 207.592313,87.86425 C206.499188,85.347375 205.944812,82.612375 205.944812,79.734875 C205.944812,76.8555 206.500438,74.0955 207.594813,71.52925 C208.695437,68.946125 210.190438,66.66175 212.036063,64.739875 C213.874813,62.82675 216.071688,61.306125 218.564188,60.219875 C221.053563,59.134875 223.782312,58.58425 226.674813,58.58425 C229.556687,58.58425 232.279188,59.15925 234.766063,60.29175 C237.273563,61.436125 239.529813,62.999875 241.472313,64.938625 L242.349187,65.814875 L252.117937,56.198 L251.211063,55.307375 C248.037312,52.192375 244.331063,49.698625 240.194188,47.893625 C236.040437,46.083 231.491687,45.164875 226.674813,45.164875 C221.860438,45.164875 217.287937,46.094875 213.084813,47.92925 C208.900438,49.757375 205.194188,52.2455 202.067937,55.32425 C198.931062,58.413 196.414813,62.101125 194.589188,66.28425 C192.754813,70.483 191.824813,75.008625 191.824813,79.734875 C191.824813,84.46425 192.755438,88.968 194.591062,93.11925 C196.417937,97.25675 198.930438,100.941125 202.060438,104.068625 C205.191688,107.2005 208.904188,109.693 213.096062,111.474875 C217.294813,113.259875 221.863563,114.164875 226.674813,114.164875 C231.488563,114.164875 236.059188,113.248 240.259812,111.439875 C244.444188,109.636125 248.176063,107.139875 251.350437,104.02175 L252.249813,103.138625 L242.476063,93.346125 L241.591688,94.271125 Z M269.284625,14.9248125 C266.8115,14.9248125 264.6815,15.7735625 262.95525,17.4473125 C261.2165,19.1323125 260.33525,21.2479375 260.33525,23.7348125 C260.33525,26.2241875 261.217125,28.3398125 262.954,30.0216875 C264.679,31.6954375 266.809,32.5448125 269.284625,32.5448125 C271.75775,32.5448125 273.88775,31.6960625 275.614625,30.0223125 C277.353375,28.3366875 278.234625,26.2216875 278.234625,23.7348125 C278.234625,21.2504375 277.353375,19.1354375 275.614,17.4466875 C273.884625,15.7729375 271.75525,14.9248125 269.284625,14.9248125 Z M262.295,112.065 L276.275,112.065 L276.275,47.265 L262.295,47.265 L262.295,112.065 Z M318.98925,87.2148125 L299.82925,47.2648125 L285.1355,47.2648125 L318.003625,114.164812 L319.982375,114.164812 L352.704875,47.2648125 L338.012375,47.2648125 L318.98925,87.2148125 Z M368.64075,14.9943125 C366.167625,14.9943125 364.037625,15.8430625 362.311375,17.5168125 C360.572625,19.2018125 359.691375,21.3174375 359.691375,23.8043125 C359.691375,26.2936875 360.57325,28.4093125 362.310125,30.0911875 C364.035125,31.7649375 366.165125,32.6143125 368.64075,32.6143125 C371.113875,32.6143125 373.243875,31.7655625 374.97075,30.0918125 C376.7095,28.4061875 377.59075,26.2911875 377.59075,23.8043125 C377.59075,21.3199375 376.7095,19.2049375 374.970125,17.5161875 C373.24075,15.8424375 371.111375,14.9943125 368.64075,14.9943125 Z M361.65125,112.134375 L375.63125,112.134375 L375.63125,47.334375 L361.65125,47.334375 L361.65125,112.134375 Z M436.282375,93.3458125 L435.398,94.2708125 C433.4655,96.2914375 431.194875,97.8939375 428.649875,99.0339375 C426.118625,100.168937 423.3705,100.744562 420.481125,100.744562 C417.587375,100.744562 414.85925,100.194562 412.3705,99.1101875 C409.873625,98.0214375 407.677375,96.5014375 405.842375,94.5901875 C403.99425,92.6670625 402.498625,90.4045625 401.398625,87.8639375 C400.3055,85.3476875 399.751125,82.6120625 399.751125,79.7351875 C399.751125,76.8558125 400.306125,74.0951875 401.401125,71.5289375 C402.50175,68.9458125 403.99675,66.6614375 405.842375,64.7395625 C407.681125,62.8264375 409.878,61.3064375 412.3705,60.2201875 C414.859875,59.1345625 417.588625,58.5845625 420.481125,58.5845625 C423.363,58.5845625 426.0855,59.1589375 428.572375,60.2914375 C431.079875,61.4364375 433.336125,62.9995625 435.278625,64.9389375 L436.1555,65.8145625 L445.92425,56.1976875 L445.017375,55.3070625 C441.843625,52.1926875 438.137375,49.6983125 434.0005,47.8939375 C429.84675,46.0826875 425.298,45.1645625 420.481125,45.1645625 C415.66675,45.1645625 411.09425,46.0945625 406.891125,47.9289375 C402.70675,49.7576875 399.0005,52.2458125 395.87425,55.3239375 C392.737375,58.4133125 390.221125,62.1008125 388.3955,66.2839375 C386.561125,70.4826875 385.631125,75.0089375 385.631125,79.7351875 C385.631125,84.4645625 386.56175,88.9676875 388.397375,93.1195625 C390.22425,97.2570625 392.73675,100.940812 395.86675,104.068312 C398.998,107.200812 402.7105,109.692687 406.902375,111.475187 C411.101125,113.259562 415.669875,114.165187 420.481125,114.165187 C425.294875,114.165187 429.8655,113.248312 434.066125,111.439562 C438.2505,109.635812 441.982375,107.139562 445.15675,104.021437 L446.056125,103.138312 L436.282375,93.3458125 Z"
                                                      id="Fill-7" fill="#202121" sketch:type="MSShapeGroup"></path>
                                        </g>
                                </g>
                        </g>
                </svg>
        </div>

        <h1><?= esc_html( get_admin_page_title() ); ?></h1>

	<form method="post">
  		<input type="hidden" name="page" value="my_list_test" />
  		<?php $myListTable->search_box('search', 'search_id'); ?>
		<?php add_thickbox(); ?>
  		<?php $myListTable->display(); ?>
	</form>
</div>
