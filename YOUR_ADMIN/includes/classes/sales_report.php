<?php
/**
 * SALES REPORT 3.1
 *
 * The class file acts as the engine in the sales report.  All the data displayed is gathered and
 * calculated in here. The logic tree provides a brief summary of the main functions at work every
 * time a report is generated. 
 *
 * @author     Frank Koehl (PM: BlindSide)
 * @author     Conor Kerr <conor.kerr_zen-cart@dev.ceon.net>
 * @author     Carl Peach <carlvt88 at zen-cart.com/forum>
 * @updated by stellarweb to work with version 1.5.0 02-29-12 
 * @copyright  Portions Copyright 2003-2006 Zen Cart Development Team
 * @copyright  Portions Copyright 2003 osCommerce
 * @license    http://www.gnu.org/copyleft/gpl.html   GNU Public License V2.0
 *  
 * update:  Josef Zahradník
 * web:     www.magic-shop.cz   
 */


/*
** Logic Tree of class sales_report functions
  sales_report - establishes base class variables, initializes loop for timeframes
   |
   |_build_timeframe - initial oID query for given timeframe
       |_build_li_totals - basic totals for each timeframe
           |_build_li_orders - line item for each order in the timeframe
           |_build_li_products - line item for each product in the timeframe
       |_build_matrix - calculate detailed stats for each timeframe; non-linear display;
                        uses data from build_li_orders and build_li_products
*/

  //_TODO modularize time format code, allowing for other formats

class sales_report 
{
    var $timeframe_group, $sd, $ed, $sd_raw, $ed_raw, $date_target, $date_status;
    var $payment_method, $payment_method_omit, $current_status, $manufacturer, $detail_level, $output_format;
    var $timeframe, $timeframe_id, $current_date, $product_filter;

    function __construct($parms) 
    {
        global $db;

        // place passed variables into class variables
        $this->timeframe_group = $parms['timeframe'];
        $this->date_target = $parms['date_target'];
        $this->date_status = $parms['date_status'];
        $this->payment_method = $parms['payment_method'];
        $this->payment_method_omit = $parms['payment_method_omit'];
        $this->current_status = $parms['current_status'];
        $this->manufacturer = $parms['manufacturer'];
        $this->detail_level = $parms['detail_level'];
        $this->output_format = $parms['output_format'];
        $this->order_total_validation = $parms['order_total_validation'];
        
        $this->customer_filter = '';
        $this->product_filter = '';

        // all our calculations are done using a "raw" timestamp format, which are
        // pulled from entered date strings using the substr function (similar to zen_date_raw)
        $sd = $parms['start_date'];
        $ed = $parms['end_date'];
        if (strtolower(DATE_FORMAT) == 'm/d/y') {
            // Use US date format (m/d/Y)
            $this->sd_raw = mktime(0, 0, 0, substr($sd, 0, 2), substr($sd, 3, 2), substr($sd, 6, 4) );
            $this->ed_raw = mktime(0, 0, 0, substr($ed, 0, 2), substr($ed, 3, 2), substr($ed, 6, 4) );
        } elseif (strtolower(DATE_FORMAT) == 'd/m/y') {
            // Use UK date format (d/m/Y)
            $this->sd_raw = mktime(0, 0, 0, substr($sd, 3, 2), substr($sd, 0, 2), substr($sd, 6, 4) );
            $this->ed_raw = mktime(0, 0, 0, substr($ed, 3, 2), substr($ed, 0, 2), substr($ed, 6, 4) );
        } elseif (strtolower(DATE_FORMAT) == 'd.m.y') {
            // Use CZ, SK date format (d/m/Y)
            $this->sd_raw = mktime(0, 0, 0, substr($sd, 3, 2), substr($sd, 0, 2), substr($sd, 6, 4) );
            $this->ed_raw = mktime(0, 0, 0, substr($ed, 3, 2), substr($ed, 0, 2), substr($ed, 6, 4) );
        }

        // run a few checks on the dates
        // avoid dates before the first order
        $first = $db->Execute("SELECT MIN(date_purchased) AS date FROM " . TABLE_ORDERS);
        $this->global_sd = strtotime(substr($first->fields['date'], 0, 10));
        if ($this->sd_raw < $this->global_sd) {
            $this->sd_raw = $this->global_sd;
        }
        if ($this->ed_raw < $this->global_sd) {
            $this->ed_raw = $this->global_sd;
        }

        // avoid days in the future
        $now = strtotime('today midnight');
        if ($this->sd_raw > $now) {
            $this->sd_raw = $now;
        }
        if ($this->ed_raw > $now) {
            $this->ed_raw = $now;
        }

        // now that the date checks are out of the way, let's begin
        $this->sd = date(DATE_FORMAT_SPIFFYCAL, $this->sd_raw);
        $this->ed = date(DATE_FORMAT_SPIFFYCAL, $this->ed_raw);
        $this->current_date = $this->sd_raw;

        $this->timeframe_id = 0;
        $this->timeframe = array();
        $this->grand_total = $this->initializeTotals();

        while ($this->current_date <= $this->ed_raw) {
            $this->build_timeframe();
        }

        // build matrix data if requested
        // By placing it here and adding 'matrix' to the 'if' statements
        // for building order and product line items, we have all
        // the possible data at our disposal
        if ($this->detail_level == 'matrix') {
            $this->build_matrix();
        }
    }  // END class constructor


