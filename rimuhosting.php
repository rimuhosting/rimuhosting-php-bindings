<?php 
/**
 * RimuHosting API binding for PHP
 * 
 * For more information please check 
 * @link http://apidocs.rimuhosting.com/
 * @link http://rimuhosting.com/cp/apikeys.jsp
 * @link http://github.com/hadashi/libcloud/blob/master/libcloud/drivers/rimuhosting.py 
 *
 * @author Abdallah Deeb <abdallah@rimuhosting.com>
 * @version 0.6
 *
 * @package RimuHosting
 */

/**
 * A helper function to urlencode an array of parameters
 * @param array $params Array to be urlencoded
 * @return string
 */
function urlencode_array($params) {
    $querystring=null;
		if (is_array($params) and !empty($params)) { 
		foreach ($params as $name => $value) {
			$querystring=$name.'='.urlencode($value).'&'.$querystring;
		}
		// Cut the last '&'
		$querystring=substr($querystring,0,strlen($querystring)-1);
	} else { $querystring = $params; }
	return htmlentities($querystring);
}


/** 
 * The BillingMethod generic class 
 * 
 * @package RimuHosting
 */
class BillingMethod {
    /**
     * @param BillingMethod $object
     * @return BillingMethod
     */
    function __construct($object) {
		foreach (get_object_vars($object) as $k => $v) { $this->{$k} = $v; }
    }
}
/** 
 * The PricingPlan generic class 
 * 
 * @package RimuHosting
 */
class PricingPlan {
    /**
     * @param PricingPlan $object
     * @return PricingPlan
     */
    function __construct($object) {
		foreach (get_object_vars($object) as $k => $v) { $this->{$k} = $v; }
    }
}


/** 
 * The RimuHostingNode class 
 * @package RimuHosting
 */
class RimuHostingNode { 
	private $_api_handle;
	
	public $order_oid;
	public $domain_name;
	public $slug;
	public $billing_oid;
    public $is_on_customers_own_physical_server;  //
    public $host_server_oid;
	public $server_type;
	public $running_state;  //
    public $distro;  
    public $pings_ok;
    public $current_kernel;
    public $current_kernel_canonical;
    public $last_backup_message;
    public $is_console_login_enabled;
    public $console_public_authorized_keys;
    public $is_backup_running; //
    public $is_backups_enabled; 
    public $vps_uptime_s;  // 
    public $vps_cpu_time_s;  //
    public $is_suspended;  //
	
	/**
     * Constructor sets up {@link $api_handle, $order, $domain_name}
     */
	function __construct($api_handle, $order, $domain_name='') { 
		if (is_object($order)) { 
			foreach (get_object_vars($order) as $k => $v) { $this->{$k} = $v; }
		} else { 
			$this->_order_oid = $order;
			if (! empty($domain_name)) $this->_domain_name = $domain_name;
		}
		$this->_api_handle = $api_handle;
	}

	/**
	 * Get the VPS info
	 * GET /r/orders/order-{order-oid:[0-9]+}-{domain}
	 * returns the VPS object 
	 * @return RimuHostingNode this object with more information
	 */
	public function get_info() {
		if (empty($this->domain_name)) { 
			$url = $this->_api_handle->_base_url . '/r/orders/' . $this->slug;
			$response = $this->_api_handle->run_query($url);
			$this->_api_handle->response = $response->get_order_response;
			if ($response->get_order_response->response_type=='OK') { 
				foreach (get_object_vars($response->get_order_response->about_order) as $k => $v) {
					$this->{$k} = $v; 
				}
				
				return $this;
			} else {
				return NULL;
			}
		}
		$this->_api_handle->response = NULL;
		return $this;
	}
	
	public function __toString() {
		// TODO
	}
		
