<html>                                                                  
	<head>                                                                  
		<script type="text/javascript" src="jquery.min.js"></script>          
		<script type="text/javascript">                                         
			$(document).ready(function() {
				$(".details").hide();
				$("a").live("click", function(){$("#div_"+this.id.substring(2)).toggle();});
			});
		</script>                                                               
	</head>                                                                 
	<body>                                                                  
<?php 
/**
* Tests for RimuHosting API
*/

include 'Rimuhosting.php'; 
ob_start();


/**
 * A debug output helper function, to be removed
 * @param mixed $mixed variable to print 
 */
function print_pre($mixed) { 
    echo '<pre>'; 
    print_r($mixed);
    echo '</pre>';
	ob_flush(); flush();
}

function open_div($title, $id) { 
	echo '<br/><a href="#" id="a_'.$id.'">', 
			$title, 
		'</a>', 
		'<div class="details" id="div_'.$id.'">';
}
function close_div() { 
	echo '</div>';
}

/**
* Run the following tests 
**/
$r = new RimuHostingConnection();
/**
* Get your api key from http://rimuhosting.com/cp/apikeys.jsp
**/
$r->set_api_key('addYourKeyHere!'); 

/*********************************
* Uncomment the following to test
**/

/**
* Testing get_distributions 
***************************/
open_div('Testing get_distributions', 'list_images');
$distros = $r->get_distributions();
print_pre($distros);
close_div();
/**/

/**
* Testing get_pricing_plans 
***************************/
open_div('Testing get_pricing_plans', 'list_sizes');
$filters = array('dc-location'=>'dclondon', 'price_under'=>30); // 
$pricing_plans = $r->get_pricing_plans(urlencode_array($filters));
print_pre($pricing_plans);
close_div();
/**/

/**
* Testing get_billing_methods 
*****************************/
open_div('Testing get_billing_methods', 'billing_methods');
$billing_methods = $r->get_billing_methods();
print_pre($billing_methods);
close_div();
/**/


/**
* Testing get_orders 
********************/
open_div('Testing get_orders', 'list_nodes');
$filters = array('include_inactive'=>'N',); // 
$orders = $r->get_orders(urlencode_array($filters));
print_pre($orders);
close_div();
/**/

/**
* Testing find_order 
********************/
open_div('Testing find_order', 'find_node');
$vps = $r->find_order('example.com', false);
print_pre($vps);
close_div();
/**/

/**
* Testing vps->get_info 
***********************/
open_div('Testing vps->get_info', 'node_info');
$info = $vps->get_info();
print_pre($info);
close_div();
/**/

/**
* Testing vps->get_more_info 
****************************/
open_div('Testing vps->get_more_info', 'more_info');
$more_info = $vps->get_more_info();
print_pre($more_info);
close_div();
/**/

/**
* Testing new_vps 
*****************/
open_div('Testing new_vps', 'create_node');
$vps = $r->new_vps('dummy-order.com', 
	'centos5.64', 'EU2B', 
	array('billing_oid'=>$billing_method->billing_oid, 'memory_mb'=>256)
	); 
print_pre($vps);
close_div();
/**/

/**
* Testing vps->restart 
**********************/
open_div('Testing vps->reboot', 'reboot_vps');
$reboot_info = $vps->reboot();
print_pre($reboot_info);
close_div();
/**/

/**
* Testing vps->power_cycle 
***************************/
open_div('Testing vps->power_cycle', 'powercycle');
$cycle_info = $vps->power_cycle();
print_pre($cycle_info);
close_div();
/**/

/**
* Testing vps->destroy 
**********************/
open_div('Testing vps->destroy', 'destroy');
$destroy_info = $vps->destroy();
print_pre($destroy_info);
close_div();
/**/
?>
	</body>                                                                 
</html>