    //////////////////////////////////////////////////////////
    // Each time this function runs, another timeframe array
    // is built.  The variable $this->current_date acts as the
    // key, used to determine the start and end dates of this
    // particular timeframe.  All other functions are called
    // from within here to build all the requested timeframe
    // information (order line items, product line items, or
    // data matrix).
    //
    function build_timeframe() 
    {
        global $db;
        $id = $this->timeframe_id;  // we use $id to keep arrays short, easier to read

        // $sd and $ed are local to this function, not to be confused with
        // $this->start_date and $this->end_date, entered by the user
        $sd = $this->current_date;

        switch ($this->timeframe_group) {
            case 'year':
                $ed = mktime(0, 0, 0, date("m", $sd), date("d", $sd), date("Y", $sd) + 1);
                break;
            case 'month':
                $ed = mktime(0, 0, 0, date("m", $sd) + 1, 1, date("Y", $sd));
                break;
            case 'week':
                $ed = mktime(0, 0, 0, date("m", $sd), date("d", $sd) + 7, date("Y", $sd));
                break;
            case 'day':
                $ed = mktime(0, 0, 0, date("m", $sd), date("d", $sd) + 1, date("Y", $sd));
                break;
        }

        // dial back $ed if it's beyond the user-specified end date
        // we go 1 day beyond specified end date because end date is exclusive in the query
        if ($ed > $this->ed_raw) {
            $ed = mktime(0, 0, 0, date("m", $this->ed_raw), date("d", $this->ed_raw) + 1, date("Y", $this->ed_raw));
        }

        // define the timeframe array
        $this->timeframe[$id] = array();

        // store the start date and end date for this timeframe
        // timestamp format allows us to use whatever display format we want at output
        // we subtract 1 day so that the displayed end date is the actual end date
        $this->timeframe[$id]['sd'] = $sd;
        $this->timeframe[$id]['ed'] = mktime(0, 0, 0, date("m", $ed), date("d", $ed) - 1, date("Y", $ed));

        // build the excluded products array - not really debugged well
        $this->product_filter = '';
        $exclude_products = unserialize(EXCLUDE_PRODUCTS);
        if (is_array($exclude_products) && count($exclude_products) > 0) {
            foreach($exclude_products as $pID) {
                $this->product_filter .= " and op.products_id != " . (int)$pID;
            }
        }

        //need to add some error checking here - assumes list of valid numbers
        $include_products = explode(',', $_GET['prod_includes']);
        if (isset($_GET['doProdInc']) && is_array($include_products) && count($include_products) > 0) {
            for ($i = 0, $n = count($include_products); $i < $n; $i++){
                $include_products[$i] = (int)trim($include_products[$i]);
            }
            $include_products = array_values(array_unique($include_products));
            $this->product_filter .= (" AND op.products_id IN (" . implode(',', $include_products) . ")");
        }
        
        $include_customers = explode(',', $_GET['cust_includes']);
        if (isset($_GET['doCustInc']) && is_array($include_customers) && count($include_customers) > 0) {
            for ($i = 0, $n = count($include_customers); $i < $n; $i++){
                $include_customers[$i] = (int)trim($include_customers[$i]);
            }
            $include_customers = array_values(array_unique($include_customers));
            $this->customer_filter .= (" AND o.customers_id IN (" . implode(',', $include_customers) . ")");
        }

        // build the SQL query of order numbers within the current timeframe
        $sql = "SELECT DISTINCT o.orders_id from " . TABLE_ORDERS . " o \n";
        
        if ($this->manufacturer != 0 || (isset($_GET['doProdInc']) && is_array($include_products) && count($include_products) > 0)) {
            $sql .= "LEFT JOIN " . TABLE_ORDERS_PRODUCTS . " op ON o.orders_id = op.orders_id \n";
        }
        
        if ($this->manufacturer != 0) {
            $sql .= ("LEFT JOIN " . TABLE_PRODUCTS . " p ON p.products_id = op.products_id" . PHP_EOL);
        }
        
//      if ($_GET['doCustInc']== 'on' && is_array($include_customers) && sizeof($include_customers) > 0) {
//        $sql .= "LEFT JOIN " . TABLE_ORDERS_PRODUCTS . " op ON o.orders_id = op.orders_id \n";
//      }
        if ($this->date_target == 'status') {
            $sql .= "LEFT JOIN " . TABLE_ORDERS_STATUS_HISTORY . " osh ON o.orders_id = osh.orders_id \n";
            $sql .= "WHERE osh.date_added >= '" . date("Y-m-d H:i:s", $sd) . "' AND osh.date_added < '" . date("Y-m-d H:i:s", $ed) . "' \n";
            $sql .= "AND osh.orders_status_id = '" . $this->date_status . "' \n";
        } else {
            $sql .= "WHERE o.date_purchased >= '" . date("Y-m-d H:i:s", $sd) . "' AND o.date_purchased < '" . date("Y-m-d H:i:s", $ed) . "' \n";
        }
        
        if ($this->manufacturer != 0) {
            $sql .= "AND p.manufacturers_id = " . $this->manufacturer . PHP_EOL;
        }
        
        if ($this->payment_method) {
            $sql .= "AND o.payment_module_code LIKE '" . $this->payment_method . "' \n";
        }
        if ($this->payment_method_omit) {
            $sql .= "AND o.payment_module_code NOT LIKE '" . $this->payment_method_omit . "' \n";
        }
        if ($this->current_status) {
            $sql .= "AND o.orders_status = '" . $this->current_status . "' \n";
        }
        if ($this->product_filter != '') {
            $sql .= $this->product_filter . " \n";
        }
        if ($this->customer_filter != '') {
            $sql .= $this->customer_filter . " \n";
        }
        $sql .= " ORDER BY o.orders_id DESC";

        // DEBUG
        //$this->sql[$id] = $sql;
        
        // loop through query and build the arrays for this timeframe
        $sales = $db->Execute($sql);
        $grand_total = 0;
        
        // make sure we actually have orders to process
        if ($sales->RecordCount() > 0) {
            // initialize the various timeframe arrays
            // by creating them inside the RecordCount() check, we can easily
            // check for an empty timeframe with is_array() in the report
            $totals = $this->initializeTotals();
            $totals['diff_products'] = array();
            $this->timeframe[$id]['total'] = $totals;
            if ($this->detail_level == 'order') {
                $this->timeframe[$id]['orders'] = array();
            } elseif ($this->detail_level == 'product') {
                $this->timeframe[$id]['products'] = array();
            }
            while (!$sales->EOF) {
                $oID = $sales->fields['orders_id'];
                $grand_total += $this->build_li_totals($oID);
                if (count($this->timeframe[$id]['orders']) == 0) {
                    $this->timeframe[$id]['orders'] = false;
                }
                if (count($this->timeframe[$id]['products']) == 0) {
                    $this->timeframe[$id]['products'] = false;
                }
                $sales->MoveNext();
            }
            // calculate the total for the timeframe
            $this->timeframe[$id]['total']['grand'] = $grand_total;
            //_MATHCHECK compare this figure to total of individual orders/products
            // add values to the grand total line at the bottom of the report
            foreach (array_keys($this->grand_total) as $key) {
                $this->grand_total[$key] += $this->timeframe[$id]['total'][$key];
            }
        }
        // Since $sd is inclusive, but $ed is exclusive in our query, we need
        // only set next starting point to the current $ed
        $this->current_date = $ed;
        // increment the id number
        $this->timeframe_id++;
    }  // END function build_timeframe()
    