	/**
	 * Get more info about the VPS
	 * GET /r/orders/order-{order-oid:[0-9]+}-{domain}/vps/data-transfer-usage
	 * VPS data transfer usage.  (Also happens to include a few other VPS stats).  
		The data transfer usage currently takes a few seconds longer than the regular VPS status.  
		So we split this data out so you only take the response time hit when you actually need the information.
	 * @return RimuHostingNode the same object with more information
	 */
	public function get_more_info() {
		$url = $this->_api_handle->_base_url . '/r/orders/' . $this->slug . '/vps/data-transfer-usage';
		$response = $this->_api_handle->run_query($url);
		$this->_api_handle->response = $response->get_data_transfer_usage_and_other_vps_status_information;
		if ($response->get_data_transfer_usage_and_other_vps_status_information->response_type=='OK') { 
			foreach (get_object_vars($response->get_data_transfer_usage_and_other_vps_status_information->running_vps_info) as $k => $v) { $this->{$k} = $v; }
			return $this;
		} 
		return NULL;
	}
	
	/**
	 * PUT /r/orders/order-{order-oid:[0-9]+}-{domain}/vps/running-state
	 * Change the running state to reboot a VPS.  
	 *	For example, set it to 'RUNNING' if you want to start a stopped VPS (else does nothing).  
	 *	e.g. to 'RESTARTING' if you wish to issue a reboot.  
	 *	e.g. 'POWERCYCLING' to kill the VPS and restart it.  
	 *	Setting a 'NOTRUNNING' state is currently not supported.  
	 *	Since we are attempting to create a REST-ful API we are trying to 'verb' URIs like '/reboot'.
	 * @input string state 
	 * @return string server response 
	 */
	public function restart($state) {
		$url = $this->_api_handle->_base_url . '/r/orders/' . $this->slug . '/vps/running-state';
		$reboot_request = json_encode(array('reboot_request'=>array('running_state'=>$state)));
		set_time_limit(120);
		$response = $this->_api_handle->run_query($url, 'PUT', $reboot_request);
		$this->_api_handle->response = $response->put_running_state_response;
		if ($response->put_running_state_response->response_type=='OK') { 
			return $response->put_running_state_response->running_vps_info;
		}
		return NULL;
	}
	
	/**
	 * reboot VPS
	 * @return string reboot request answer
	 */
	public function reboot() {
		return $this->restart('RESTARTING');
	}
	/**
	 * power cycle VPS
	 * @return string reboot request answer
	 */
	public function power_cycle() {
		return $this->restart('POWERCYCLING');
	}
	/**
	 * Start shutdown VPS
	 * @return string startup request answer
	 */
	public function start() {
		return $this->restart('RUNNING');
	}
	
	/**
	 * Destroy VPS
	 * DELETE /r/orders/order-{order-oid:[0-9]+}-{domain}/vps
	 * @return string destroy request answer
	 */
	// 
	public function destroy() {
		$url = $this->_api_handle->_base_url . '/r/orders/' . $this->slug . '/vps';
        $result = $this->_api_handle->run_query($url, 'DELETE');
		$this->_api_handle->response = $response->delete_server_response;		
		if ($delete_server_response->response_type=='OK') { 
			return $result->delete_server_response->cancel_messages;
		} 
		return NULL;
	}

	/**
	 * PUT /r/orders/order-{order-oid:[0-9]+}-{domain}/vps/host-server
	/* input:
		<VPSMoveRequest>
			<host_server_oid>xsd:string</host_server_oid>
			<is_update_dns>xsd:boolean</is_update_dns>
			<move_reason>xsd:string</move_reason>
			<pricing_change_option>CHOOSE_SAME_RESOURCES | CHOOSE_SAME_PRICING | CHOOSE_BEST_OPTION</pricing_change_option>
		</VPSMoveRequest>
	 * @input string new_host_oid
	 * @input boolean update_dns default is false
	 * @input string reason reason for moving the server
	 * @input string pricing_change CHOOSE_SAME_RESOURCES | CHOOSE_SAME_PRICING | CHOOSE_BEST_OPTION
	 * @return string  VPSMoveRequest response
	 */
	public function move_vps($new_host_oid='', $update_dns=false, $reason='', $pricing_change='CHOOSE_BEST_OPTION') {
		$VPSMoveRequest = array(
			'host_server_oid'=>$new_host_oid,
			'is_update_dns'=>$update_dns,
			'move_reason'=>$reason,
			'pricing_change_option'=>$pricing_change
			);
		set_time_limit(0);
		$url = $this->_api_handle->_base_url . '/r/orders/' . $this->slug . '/vps/host-server';
        $this->_api_handle->run_query($url, 'PUT', $VPSMoveRequest);
		//$this->_api_handle->response = $response->delete_server_response;
		return; // TODO
	}
	
