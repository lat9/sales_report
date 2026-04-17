<?php
/**
 * Sales Report II, v4.0.3
 *
 * The language file contains all the text that appears on the report. The first set of
 * configuration defines actually impact the report's output and behavior.
 *
 * @author     Frank Koehl (PM: BlindSide)
 * @author     Conor Kerr <conor.kerr_zen-cart@dev.ceon.net>
 * @updated by stellarweb to work with version 1.5.0 02-29-12 
 * @updated by lat9 for continued operation under zc155/zc156, 20190622
 * @copyright  Portions Copyright 2003-2006 Zen Cart Development Team
 * @copyright  Portions Copyright 2003 osCommerce
 * @license    http://www.gnu.org/copyleft/gpl.html   GNU Public License V2.0
 */

$defines = [
    //////////////////////////////////////////////////////////
    // DISPLAY EMPTY TIMEFRAME LINES
    // Setting this define to false will disable displaying
    // a timeframe line if that timeframe is empty.  By
    // default, an empty timeframe displays the value of the
    // define TEXT_NO_DATA.
    //
    // Be aware, if this is enabled and your search yields
    // no results at all, the screen will look as if no search
    // was performed (which is why this is enabled by default).
    //
    // Note: This is a change from previous versions where false enabled
    // the display and true disabled the display!
    //
    'DISPLAY_EMPTY_TIMEFRAMES' => true,

    //////////////////////////////////////////////////////////
    // REPORTING A SUBSET OF CUSTOMERS / PRODUCTS
    // By checking the boxes to 'Only Include Specific customers
    // or Products (SEARCH_SPECIFIC_CUSTOMERS/PRODUCTS),
    // only orders for those customers / products will be
    // included in the result. By default, the included customers/
    // products will be printed above the results table. If this
    // gets too long, this printout can be disabled with
    // the DISPLAY booleans below.
    //
    // If you often want a specific product, you can set a 
    // default here, e.g.:
    // define('INCLUDE_PRODUCTS', '10, 15');
    //
    'INCLUDE_PRODUCTS' => '',
    'INCLUDE_CUSTOMERS' => '',
    'DISPLAY_TABLE_HEADING_CUSTOMERS' => true,
    'DISPLAY_TABLE_HEADING_PRODUCTS' => true,
    'TEXT_CUSTOMER_TABLE_HEADING' => ' Orders for: ',  //Prefix used to print before customer name(s) when filtering by customer

    //////////////////////////////////////////////////////////
    // PRODUCT MANUFACTURERS COLUMN
    // Setting this define to true will display the
    // manufacturer on each product line item, and will default
    // to the value of TEXT_NONE if there is no manufacturer.
    // False will remove the manufacturer column from the report.
    //
    'DISPLAY_MANUFACTURER' => false,

    //////////////////////////////////////////////////////////
    // ONE-TIME FEES COLUMN
    // If your store does not have *any* one-time fees on its
    // products, you can disable displaying the column.
    //
    // Note that this switch does not affect math calculations,
    // so if you happen to have a product with fees attached,
    // they will still be accounted for and appear in the total.
    //
    'DISPLAY_ONE_TIME_FEES' => false,

    //////////////////////////////////////////////////////////
    // COUNTRY/STATE COLUMNS
    // If your store wants to display the country and state when
    // displaying "Order Total Line Items", set this to (bool)true.
    //
    'DISPLAY_COUNTRY_AND_STATE' => false,

    //////////////////////////////////////////////////////////
    // DECIMAL PLACES IN AVERAGES
    // Sets the number of decimal places displayed in averages
    // on timeframe statistics display
    //
    'NUM_DECIMAL_PLACES' => 2,

    //////////////////////////////////////////////////////////
    // TIMEFRAME DATE DISPLAY
    // Note:  Other constants moved to the main processing file for v3.2.1.
    //
    'DATE_FORMAT_TIMEFRAME' => 'd-MMM.-yy',
    'DATE_SPACER' => ' to<br>&nbsp;&nbsp;&nbsp;',

    //////////////////////////////////////////////////////////
    // EXCLUDE SPECIFIED PRODUCTS
    // Prevents specified products from appearing on the sales
    // report at all.  **ADDING PRODUCTS TO THIS DEFINE WILL
    // IMPACT TOTALS CALCULATIONS!**
    //
    // The value of the product will be excluded from totals
    // for gc_sold, gc_sold_qty, goods, num_products, and
    // diff_products.
    //
    // The values for gc_used, gc_used_qty, discount,
    // discount_qty, tax, and shipping all come from the
    // orders_total table, and so CANNOT be excluded based
    // on product ID.
    //
    // If an order is made up entirely of excluded products,
    // and has no shipping, discounts, tax, or used gift
    // certificates, it will have a total of 0.  In this
    // situation, the order will not be displayed in the results.
    //
    // EXAMPLE:     'EXCLUDE_PRODUCTS' => serialize([25, 14, 43]) );
    //
    'EXCLUDE_PRODUCTS' => serialize([]),

    /*
    ** LANGUAGE DEFINES
    */
    // Search menu heading
    'PAGE_HEADING' => 'Sales Report II',
    'HEADING_TITLE_SEARCH' => '1. Gather &amp; Filter Data',
    'HEADING_TITLE_SORT' => '2. Sort &amp; Specify Results',
    'HEADING_TITLE_PROCESS' => '3. Generate Report',
    'SEARCH_TIMEFRAME' => 'Timeframe',
    'SEARCH_TIMEFRAME_DAY' => ' Daily',
    'SEARCH_TIMEFRAME_WEEK' => ' Weekly',
    'SEARCH_TIMEFRAME_MONTH' => ' Monthly',
    'SEARCH_TIMEFRAME_YEAR' => ' Annually',
    'SEARCH_TIMEFRAME_SORT' => 'Timeframe Sort',
    'SEARCH_TIMEFRAME_SORT_ASC' => 'asc',
    'SEARCH_TIMEFRAME_SORT_DESC' => 'desc',
    'SEARCH_DATE_PRESET' => 'Preset Date Range',
    'SEARCH_DATE_CUSTOM' => 'Custom Date Range',
    'SEARCH_DATE_TODAY' => ' Today (%s)',
    'SEARCH_DATE_YESTERDAY' => ' Yesterday (%s)',
    'SEARCH_DATE_LAST_MONTH' => ' Last Month (%s)',
    'SEARCH_DATE_THIS_MONTH' => ' This Month (%s)',
    'SEARCH_DATE_LAST_YEAR' => ' Last Year (%s)',
    'SEARCH_DATE_LAST_12_MONTHS' => ' Last 12 Months',
    'SEARCH_DATE_YTD' => ' YTD (%s to %s)',
    'SEARCH_DATE_YTD_FORMAT_1' => 'MMM. d',
    'SEARCH_DATE_YTD_FORMAT_2' => 'MMM. d y',
    'SEARCH_DATE_YTD_FORMAT_M_D' => 'MMM. d',
    'SEARCH_DATE_YTD_FORMAT_M_Y' => 'MMMM \'\'yy',
    'SEARCH_START_DATE' => 'Start Date',
    'SEARCH_END_DATE' => 'End Date (inclusive)',
    'SEARCH_DATE_FORMAT' => 'mm/dd/yyyy',
    'SEARCH_DATE_TARGET' => 'Search date of...',
    'SEARCH_PAYMENT_METHOD' => 'Payment Method',
    'SEARCH_PAYMENT_METHOD_OMIT' => 'Payment Method To Omit',
    'SEARCH_CURRENT_STATUS' => 'Current Order Status',
    'SEARCH_EXCLUDED_STATUS' => 'Order Status to Omit',
    'SEARCH_SPECIFIC_CUSTOMERS' => 'Only include specific Customer IDs (comma separated list)',
    'SEARCH_SPECIFIC_PRODUCTS' => 'Only include specific Product IDs (comma separated list)',
    'SEARCH_MANUFACTURER' => 'Product Manufacturer',
    'SEARCH_DETAIL_LEVEL' => 'Displayed Information',
    'SEARCH_OUTPUT_FORMAT' => 'Output Format',
    'SEARCH_SORT_PRODUCT' => 'Sort products by...',
    'SEARCH_SORT_ORDER' => 'Sort orders by...',
    'SEARCH_SORT_THEN' => 'Then sort by...',
    'BUTTON_SEARCH' => 'Show me the money!',
    'BUTTON_DEFAULT_SEARCH' => 'Quick Search',
    'SEARCH_WAIT_TEXT' => 'Processing, please wait...',

    // Form element text
    // radio buttons
    'RADIO_DATE_TARGET_PURCHASED' => 'Order purchase',
    'RADIO_DATE_TARGET_STATUS' => 'Assigned status (select below)',
    'RADIO_TIMEFRAME_SORT_ASC' => 'Oldest on top',
    'RADIO_TIMEFRAME_SORT_DESC' => 'Newest on top',
    'RADIO_LI_SORT_ASC' => 'Ascending',
    'RADIO_LI_SORT_DESC' => 'Descending',

    // dropdown menus
    'SELECT_DETAIL_TIMEFRAME' => 'Timeframe Totals',
    'SELECT_DETAIL_PRODUCT' => '&nbsp;+ Product Line Items',
    'SELECT_DETAIL_ORDER' => '&nbsp;+ Order Line Items',
    'SELECT_DETAIL_MATRIX' => 'Timeframe Statistics',
    'SELECT_OUTPUT_DISPLAY' => 'Screen Display',
    'SELECT_OUTPUT_PRINT' => 'Print Format',
    'SELECT_OUTPUT_CSV' => 'CSV Export',
    'SELECT_PRODUCT_ID' => 'Product ID',
    'SELECT_QUANTITY' => 'Quantity',
    'SELECT_LAST_NAME' => 'Cust. Last Name',

    // checkboxes
    'CHECKBOX_AUTO_PRINT' => ' Print report automatically',
    'CHECKBOX_CSV_HEADER' => ' Column titles in first row',
    'CHECKBOX_NEW_WINDOW' => ' Open results in new window',
    'CHECKBOX_VALIDATE_TOTALS' => ' Output Order Total Validation Column',
    'CHECKBOX_DISPLAY_EMAIL_ADDRESS' => ' Display customer\'s email address?',

    // Report Column Headings
    // Timeframe
    'TABLE_HEADING_TIMEFRAME' => 'Timeframe',
    'TABLE_HEADING_NUM_ORDERS' => 'Num Orders',
    'TABLE_HEADING_NUM_PRODUCTS' => 'Num Products',
    'TABLE_HEADING_TOTAL_GOODS' => 'Goods Value',
    'TABLE_HEADING_TAX' => 'Tax',
    'TABLE_HEADING_GOODS_TAX' => 'Goods Tax',
    'TABLE_HEADING_ORDER_RECORDED_TAX' => 'Order Rec. Tax',
    'TABLE_HEADING_SHIPPING' => 'Shipping',
    'TABLE_HEADING_DISCOUNTS' => 'Discounts',
    'TABLE_HEADING_GC_SOLD' => 'Gift Cert. Sold',
    'TABLE_HEADING_GC_USED' => 'Gift Cert. Used',
    'TABLE_HEADING_TOTAL' => 'Total',
    'TABLE_FOOTER_TIMEFRAMES' => ' Timeframes',

    // Order Line Items
    'TABLE_HEADING_ORDERS_ID' => 'Order ID',
    'TABLE_HEADING_CUSTOMER' => 'Customer',
    'TABLE_HEADING_EMAIL_ADDRESS' => 'Email Address',
    'TABLE_HEADING_COUNTRY' => 'Country',
    'TABLE_HEADING_STATE' => 'State',
    'TABLE_HEADING_ORDER_TOTAL' => 'Order Total',
    'TABLE_HEADING_ORDER_TOTAL_VALIDATION' => 'OT Valid',

    // Product Line Items
    'TABLE_HEADING_PRODUCT_ID' => 'Prod ID',
    'TABLE_HEADING_PRODUCT_NAME' => 'Product Name',
    'TABLE_HEADING_PRODUCT_ATTRIBUTES' => 'Attributes',
    'TABLE_HEADING_MANUFACTURER' => 'Manufacturer',
    'TABLE_HEADING_MODEL_NO' => 'Model No.',
    'TABLE_HEADING_BASE_PRICE' => 'Base Price',
    'TABLE_HEADING_FINAL_PRICE' => 'Final Price',
    'TABLE_HEADING_QUANTITY' => 'Qty.',
    'TABLE_HEADING_ONETIME_CHARGES' => '1-time Fees',
    'TABLE_HEADING_PRODUCT_TOTAL' => 'Product Total',

    // Data Matrix
    'MATRIX_GENERAL_STATS' => 'General Stats',
    'MATRIX_ORDER_REVENUE' => 'Total Revenue',
    'MATRIX_ORDER_PRODUCT_COUNT' => 'Total Product Count',
    'MATRIX_LARGEST' => 'Largest Order: ',
    'MATRIX_SMALLEST' => 'Smallest Order: ',
    'MATRIX_AVERAGES' => 'Averages',
    'MATRIX_AVG_ORDER' => '&nbsp;order value',
    'MATRIX_AVG_PROD_ORDER' => '&nbsp;products per order',
    'MATRIX_AVG_PROD_ORDER_DIFF' => '&nbsp;unique products per order',
    'MATRIX_AVG_ORDER_CUST' => '&nbsp;orders per customer',
    'MATRIX_ORDER_STATS' => 'Order Stats',
    'MATRIX_TOTAL_PAYMENTS' => 'Payment Methods',
    'MATRIX_TOTAL_CC' => 'Credit Cards',
    'MATRIX_TOTAL_SHIPPING' => 'Shipping Methods',
    'MATRIX_TOTAL_CURRENCIES' => 'Used Currencies',
    'MATRIX_TOTAL_CUSTOMERS' => 'Unique Customers',
    'MATRIX_PRODUCT_STATS' => 'Product Stats',
    'MATRIX_PRODUCT_SPREAD' => 'Product Spread',
    'MATRIX_PRODUCT_REVENUE_RATIO' => 'Total Revenue %',
    'MATRIX_PRODUCT_QUANTITY_RATIO' => 'Total Quantity %',

    // CSV Export
    'CSV_FILENAME_PREFIX' => 'sales_',
    'CSV_HEADING_START_DATE' => 'Start Date',
    'CSV_HEADING_END_DATE' => 'End Date',
    'CSV_HEADING_LAST_NAME' => 'Last Name',
    'CSV_HEADING_FIRST_NAME' => 'First Name',
    'CSV_HEADING_COUNTRY' => 'Country',
    'CSV_HEADING_STATE' => 'State',
    'CSV_SEPARATOR' => ',',
    'CSV_NEWLINE' => "\n",

    // Print Format
    'PRINT_DATE_TO' => ' thru ',
    'PRINT_DATE_TARGET' => 'Date of ',
    'PRINT_TIMEFRAMES' => '%s timeframes sorted %s',
    'PRINT_DATE_PURCHASED' => 'order creation',
    'PRINT_DATE_STATUS' => 'assigned status',
    'PRINT_ORDER_STATUS' => '%s [%s]',
    'PRINT_PAYMENT_METHOD' => 'Payment Method:',
    'PRINT_CURRENT_STATUS' => 'Current Order Status:',
    'PRINT_DETAIL_LEVEL' => 'Displaying ',

    // javascript pop-up alert window
    'ALERT_JS_HIGHLIGHT' => '#FF40CF',
    'ALERT_MSG_START' => 'There are one or more errors with your selections:',
    'ALERT_DATE_INVALID_LENGTH' => '> Dates must be 10 characters in length: ',
    'ALERT_DATE_INVALID' => '> This is not a valid date: ',
    'ALERT_MSG_FINISH' => 'Please correct the issue(s) and resubmit your search.',

    // Other text defines
    'ERROR_MISSING_REQ_INFO' => 'Error: Required fields are empty',
    'ALT_TEXT_SORT_ASC' => 'Re-sort in ASCENDING order',
    'ALT_TEXT_SORT_DESC' => 'Re-sort in DESCENDING order',
    'TEXT_REPORT_TIMESTAMP' => 'Report Time: ',
    'TEXT_PARSE_TIME' => 'Parse Time: %s sec.',
    'TEXT_EMPTY_SELECT' => '(doesn\'t matter)',
    'TEXT_QTY' => '| Qty: ',
    'TEXT_DIFF' => ' | Diff: ',
    'TEXT_SAME' => '| (same)',
    'TEXT_SAME_ONE' => '| --',
    'TEXT_PRINT_FORMAT' => 'display report in print format',
    'TEXT_NO_DATA' => '-- NO ORDERS IN TIMEFRAME --',

    // Buttons
    'BUTTON_TIMEFRAME_PRESET' => 'Choose Preset',
    'BUTTON_TIMEFRAME_CUSTOM' => 'Choose Custom',
];

// -----
// Some of the language definitions reuse the main definitions; add them separately since
// they're not defined at this point.
//
$defines['ALERT_CSV_CONFLICT'] = '> CSV output is not available for ' . $defines['SELECT_DETAIL_MATRIX'] . ' display.';
$defines['ERROR_CSV_CONFLICT'] = 'CSV output is not available for <em>' . $defines['SELECT_DETAIL_MATRIX'] . '</em> display; please re-select the report options.';
$defines['TEXT_PRINT_FORMAT_TITLE'] = 'TIP: click \'' . $defines['PAGE_HEADING'] . '\' to return to display view';

return $defines;