    protected function initializeTotals()
    {
        return array(
            'goods' => 0,
            'num_orders' => 0,
            'num_products' => 0,
            'shipping' => 0,
            'goods_tax' => 0,
            'order_recorded_tax' => 0,
            'discount' => 0,
            'discount_qty' => 0,
            'gc_sold' => 0,
            'gc_sold_qty' => 0,
            'gc_used' => 0,
            'gc_used_qty' => 0,
            'grand' => 0
        );
    }

    //////////////////////////////////////////////////////////
    // build_li_totals() actually does the tallying for each
    // order found within the timeframe set and searched in
    // build_timeframe().  It calls build_li_orders() and
    // build_li_products() as needed.
    //
    function build_li_totals($oID) 
    {
        global $db, $currencies;
        $id = $this->timeframe_id;
        $oID = (int)$oID;

        $order_info = $db->Execute(
            "SELECT o.currency, o.currency_value, o.order_total
               FROM " . TABLE_ORDERS . " o
              WHERE o.orders_id = $oID
              LIMIT 1"
        );

        // if we have to filter on manufacturer, the SQL is totally different
        if ($this->manufacturer != 0) {
            $products_sql = 
                "SELECT op.* 
                   FROM " . TABLE_ORDERS_PRODUCTS . " op
                        INNER JOIN " . TABLE_PRODUCTS . " p
                            ON p.products_id = op.products_id
                  WHERE p.manufacturers_id = " . $this->manufacturer . "
                    AND op.orders_id = $oID" . $this->product_filter;
        } else {
            $products_sql = 
                "SELECT op.* 
                   FROM " . TABLE_ORDERS_PRODUCTS . " op
                  WHERE op.orders_id = $oID" . $this->product_filter;
        }
        $products = $db->Execute($products_sql);

        // these "order_" variables are local to the build_li_totals() function.  They
        // are used to determine order total, timeframe grand total, and order count
        $order_goods = 0;
        $order_goods_tax = 0;
        $order_recorded_tax = 0;
        $order_shipping = 0;
        $order_discount = 0;
        $order_gc_sold = 0;
        $order_gc_used = 0;

        while (!$products->EOF) {
            // assign key values to shorter variables for clarity
            $pID = $products->fields['products_id'];
            $final_price = $products->fields['final_price'];
            $quantity = $products->fields['products_quantity'];
            $tax = $products->fields['products_tax'];
            $onetime_charges = $products->fields['onetime_charges'];
            $model = zen_db_output($products->fields['products_model']);

            // do the math

            // gift certificates aren't products, so we must separate those out
            if (substr($model, 0, 4) == 'GIFT') {
                $order_gc_sold += ($final_price * $quantity);
                $this->timeframe[$id]['total']['gc_sold'] += ($final_price * $quantity);
                $this->build_li_orders($oID, 'gc_sold', $final_price * $quantity);

                $this->timeframe[$id]['total']['gc_sold_qty'] += $quantity;
                $this->build_li_orders($oID, 'gc_sold_qty', $quantity);

                $order_goods += $onetime_charges;
                $this->timeframe[$id]['total']['goods'] += $onetime_charges;
                $this->build_li_orders($oID, 'goods', $onetime_charges);
            } else {
                // Round up the final product price in same manner as order class - otherwise the amounts
                // from this report will most likely not agree with the actual final order values!
                $product_price = zen_round(( ($final_price * $quantity) + $onetime_charges ), $currencies->currencies[$order_info->fields['currency']]['decimal_places']);
              
                // Get the amount of tax for this product
                $product_tax = zen_calculate_tax($onetime_charges, $tax);
                $product_tax += zen_calculate_tax($final_price * $quantity, $tax);
              
                $order_goods_tax += $product_tax;
              
                // Calculate the subtotal inc tax in the same way that the order class does
                $product_price_inc_tax = (zen_add_tax($final_price, $tax) * $quantity) + zen_add_tax($onetime_charges, $tax);
                $product_price_exc_tax = ($product_price_inc_tax - $product_tax);
                $order_goods += $product_price_exc_tax;
              
                $this->timeframe[$id]['total']['goods'] += $product_price_exc_tax;
                $this->build_li_orders($oID, 'goods', $product_price_exc_tax);
          
                $this->timeframe[$id]['total']['goods_tax'] += $product_tax;
                $this->build_li_orders($oID, 'goods_tax', $product_tax );
          
                $this->timeframe[$id]['total']['num_products'] += $quantity;
                $this->build_li_orders($oID, 'num_products', $quantity);
            }

            // check to see if product is unique in this timeframe
            // add to 'diff_products' array if so
            if (!in_array($pID, $this->timeframe[$id]['total']['diff_products'])) {
                $this->timeframe[$id]['total']['diff_products'][] = $pID;
            }

            if (!in_array($pID, $this->timeframe[$id]['orders'][$oID]['diff_products'])) {
                $this->timeframe[$id]['orders'][$oID]['diff_products'][] = $pID;
            }

            // build product line items (if requested)
            if ($this->detail_level == 'product' || $this->detail_level == 'matrix') {
                // build array of product info so the function already has what it needs, avoiding another query
                $product_tax = zen_calculate_tax($onetime_charges, $tax) + zen_calculate_tax($final_price * $quantity, $tax);
                // get product's attributes to display under product name
                $products_name_with_attributes = $products->fields['products_name'] . '<br>';
                $products_attributes = array();
                $products_attributes_display = '';
                $attributes_select = $db->Execute(
                    "SELECT products_options_id, products_options_values_id, products_options, products_options_values, options_values_price, price_prefix, product_attribute_is_free
                       FROM " . TABLE_ORDERS_PRODUCTS_ATTRIBUTES . "
                      WHERE orders_id = " . (int)$oID . "
                        AND orders_products_id = " . (int)$products->fields['orders_products_id']
                );
                while( !$attributes_select->EOF ){
                    //$products_name_with_attributes .= '<small> - ' . $attributes_select->fields['products_options'] . ': ' . $attributes_select->fields['products_options_values'] . '</small><br>';
                    $products_attributes_display .= '<small> - ' . $attributes_select->fields['products_options'] . ': ' . $attributes_select->fields['products_options_values'] . '</small><br>';
                    $products_attributes[$attributes_select->fields['products_options_id']] = $attributes_select->fields['products_options_values_id'];
                    $attributes_select->MoveNext();
                }
                // unique id for product with attributes
                $uprid = zen_get_uprid($pID, $products_attributes);

                $this_product = array(
                    'id' => $pID,
                    'uprid' => $uprid,
                    'name' => $products_name_with_attributes,
                    'attributes' => $products_attributes_display,
                    'model' => $model,
                    'base_price' => $products->fields['products_price'],
                    'final_price' => $final_price,
                    'quantity' => $quantity,
                    'tax' => $product_tax,
                    'onetime_charges' => $onetime_charges,
                    'total' => ($final_price * $quantity) + $onetime_charges
                );
                $this->build_li_products($this_product);
            }
            $products->MoveNext();
        }

        // pull shipping, discounts, tax, and gift certificates used from orders_total table
        $totals = $db->Execute("select * from " . TABLE_ORDERS_TOTAL . " where orders_id = '" . $oID . "'");
        while (!$totals->EOF) {
            $class = $totals->fields['class'];
            $value = $totals->fields['value'];
            if ($class != "ot_total" && $class != "ot_subtotal") {
                if ($class == "ot_gv") {
                    $order_gc_used += $value;
                    $this->timeframe[$id]['total']['gc_used'] += $value;
                    $this->build_li_orders($oID, 'gc_used', $value);

                    $this->timeframe[$id]['total']['gc_used_qty']++;
                    $this->build_li_orders($oID, 'gc_used_qty', 1);
                } elseif ($class == "ot_coupon" || $class == "ot_group_pricing" || $class == "ot_better_together") {  
                    $order_discount += $value;
                    $this->timeframe[$id]['total']['discount'] += $value;
                    $this->build_li_orders($oID, 'discount', $value);

                    $this->timeframe[$id]['total']['discount_qty']++;
                    $this->build_li_orders($oID, 'discount_qty', 1);
                } elseif ($class == "ot_cashback") {
                    $order_discount += $value;
                    $this->timeframe[$id]['total']['discount'] += $value;
                    $this->build_li_orders($oID, 'discount', $value);

                    $this->timeframe[$id]['total']['discount_qty']++;
                    $this->build_li_orders($oID, 'discount_qty', 1);
                } elseif ($class == "ot_tax") {
                    $order_recorded_tax += $value;
                    $this->timeframe[$id]['total']['order_recorded_tax'] += $value;
                    $this->build_li_orders($oID, 'order_recorded_tax', $value);
                } elseif ($class == "ot_shipping") {
                    $order_shipping += $value;
                    $this->timeframe[$id]['total']['shipping'] += $value;
                    $this->build_li_orders($oID, 'shipping', $value);
                } elseif ($value < 0) {
                    // this allows for a custom discount, a la Super Orders
                    $order_discount += abs($value);
                    $this->timeframe[$id]['total']['discount'] += abs($value);
                    $this->build_li_orders($oID, 'discount', abs($value) );

                    $this->timeframe[$id]['total']['discount_qty']++;
                    $this->build_li_orders($oID, 'discount_qty', 1);
                }
            }
            $totals->MoveNext();
        }

        // we want to count an order if it has a value in any category
        $order_values = ($order_goods + $order_goods_tax + $order_shipping + $order_gc_sold + $order_discount + $order_gc_used);
        if ($order_values != 0) {
            $this->timeframe[$id]['total']['num_orders']++;
            $this->build_li_orders($oID, 'has_no_value', false);

            // add up stored values for order grand total
            // (goods + tax + shipping + gc_sold) - (discount + gc_used)
            $order_total = ($order_goods + $order_goods_tax + $order_shipping + $order_gc_sold) - ($order_discount + $order_gc_used);

            if ($this->detail_level == 'order' || $this->detail_level == 'matrix') {
                $this->build_li_orders($oID, 'grand', $order_total);
          
                // Build order total verification column if requested
                if ($this->order_total_validation) {
                    // Get the recorded order total
                    $recorded_order_total = $order_info->fields['order_total'];
            
                    if (zen_round($order_total, 2) != $recorded_order_total) {
                        $order_total_validation = "DON'T MATCH!<br />$order_total : $recorded_order_total";
                    } else {
                        $order_total_validation = 'VALID';
                    }
                    $this->build_li_orders($oID, 'order_total_validation', $order_total_validation);
                } 
            }
            return $order_total;
        } else {
            $this->build_li_orders($oID, 'has_no_value', true);
            return 0;
        }

    }  // END function build_li_totals($oID)