	/**
	 * Cancel server order
	 * PUT /r/orders/order-{order-oid:[0-9]+}-{domain}/cancelled
	 * @return string  cancel order response
	 */
	public function cancel_order() {
		$url = $this->_api_handle->_base_url . '/r/orders/' . $this->slug . '/cancelled';
        $this->_api_handle->run_query($url, 'PUT');
		return; // TODO
	}

	// PUT /r/orders/order-{order-oid:[0-9]+}-{domain}/vps/parameters
	// Change a VPS's resources like memory or disk size.  Will force a VPS restart.  And will update billing.
	/* input: 
		<RunningVPSData>
			<disk_space_2_mb>xsd:int</disk_space_2_mb>
			<disk_space_mb>xsd:int</disk_space_mb>
			<memory_mb>xsd:int</memory_mb>
		</RunningVPSData>
	*/	
	public function change_resources($options=array()) {
		$RunningVPSData = array();
		set_time_limit(300);
		if (! empty($options['disk_space_mb'])) $RunningVPSData['disk_space_mb'] = $options['disk_space_mb'];
		if (! empty($options['disk_space_2_mb'])) $RunningVPSData['disk_space_2_mb'] = $options['disk_space_2_mb'];
		if (! empty($options['memory_mb'])) $RunningVPSData['memory_mb'] = $options['memory_mb'];
		$url = $this->_api_handle->_base_url . '/r/orders/' . $this->slug . '/vps/host-server';
        $this->_api_handle->run_query($url, 'PUT', $RunningVPSData);
		return; // TODO
	}

}

/** 
 * The RimuHostingConnection class 
 * 
 * @package RimuHosting
 */
class RimuHostingConnection { 
    private $_apikey = '';
    public $_base_url = 'http://api.rimuhosting.com';
    
    function __construct($apikey='') {
        if (! empty($apikey)) { $this->_apikey = $apikey; }
        
        $this->curl = curl_init();
        curl_setopt($this->curl,CURLOPT_RETURNTRANSFER,true);
        curl_setopt($this->curl,CURLOPT_AUTOREFERER,true); // This make sure will follow redirects
        curl_setopt($this->curl,CURLOPT_FOLLOWLOCATION,true); // This too
        curl_setopt($this->curl,CURLOPT_HEADER,false); // set to true to get the headers
    }

    function run_query($url, $method='GET', $data=NULL) {
        curl_setopt($this->curl, CURLOPT_URL, $url);
        curl_setopt($this->curl, CURLOPT_HTTPHEADER, array(
            "Expect:",
            "Content-Type: application/json",
            "Accept: application/json",
            "Authorization: rimuhosting apikey=".$this->_apikey,
        ));
		switch($method) { 
			case 'POST':
				curl_setopt($this->curl, CURLINFO_HEADER_OUT, false);
				curl_setopt($this->curl, CURLOPT_POST, true);
				curl_setopt($this->curl, CURLOPT_POSTFIELDS, $data); 
				break;
			case 'PUT':
				curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'PUT');
				curl_setopt($this->curl, CURLOPT_POSTFIELDS, $data); 
				break;
			case 'DELETE':
				curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
				break;
		}
        $result = curl_exec($this->curl);
        $info = curl_getinfo($this->curl);
        