    //////////////////////////////////////////////////////////
    // build_li_orders() is called each time a value is added
    // to the 'total' array.  If the customer wishes to
    // display order line items, the value is added to the
    // corresponding 'orders' array.
    //
    function build_li_orders($oID, $field, $value) 
    {
        $id = $this->timeframe_id;
        // first check to see if we even need to do anything
        if ($this->detail_level == 'order' || $this->detail_level == 'matrix') {
            // create the array if it doesn't already exist
            if (!isset($this->timeframe[$id]['orders'][$oID]) ) {
                $this->timeframe[$id]['orders'][$oID] = array(
                    'oID' => $oID,
                    // the $oID key will be reset when we sort the array at
                    // display, so we store it as a part of the array as well
                    'goods' => 0,
                    'num_products' => 0,
                    'diff_products' => array(),
                    'shipping' => 0,
                    'goods_tax' => 0,
                    'order_recorded_tax' => 0,
                    'discount' => 0,
                    'discount_qty' => 0,
                    'gc_sold' => 0,
                    'gc_sold_qty' => 0,
                    'gc_used' => 0,
                    'gc_used_qty' => 0,
                    'grand' => 0,
                    'order_total_validation' => '',
                    'has_no_value' => 0
                );

                // get the customer data
                $c_data = $GLOBALS['db']->Execute(
                    "SELECT c.* 
                       FROM " . TABLE_ORDERS . " o
                            INNER JOIN " . TABLE_CUSTOMERS . " c
                                ON o.customers_id = c.customers_id
                      WHERE o.orders_id = $oID
                      LIMIT 1"
                );
                $this->timeframe[$id]['orders'][$oID]['customers_id'] = $c_data->fields['customers_id'];
                $this->timeframe[$id]['orders'][$oID]['first_name'] = zen_db_output($c_data->fields['customers_firstname']);
                $this->timeframe[$id]['orders'][$oID]['last_name'] = zen_db_output($c_data->fields['customers_lastname']);
            }

            // add the passed $value to the passed $field in the ['orders'] array
            if ($field != 'order_total_validation') {
                $this->timeframe[$id]['orders'][$oID][$field] += $value;
            } else {
                $this->timeframe[$id]['orders'][$oID][$field] = $value;
            }
        }
    }

    //////////////////////////////////////////////////////////
    // Since product line items don't need to look at the
    // orders_total table, we can just call build_li_products
    // once and build/increment the product array per product
    // (i.e. products are already line items, orders are not).
    //
    function build_li_products($product) 
    {
        $id = $this->timeframe_id;
        $pID = $product['id'];

        // initialize the array for this products_id if it doesn't exist yet
        if (!isset($this->timeframe[$id]['products'][$pID]) ) {
            $this->timeframe[$id]['products'][$pID] = array(
                'pID' => $product['id'],
                'name' => $product['name'],
                'attributes' => $product['attributes'],
                'model' => $product['model'],
                'manufacturer' => '',
                'base_price' => $product['base_price'],
                'final_price' => $product['final_price'],
                'quantity' => $product['quantity'],
                'onetime_charges' => $product['onetime_charges'],
                'total' => $product['total'], // 'total' = ( ($final_price * $quantity) + $onetime_charges ) )
                'tax' => $product['tax'],
                'grand' => $product['total'] + $product['tax']
            );

            // get the manufacturers_id from `products` table
            if (DISPLAY_MANUFACTURER) {
                $manufacturer_name = zen_get_products_manufacturers_name($pID);
                if ($manufacturer_name == '') {
                    $manufacturer_name = TEXT_NONE;
                }
                $this->timeframe[$id]['products'][$pID]['manufacturer'] = $manufacturer_name;
            }
        } else {

            // or add the values of ordered product to existing 'products' array
            // note that the informational fields are only defined once (i.e. the SQL sort order matters!)
            $this->timeframe[$id]['products'][$pID]['quantity'] += $product['quantity'];
            $this->timeframe[$id]['products'][$pID]['onetime_charges'] += $product['onetime_charges'];
            $this->timeframe[$id]['products'][$pID]['total'] += $product['total'];
            $this->timeframe[$id]['products'][$pID]['tax'] += $product['tax'];
            $this->timeframe[$id]['products'][$pID]['grand'] += $product['total'] + $product['tax'];
        }

    }  // END function build_li_products($product)