		// curl_close($this->curl); //  don't close; reuse
        return json_decode($result);
    }
    
	/**
	 * Set the API key 
	 * @link http://rimuhosting.com/cp/apikeys.jsp
	 * @param string key
	 * 
	 */
	public function set_api_key($key) { 
        $this->_apikey = $key;
    }
    
    /**
	 * A listing of pricing plans.  For VPSs you will need: /pricing-plans;server-type=VPS You can specify other filters  
     * @param string filters
     *       dc-location: dclondon; dcdallas; dcbrisbane
     *       sever-type: VPS; PHYSICAL
     *       data_transfer_at_least_gb : 0
     *       just_current : true
     *       just_plans : NULL
     *       mem_at_least : 0
     *       not_third_party : true
     *       price : false
     *       price_over : 0.0
     *       price_under : 0.0
     *       use_jboss : false
     *       use_liferay : false
     *       use_rails : false
     *       use_spamassassin : false
     *       use_tomcat : false
     * @return array empty array if nothing found or array of PricingPlan objects 
     */
    public function get_pricing_plans($filters='') { 
		if (is_array($filters)) $filters = urlencode_array($filters);
        $url = $this->_base_url . '/r/pricing-plans;'.$filters;
		$response = $this->run_query($url);
		$plans = array();
		$this->response = $response->get_pricing_plans_response;
		if ($response->get_pricing_plans_response->response_type=='OK') {
			foreach($response->get_pricing_plans_response->pricing_plan_infos as $plan) {
				$plans[$plan->pricing_plan_code] = new PricingPlan($plan);
			}
		}
		return $plans;
    }
    public function list_sizes($filters='') { return $this->get_pricing_plans($filters); }	
    
    /**
     * Gets a list of orders belonging to the user. The list can be filtered
     * @param string|array filters
     * 	 dc-location: dclondon; dcdallas; dcbrisbane
     *   sever-type: VPS; PHYSICAL
     * 	 include_inactive: Y | N
     * @return array empty array if nothing found or array of RimuHostingNode objects
     */
    public function get_orders($filters='') { 
		if (is_array($filters)) $filters = urlencode_array($filters);
        $url = $this->_base_url . '/r/orders;'.$filters;
		$orders = array();
        $response = $this->run_query($url);
		$this->response = $response->get_orders_response;
		if ($response->get_orders_response->response_type=='OK') { 
			foreach ($response->get_orders_response->about_orders as $order) {
				$orders[$order->order_oid] = new RimuHostingNode($this, $order, $order->domain_name);
			}
		}
		return $orders;
    }
	public function list_nodes($filters='') { return get_orders($filters); }
	
	/**
     * Find an order by domain name   
     * @param string domain_name
     * @param boolean exact	if not exact, domain_name will match partial names
     * @return NULL|RimuHostingNode returns null if nothing found, or a RimuHostingNode instance
     */
	public function find_order($domain_name, $exact=true) { 
        $list = $this->get_orders();
		if (!empty($list) and is_array($list)){
			foreach($list as $order){ 
				if ($exact) { 
					if ($order->domain_name==$domain_name) {
						$_order_obj = new RimuHostingNode($this, $order, $order->domain_name);
						return $_order_obj;
					}
				} else {
					if(stristr($order->domain_name, $domain_name)) {
						$_order_obj = new RimuHostingNode($this, $order, $order->domain_name);
						return $_order_obj;
					}
				}
			}
		}
		return NULL;
	} 
	
    /**
     * List available distributions  
     * @return array returns an array of {distro_code => description}
     */
    public function get_distributions() { 
        $url = $this->_base_url . '/r/distributions';
        $response = $this->run_query($url);
		$this->response = $response->get_distros_response;
		$distros = array();
		if ($response->get_distros_response->response_type=='OK') {
			foreach($response->get_distros_response->distro_infos as $distro) {
				$distros[$distro->distro_code] = $distro->distro_description;
			}
		}
		return $distros;
    }
    public function list_images() { return $this->get_distributions(); }
	
	
    /**
     * List the customer's billing methods
     * @return array returns an array of BillingMethod objects
     */
    public function get_billing_methods() { 
        $url = $this->_base_url . '/r/billing-methods';
        $response = $this->run_query($url);
		$this->response = $response->get_billing_methods_response;
		$billing_methods = array();
		if ($response->get_billing_methods_response->response_type=='OK') {
			foreach($response->get_billing_methods_response->billing_methods as $billing_method) {
				$billing_methods[$billing_method->billing_oid] = new BillingMethod($billing_method);
			}
		}
		return $billing_methods;
    }
	
	/**
     * Get billing method info
     * @return NULL|BillingMethod returns BillingMethod object or null if billing_oid not found
     */
    public function get_billing_method($billing_oid) { 
		$billing_methods = $this->get_billing_methods();
		if (!empty($billing_methods) and is_array($billing_methods)){ 
			if (array_key_exists($billing_oid, $billing_methods)) {
				return $billing_methods[$billing_oid];
			} else { 
				return NULL;
			}
		}
		return NULL;
	}
    
    /*
    / new_vps  
    /
    <NewVPSRequest>
        <billing_oid>xsd:long</billing_oid>
        <host_server_oid>xsd:string</host_server_oid>
        <instantiation_options>InstantiationData</instantiation_options>
        <ip_request>IPRequestData</ip_request>
        <pricing_plan_code>xsd:string</pricing_plan_code>
        <user_oid>xsd:long</user_oid>
        <vps_order_oid_to_clone>xsd:long</vps_order_oid_to_clone>
        <vps_parameters>RunningVPSData</vps_parameters>
    </NewVPSRequest>
    <InstantiationData>
        <control_panel>xsd:string</control_panel>
        <distro>xsd:string</distro>
        <domain_name>xsd:string</domain_name>
        <password>xsd:string</password>
    </InstantiationData>
    <RunningVPSData>
        <disk_space_2_mb>xsd:int</disk_space_2_mb>
        <disk_space_mb>xsd:int</disk_space_mb>
        <memory_mb>xsd:int</memory_mb>
    </RunningVPSData>


    */
    public function new_vps(
        $domain_name, 
        $distro, 
        $pricing_plan_code, 
		$options=array() 
		/* List of options:
			$control_panel='webmin', 
			$options['password'])='',
			$options['billing_oid'])='',
			$host_server_oid='',
			$num_ips=1, 
			$extra_ip_reason='',
			$user_oid=NULL,
			$vps_order_oid_to_clone=NULL,
			$memory_mb=NULL,
			$disk_space_mb=NULL,
			$disk_space_2_mb=NULL
		*/
    ) { 
        $request = array();
        
		$request['pricing_plan_code'] = $pricing_plan_code;
		if (! empty($options['billing_oid'])) 
			$request['billing_oid']=$options['billing_oid'];
		if (! empty($options['host_server_oid'])) 
			$request['host_server_oid']=$options['host_server_oid'];	
		if (! empty($options['user_oid'])) 
			$request['user_oid']=$options['user_oid'];	
		if (! empty($options['vps_order_oid_to_clone'])) 
			$request['vps_order_oid_to_clone']=$options['vps_order_oid_to_clone'];
			
		
        $InstantiationData = array(
            'domain_name'=>$domain_name,
            'distro'=>$distro,
        );
		if (! empty($options['control_panel'])) 
			$InstantiationData['control_panel']=$options['control_panel'];
        if (! empty($options['password'])) 
			$InstantiationData['password']=$options['password'];
        $request['instantiation_options'] = $InstantiationData;
        
        $RunningVPSData = array();
		if (! empty($options['memory_mb'])) $RunningVPSData['disk_space_2_mb']=$options['memory_mb'];
		if (! empty($options['disk_space_mb'])) $RunningVPSData['disk_space_2_mb']=$options['disk_space_mb'];
		if (! empty($options['disk_space_2_mb'])) $RunningVPSData['disk_space_2_mb']=$options['disk_space_2_mb'];
		if (! empty($RunningVPSData)) $request['vps_parameters']=$RunningVPSData;
		
        if ( !empty($options['num_ips']) and ($options['num_ips'] > 1) ){
            if (! empty($options['extra_ip_reason'])) {
				// return error here
				return json_encode(array(
					'response_type'=>'Error', 
					'error_info'=>'Need an reason for having an extra IP'));
            } else {
                $request['ip_request'] = array();
				$request['ip_request']['num_ips'] = $num_ips;
                $request['ip_request']['extra_ip_reason'] = $extra_ip_reason;
			}
		}

        $new_vps_data = json_encode(array('new-vps'=>$request));
        $url = $this->_base_url . '/r/orders/new-vps';

		set_time_limit(0); // set time limit to wait for function to return
        $response = $this->run_query($url, 'POST', $new_vps_data);
		$this->response = $response->post_new_vps_response;
		if ($response->post_new_vps_response->response_type=='OK') { 
			$_new_vps = new RimuHostingNode($this, $response->post_new_vps_response->about_order, $domain_name); 
			return $_new_vps;
		} else { 
			return NULL;
		}
    }
    
}

?>