    //////////////////////////////////////////////////////////
    // Building the data matrix requires data from both the
    // order and product level, so we build both arrays when
    // creating a data matrix.  This saves us from having to
    // run several queries and makes the adding the matrix
    // report a snap, since we can just tack it on after
    // building all the data arrays!
    //
    function build_matrix() 
    {
        for ($i = 0, $n = count($this->timeframe); $i < $n; $i++) {
            // skip the current timeframe if there isn't any data
            if (!is_array($this->timeframe[$i]['orders']) || !is_array($this->timeframe[$i]['products'])) {
                continue;
            }

            $this->timeframe[$i]['matrix'] = array(
                'diff_customers' => array(),
                'payment_methods' => array(),
                'shipping_methods' => array(),
                'credit_cards' => array(),
                'currencies' => array(),
                'biggest_per_revenue' => 0,
                'biggest_per_products' => 0,
                'smallest_per_revenue' => 0,
                'smallest_per_products' => 0,
                'avg_order_value' => 0,
                'avg_products_per_order' => 0,
                'avg_diff_products_per_order' => 0,
                'avg_orders_per_customer' => 0,
                'product_spread' => array(),
                'product_revenue_ratio' => array(),
                'product_quantity_ratio' => array() 
            );

            // gather statistics from orders array
            foreach ($this->timeframe[$i]['orders'] as $oID => $o_data) {
                $order = $GLOBALS['db']->Execute(
                    "SELECT * 
                       FROM " . TABLE_ORDERS . " 
                      WHERE orders_id = $oID 
                      LIMIT 1"
                );

                // place pertient data in short variables
                $cc_type = $order->fields['cc_type'];
                $payment_method = $order->fields['payment_method'];
                $payment_module_code = $order->fields['payment_module_code'];
                $shipping_method = $order->fields['shipping_method'];
                $shipping_module_code = $order->fields['shipping_module_code'];
                $currency = $order->fields['currency'];

                // Format shipping method to remove the data in parentheses
                $shipping_method = explode(' (', $shipping_method, 2);
                $shipping_method = rtrim($shipping_method[0], ':');

                // Number of unique customers
                $cID = $o_data['customers_id'];
                $new_customer = true;
                foreach ($this->timeframe[$i]['matrix']['diff_customers'] as $this_cID => $c_data) {
                    $c_data =& $this->timeframe[$i]['matrix']['diff_customers'][$this_cID];
                    if ($cID == $this_cID) {
                        $c_data['num_orders']++;
                        $new_customer = false;
                        break;
                    }
                    unset($c_data);
                }
                if ($new_customer) {
                    $this->timeframe[$i]['matrix']['diff_customers'][$cID] = array(
                        'first_name' => $o_data['first_name'],
                        'last_name' => $o_data['last_name'],
                        'num_orders' => 1
                    );
                }

                // Payment methods used, with count
                $new_payment_method = true;
                foreach ($this->timeframe[$i]['matrix']['payment_methods'] as $key => $value) {
                    $value =& $this->timeframe[$i]['matrix']['payment_methods'][$key];
                    if ($value['module_code'] == $payment_module_code) {
                        $value['count']++;
                        $new_payment_method = false;
                        unset($value);
                        break;
                    }
                    unset($value);
                }
                if ($new_payment_method) {
                    $this->timeframe[$i]['matrix']['payment_methods'][] = array(
                        'method' => $payment_method,
                        'module_code' => $payment_module_code,
                        'count' => 1
                    );
                }

                // Shipping methods used, with count
                $new_shipping_method = true;
                foreach ($this->timeframe[$i]['matrix']['shipping_methods'] as $key => $value) {
                    $value =& $this->timeframe[$i]['matrix']['shipping_methods'][$key];
                    if ($value['module_code'] == $shipping_module_code) {
                        $value['count']++;
                        $new_shipping_method = false;
                        unset($value);
                        break;
                    }
                    unset($value);
                }
                if ($new_shipping_method) {
                    $this->timeframe[$i]['matrix']['shipping_methods'][] = array(
                        'method' => $shipping_method,
                        'module_code' => $shipping_module_code,
                        'count' => 1
                    );
                }

                // Credit cards used, with count
                $new_credit_card = true;
                foreach ($this->timeframe[$i]['matrix']['credit_cards'] as $key => $value) {
                    $value =& $this->timeframe[$i]['matrix']['credit_cards'][$key];
                    if ($value['type'] == $cc_type) {
                        $value['count']++;
                        $new_credit_card = false;
                        unset($value);
                        break;
                    }
                    unset($value);
                }
                if ($new_credit_card && $cc_type != '') {
                    $this->timeframe[$i]['matrix']['credit_cards'][] = array(
                        'type' => $cc_type,
                         'count' => 1
                     );
                }

                // Currencies used, with count
                // eliminate display on report with "if (sizeof($timeframe['matrix']['currencies']) > 1)"
                $new_currency = true;
                foreach ($this->timeframe[$i]['matrix']['currencies'] as $key => $value) {
                    $value =& $this->timeframe[$i]['matrix']['currencies'][$key];
                    if ($value['type'] == $currency) {
                        $value['count']++;
                        $new_currency = false;
                        unset($value);
                        break;
                    }
                    unset($value);
                }
                if ($new_currency) {
                    $this->timeframe[$i]['matrix']['currencies'][] = array(
                        'type' => $currency,
                        'count' => 1
                    );
                }

                // Biggest order by revenue (display order # and customer name)
                if (empty($this->timeframe[$i]['matrix']['biggest_per_revenue'])) {
                    $this->timeframe[$i]['matrix']['biggest_per_revenue'] = $oID;
                } else {
                    $current_leader = $this->timeframe[$i]['orders'][$this->timeframe[$i]['matrix']['biggest_per_revenue']];
                    if ($o_data['goods'] > $current_leader['goods']) {
                        $this->timeframe[$i]['matrix']['biggest_per_revenue'] = $oID;
                    }
                }

                // Smallest order by revenue (display order # and customer name)
                if (empty($this->timeframe[$i]['matrix']['smallest_per_revenue'])) {
                    $this->timeframe[$i]['matrix']['smallest_per_revenue'] = $oID;
                } else {
                    $current_leader = $this->timeframe[$i]['orders'][$this->timeframe[$i]['matrix']['smallest_per_revenue']];
                    if ($o_data['goods'] < $current_leader['goods']) {
                        $this->timeframe[$i]['matrix']['smallest_per_revenue'] = $oID;
                    }
                }

                // Biggest order by product count (display order # and customer name)
                if (empty($this->timeframe[$i]['matrix']['biggest_per_product'])) {
                    $this->timeframe[$i]['matrix']['biggest_per_product'] = $oID;
                } else {
                    $current_leader = $this->timeframe[$i]['orders'][$this->timeframe[$i]['matrix']['biggest_per_product']];
                    if ($o_data['num_products'] > $current_leader['num_products']) {
                        $this->timeframe[$i]['matrix']['biggest_per_product'] = $oID;
                    }
                }

                // Smallest order by product count (display order # and customer name)
                if (empty($this->timeframe[$i]['matrix']['smallest_per_product'])) {
                    $this->timeframe[$i]['matrix']['smallest_per_product'] = $oID;
                } else {
                    $current_leader = $this->timeframe[$i]['orders'][$this->timeframe[$i]['matrix']['smallest_per_product']];
                    if ($o_data['num_products'] < $current_leader['num_products']) {
                        $this->timeframe[$i]['matrix']['smallest_per_product'] = $oID;
                    }
                }

            }  // END foreach($this->timeframe[$i]['orders'] as $oID => $o_data)

            // Avg order value
            $this->timeframe[$i]['matrix']['avg_order_value'] = ($this->timeframe[$i]['total']['grand'] / count($this->timeframe[$i]['orders']));

            // Avg number of products in an order
            $this->timeframe[$i]['matrix']['avg_products_per_order'] = ($this->timeframe[$i]['total']['num_products'] / count($this->timeframe[$i]['orders']));

            // Avg number of unique products in an order
            $this->timeframe[$i]['matrix']['avg_diff_products_per_order'] = (sizeof($this->timeframe[$i]['total']['diff_products']) / count($this->timeframe[$i]['orders']));

            // Avg # orders per unique customer
            $this->timeframe[$i]['matrix']['avg_orders_per_customer'] = (sizeof($this->timeframe[$i]['orders']) / count($this->timeframe[$i]['matrix']['diff_customers']));

            // gather statistics from products array
            foreach ($this->timeframe[$i]['products'] as $pID => $p_data) {

                // Per product "spread" (number of orders that a product is a part of)
                foreach ($this->timeframe[$i]['orders'] as $oID => $o_data) {
                    foreach ($o_data['diff_products'] as $ordered_pID) {
                        if ($pID == $ordered_pID) {
                            if (!isset($this->timeframe[$i]['matrix']['product_spread'][$pID])) {
                                $this->timeframe[$i]['matrix']['product_spread'][$pID] = 0;
                            }
                            $this->timeframe[$i]['matrix']['product_spread'][$pID]++;
                            break;
                        }
                    }
                }

                // percentage of all revenue by product BEFORE shipping, tax, discounts, and gc's
                $this->timeframe[$i]['matrix']['product_revenue_ratio'][$pID] = number_format((($p_data['total'] / $this->timeframe[$i]['total']['goods']) * 100), 3);

                // percentage of all quantity by product
                $this->timeframe[$i]['matrix']['product_quantity_ratio'][$pID] = number_format((($p_data['quantity'] / $this->timeframe[$i]['total']['num_products']) * 100), 3);
            }  // END foreach($this->timeframe[$i]['products'] as $pID => $p_data)

        }  // END for ($i = 0, $i < sizeof($this->timeframe); $i++)

    }  // END function build_matrix()

    //////////////////////////////////////////////////////////
    // This function actually creates the CSV file when CSV
    // output is requested.  The logic and looping structure
    // is nearly identical to that found in the HTML output,
    // but we seperate it out for the sake of code clarity and
    // to allow for some differences between the 2 outputs.
    //
    function output_csv($csv_header, $timeframe_sort, $li_sort_a, $li_sort_order_a, $li_sort_b, $li_sort_order_b) 
    {
        $display_tax =  ($this->grand_total['goods_tax'] > 0);

        $filename = CSV_FILENAME_PREFIX . date('Ymd', $startDate) . "-" . date('Ymd', $endDate);
        header("Pragma: cache");
        header("Content-Type: text/comma-separated-values");
        header("Content-Disposition: attachment; filename=" . urlencode($filename) . ".csv");

        if ($csv_header) {
            switch ($this->detail_level) {
                case 'timeframe':
                    echo CSV_HEADING_START_DATE . CSV_SEPARATOR;
                    echo CSV_HEADING_END_DATE . CSV_SEPARATOR;
                    echo TABLE_HEADING_NUM_ORDERS . CSV_SEPARATOR;
                    echo TABLE_HEADING_NUM_PRODUCTS . CSV_SEPARATOR;
                    echo TABLE_HEADING_TOTAL_GOODS . CSV_SEPARATOR;
                    if ($display_tax) {
                        echo TABLE_HEADING_TAX . CSV_SEPARATOR;
                    }
                    echo TABLE_HEADING_SHIPPING . CSV_SEPARATOR;
                    echo TABLE_HEADING_DISCOUNTS . CSV_SEPARATOR;
                    echo TABLE_HEADING_GC_SOLD . CSV_SEPARATOR;
                    echo TABLE_HEADING_GC_USED . CSV_SEPARATOR;
                    echo TABLE_HEADING_TOTAL . CSV_NEWLINE;
                    break;
                case 'order':
                    echo CSV_HEADING_START_DATE . CSV_SEPARATOR;
                    echo CSV_HEADING_END_DATE . CSV_SEPARATOR;
                    echo TABLE_HEADING_ORDERS_ID . CSV_SEPARATOR;
                    echo CSV_HEADING_LAST_NAME . CSV_SEPARATOR;
                    echo CSV_HEADING_FIRST_NAME . CSV_SEPARATOR;
                    echo TABLE_HEADING_NUM_PRODUCTS . CSV_SEPARATOR;
                    echo TABLE_HEADING_TOTAL_GOODS . CSV_SEPARATOR;
                    if ($display_tax) {
                        echo TABLE_HEADING_TAX . CSV_SEPARATOR;
                        echo TABLE_HEADING_ORDER_RECORDED_TAX . CSV_SEPARATOR;
                    }
                    echo TABLE_HEADING_SHIPPING . CSV_SEPARATOR;
                    echo TABLE_HEADING_DISCOUNTS . CSV_SEPARATOR;
                    echo TABLE_HEADING_GC_SOLD . CSV_SEPARATOR;
                    echo TABLE_HEADING_GC_USED . CSV_SEPARATOR;
                    echo TABLE_HEADING_ORDER_TOTAL . CSV_NEWLINE;
                    break;
                case 'product':
                    echo CSV_HEADING_START_DATE . CSV_SEPARATOR;
                    echo CSV_HEADING_END_DATE . CSV_SEPARATOR;
                    echo TABLE_HEADING_PRODUCT_ID . CSV_SEPARATOR;
                    echo TABLE_HEADING_PRODUCT_NAME . CSV_SEPARATOR;
                    echo TABLE_HEADING_PRODUCT_ATTRIBUTES . CSV_SEPARATOR;
                    if (DISPLAY_MANUFACTURER) {
                        echo TABLE_HEADING_MANUFACTURER . CSV_SEPARATOR;
                    }
                    echo TABLE_HEADING_MODEL . CSV_SEPARATOR;
                    echo TABLE_HEADING_BASE_PRICE . CSV_SEPARATOR;
                    echo TABLE_HEADING_FINAL_PRICE . CSV_SEPARATOR;
                    echo TABLE_HEADING_QUANTITY . CSV_SEPARATOR;
                    if ($display_tax) {
                        echo TABLE_HEADING_TAX . CSV_SEPARATOR;
                    }
                    if (DISPLAY_ONE_TIME_FEES) {
                        echo TABLE_HEADING_ONETIME_CHARGES . CSV_SEPARATOR;
                    }
                    if ($display_tax) {
                        echo TABLE_HEADING_TOTAL . CSV_SEPARATOR;
                    }  
                    echo TABLE_HEADING_PRODUCT_TOTAL . CSV_NEWLINE;
                    break;
            }
        }  // END if ($csv_header)


        if ($timeframe_sort == 'desc') {
            krsort($this->timeframe);
        }

        foreach ($this->timeframe as $id => $timeframe) {
            // format the dates
            switch ($this->timeframe_group) {
                case 'day':
                    $start_date = date(TIME_DISPLAY_DAY, $timeframe['sd']);
                    $end_date = date(TIME_DISPLAY_DAY, $timeframe['ed']);
                    break;
                case 'week':
                    $start_date = date(TIME_DISPLAY_WEEK, $timeframe['sd']);
                    $end_date = date(TIME_DISPLAY_WEEK, $timeframe['ed']);
                    break;
                case 'month':
                    $start_date = date(TIME_DISPLAY_MONTH, $timeframe['sd']);
                    $end_date = date(TIME_DISPLAY_MONTH, $timeframe['ed']);
                    break;
                case 'year':
                    $start_date = date(TIME_DISPLAY_YEAR, $timeframe['sd']);
                    $end_date = date(TIME_DISPLAY_YEAR, $timeframe['ed']);
                    break;
            }
            switch ($this->detail_level) {
                case 'timeframe':
                    echo $start_date . CSV_SEPARATOR;
                    echo $end_date . CSV_SEPARATOR;
                    echo $timeframe['total']['num_orders'] . CSV_SEPARATOR;
                    echo $timeframe['total']['num_products'] . CSV_SEPARATOR;
                    //echo TEXT_DIFF . sizeof($timeframe['total']['diff_products']) . CSV_SEPARATOR;
                    echo $timeframe['total']['goods'] . CSV_SEPARATOR;
                    if ($display_tax) {
                        $timeframe['total']['goods_tax'] . CSV_SEPARATOR;
                        $timeframe['total']['order_recorded_tax'] . CSV_SEPARATOR;
                    }
                    echo $timeframe['total']['shipping'] . CSV_SEPARATOR;
                    echo $timeframe['total']['discount'] . CSV_SEPARATOR;
                    //echo TEXT_QTY . $timeframe['total']['discount_qty'] . CSV_SEPARATOR;
                    echo $timeframe['total']['gc_sold'] . CSV_SEPARATOR;
                    //echo TEXT_QTY . $timeframe['total']['gc_sold_qty'] . CSV_SEPARATOR;
                    echo $timeframe['total']['gc_used'] . CSV_SEPARATOR;
                    //echo TEXT_QTY . $timeframe['total']['gc_used_qty'] . CSV_SEPARATOR;
                    echo $timeframe['total']['grand'] . CSV_NEWLINE;
                    break;

                case 'order':
                    // sort the orders according to requested sort options
                    unset($dataset1, $dataset2);
                    if (is_null($timeframe['orders'])) {
                    // Ignore days for which no info exists
                        continue;
                    }
                    foreach($timeframe['orders'] as $oID => $o_data) {
                        $dataset1[$oID] = $o_data[$li_sort_a];
                        $dataset2[$oID] = $o_data[$li_sort_b];
                    }

                    $sort1 = ($li_sort_order_a == 'asc') ? SORT_ASC : SORT_DESC;
                    $sort2 = ($li_sort_order_b == 'asc') ? SORT_ASC : SORT_DESC;
                    array_multisort($dataset1, $sort1, $dataset2, $sort2, $timeframe['orders']);

                    foreach($timeframe['orders'] as $key => $o_data) {
                        // skip order if it has no value
                        if ($o_data['has_no_value']) {
                            continue;
                        }

                        echo $start_date . CSV_SEPARATOR;
                        echo $end_date . CSV_SEPARATOR;
                        echo $o_data['oID'] . CSV_SEPARATOR;
                        echo $o_data['last_name'] . CSV_SEPARATOR;
                        echo $o_data['first_name'] . CSV_SEPARATOR;
                        echo $o_data['num_products'] . CSV_SEPARATOR;
                        //echo (sizeof($o_data['diff_products']) > 1 ? TEXT_DIFF . sizeof($o_data['diff_products']) : TEXT_SAME) . CSV_SEPARATOR;
                        echo $o_data['goods'] . CSV_SEPARATOR;
                        if ($display_tax) {
                            echo $o_data['goods_tax'] . CSV_SEPARATOR;
                            echo $o_data['order_recorded_tax'] . CSV_SEPARATOR;
                        }
                        echo $o_data['shipping'] . CSV_SEPARATOR;
                        echo $o_data['discount'] . CSV_SEPARATOR;
                        //echo TEXT_QTY . $o_data['discount_qty'] . CSV_SEPARATOR;
                        echo $o_data['gc_sold'] . CSV_SEPARATOR;
                        //echo TEXT_QTY . $o_data['gc_sold_qty'] . CSV_SEPARATOR;
                        echo $o_data['gc_used'] . CSV_SEPARATOR;
                        //echo TEXT_QTY . $o_data['gc_used_qty'] . CSV_SEPARATOR;
                        echo $o_data['grand'] . CSV_NEWLINE;
                    }
                    break;

                case 'product':
                    // sort the products according to requested sort options
                    unset($dataset1, $dataset2);
                    foreach($timeframe['products'] as $pID => $p_data) {
                        $dataset1[$pID] = $p_data[$li_sort_a];
                        $dataset2[$pID] = $p_data[$li_sort_b];
                    }

                    $sort1 = ($li_sort_order_a == 'asc') ? SORT_ASC : SORT_DESC;
                    $sort2 = ($li_sort_order_b == 'asc') ? SORT_ASC : SORT_DESC;
                    array_multisort($dataset1, $sort1, $dataset2, $sort2, $timeframe['products']);

                    foreach($timeframe['products'] as $key => $p_data) {
                        echo $start_date . CSV_SEPARATOR;
                        echo $end_date . CSV_SEPARATOR;
                        echo $p_data['pID'] . CSV_SEPARATOR;
                        echo str_replace(array('<small>', '</small>', '<br>'), '', $p_data['name']) . CSV_SEPARATOR;
                        echo str_replace(array('<small>', '</small>', '<br>'), '', $p_data['attributes']) . CSV_SEPARATOR;
                        if (DISPLAY_MANUFACTURER) {
                            echo $p_data['manufacturer'] . CSV_SEPARATOR;
                        }
                        echo $p_data['model'] . CSV_SEPARATOR;
                        echo $p_data['base_price'] . CSV_SEPARATOR;
                        echo $p_data['final_price'] . CSV_SEPARATOR;
                        echo $p_data['quantity'] . CSV_SEPARATOR;
                        if ($display_tax) {
                            echo $p_data['tax'] . CSV_SEPARATOR;
                        }
                        if (DISPLAY_ONE_TIME_FEES) {
                            echo $p_data['onetime_charges'] . CSV_SEPARATOR;
                        }
                        if ($display_tax) {
                            echo $p_data['total'] . CSV_SEPARATOR;
                        }
                        echo $p_data['grand'] . CSV_NEWLINE;
                    }
                    echo CSV_NEWLINE;
                    break;
            }  //END switch ($this->detail_level)

        }  // END foreach ($this->timeframe as $id => $timeframe)

    }  // END function output_csv()

}
