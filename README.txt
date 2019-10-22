SALES REPORT Version 3.3.2
By Frank Koehl (PM: BlindSide), Conor Kerr Ceon, Carl Peach, Cindy Merkin (lat9)
 
author Czech translation :  Josef Zahradník
web:                        www.magic-shop.cz   
code page:                  cp1250

Forum thread:
https://www.zen-cart.com/forum/showthread.php?p=253173#post253173

=========
 INTRO
=========
This report was designed to offer professional-level accounting data for businesses
who use Zen Cart heavily, with multiple display options, several levels of data
output, and flexible search options.  It was tested (and now currently used) by a
full-time accountant and bookkeeper in a Zen Cart shop with 6500 orders (and counting)
and grossing almost $1 million in sales.  If it works for them, it should work for you.

The features are explained briefly below, but the report has been built with usability
in mind, even for the most simplistic of users.  Using the report should be fairly
obvious and self-explanatory, but please let me know if you feel something can be
tweaked/added/removed to offer a more user-friendly experience.

Note that JavaScript is required to run this report.

For the developers among you, I hope you'll find the comments included throughout the
files sufficient to create whatever modifications you desire.  I only ask that you let
me know what you have created, in case it could be useful enough to add back into the
source (for which you would of course get credit).

Thanks for downloading the Sales Report, and enjoy!

--
Frank



===========
 INSTALL
===========
1. Download the package and unzip to a temp directory.

2. OPTIONAL - Set report search criteria defaults.
              See REPORT DEFAULTS below for instructions.

3. Copy the contents of the "admin" folder to the admin folder of your shop whatever it is called (it should have been renamed from the default "admin").
	The files are already arranged in their correct locations and there are *no* overwrites of core files!

   Updating from the old Sales Report?  Don't worry!  The new version maintains the
   same filenames, so simply overwrite all conflicting files.  Again, you should
   ALWAYS back up before making any changes.

4. That's it!  You'll find "Sales Report" under "Reports" in the Admin.

============
 FEATURES
============
 Date Range
--------------
  - Ability to choose from a list of common date ranges, or enter a custom date range
  - Date search may apply to EITHER...
        > date of purchase or the
        > date a specific status was applied
 Date Format (National conventions)
--------------
  - 'd/m/y'
  - 'm/d/y'
  - 'd.m.y'

 Filters
-----------
  - Filter orders by payment method
  - Filter orders by currently applied order status
  - Filter orders by specific customer ID's
  - Filter orders by specific product ID's

 Reported Data
-----------------
  - Totals per timeframe
  - Totals per timeframe PLUS one of following...
        > break out by order
        > break out by product
        > high-level summary statistics

 Sorting Data
----------------
  - Group date ranges into one of four "timeframes:" single day, every 7 days, every
    calendar month, or every calendar year
  - Sort timeframes in ascending or descending chronological order

 Display Format
------------------
  - Screen display: includes normal admin nav header and report search boxes
  - Print format: headers removed, data optimized for printing on 8.5 x 11 paper;
                  (hint: the page title is a link to return the report to display format)
  - CSV Export: data arranged for import to another program; viewable in MS Excel.



==============================
 FREQUENTLY ASKED QUESTIONS
==============================
"Help! The [insert column here] is showing up as ####### when I open the CSV file in Excel!"
-----
That happens when the data is too big for the current column width.  Just widen the column
and the data will "automagically" appear.  Now be thankful you didn't post that question in
the forums.  ;)


"How come the CSV export for order/product line items does not have the timeframe total line?"
-----
The CSV export option is designed to move Zen Cart sales data into another system.  In order
to import from a CSV file, the importing program must know what format the data will be in,
and that format must remain consistent.  The timeframe total line breaks the data's
consistency, and therefore it is not displayed.  If you want timeframe lines, run the same
report again in CSV export mode, choosing just the timeframe totals.


"How come the currency data is not formatted as currency in the CSV export?"
-----
The rationale is the same as that provided in the previous answer.  It's assumed that
you're importing to a program that has some ability to perform math calculations. Your
dollar/pound/yen symbol would likely prevent that program from reading the value properly.


"If I run the report for a big date range, the report runs really slow.  My server
specs are awesome, so it must be your report.  What's the problem?  What can I do?"
-----
As one look at the class file will tell you, the Sales Report is not merely reporting
back data stored in the database; it runs calculations, sometimes very complicated ones.
I've already optimized the number of database queries as much as I can without
sacrificing data (maximum of 6 in "Timeframe Statistics" display).

If the report is slow and you want to show product line items, you can speed it up by
disabling the DISPLAY_MANUFACTURER setting in the language file to 'false'.  That saves
one database query per product, and will have a noticeable effect on large quantities
of different products.

Otherwise, you'll have to resort to adjusting your search settings.  Break your report
into several smaller runs, or limit the returned data to the timeframe total lines
(i.e. drop order or product line items).  The "Timeframe Statistics" option is the most
complicated report, pulling all the data for both product and order line items, then
running additional calculations on all of it.  I strongly recommend against running it
for more than a month's worth of data at a time; you *can* bring your server to its
knees by doing so, no matter how awesome it is.

Report performance will also depend heavily on the power of your server.  If you bought
that shared hosting package because it cost $5/month, don't expect to get any
processor priority.  Remember, you get what you pay for.

Finally, you may have to just accept the lag and wait for the report.  In this case, be
sure to ramp up the timeout period on your browser, to ensure the report can complete
and return the results.


"Can I make a donation?"
-----
Absolutely, your support really does help!

PayPal donations can be directed to fkoehl@gmail.com.

=============================
CHANGE IN VERSION 3.1
=============================

Updated by Carl Peach 7/12/2012 This release supports ZenCart 1.5.
[ADDED]    Added some date presets for Last Year and YTD.
[ADDED]    New feature to 'omit' specific payment methods is added.
[REMOVED]  extra_definitions - redundant with extra_datafiles for
           zencart 1.5 admin feature registration
Other than that, this release carries forward everything from 3.0.


=============================
CHANGE IN VERSION 3.0
=============================

Updated by stellarweb to work with version 1.5.0 02-29-12 
Removed boxes folder
Added admin/includes/functions/stats_sales_report.php file to bring link to report to Reports Menu

====================
CHANGE IN Version 2.3.2
====================
admin\includes\classes\sales_report.php

find:

      } else if (strtolower(DATE_FORMAT) == 'd/m/y') {
        // Use UK date format (d/m/Y)
        $this->sd_raw = mktime(0, 0, 0, substr($sd, 3, 2), substr($sd, 0, 2), substr($sd, 6, 4) );
        $this->ed_raw = mktime(0, 0, 0, substr($ed, 3, 2), substr($ed, 0, 2), substr($ed, 6, 4) );

add:
      } else if (strtolower(DATE_FORMAT) == 'd.m.y') {
        // Use CZ / SK date format (d/m/Y)
        $this->sd_raw = mktime(0, 0, 0, substr($sd, 3, 2), substr($sd, 0, 2), substr($sd, 6, 4) );
        $this->ed_raw = mktime(0, 0, 0, substr($ed, 3, 2), substr($ed, 0, 2), substr($ed, 6, 4) );
------------------------------------------------------------------------------------------
admin\includes\javascript\sales_report.js.php

find:
<?php
} else if (strtolower(DATE_FORMAT) == 'd/m/y') {
  // Use UK date format (d/m/Y)
?>
      if (isDate(sd_array[0], sd_array[1], sd_array[2]) &&
          isDate(ed_array[0], ed_array[1], ed_array[2]) ) {
<?php


add:
} else if (strtolower(DATE_FORMAT) == 'd.m.y') {
  // Use CZ, SK date format (d.m.Y)
?>
      if (isDate(sd_array[0], sd_array[1], sd_array[2]) &&
          isDate(ed_array[0], ed_array[1], ed_array[2]) ) {
<?php
------------------------------------------------------------------------------------------
=====================
NEW FILE in version 2.2.1
=====================
admin\includes\languages\czech\stats_sales_report.php
admin\includes\languages\czech\extra_definitions\stats_sales_report.php

//////////////////////////////////////////////////////////////////////////////////////

------------------------------------------------------------------
2009 12 Uploaded by torvista with no code changes as v2.2.0

2007 October 30th Updated by Conor Kerr, Ceon to version 2.2.0RC1 and posted in support thread
(but never uploaded to Software Add-ons).
=====================================
2011 01     v2.3.2
=====================================
Changes in 2.3.2
=====================================
[ADDED]    Languages CZ - Czech translation
[ADDED]    Support for CZ date formats added. Module now supports DD.MM.YYYY 
           format as well as MM/DD/YYYY.
Changes in 2.3.1
=====================================
[ADDED]    Added "today" as an option.
[BUGFIX]   When one of the select lists is empty, the form breaks.
=====================================
Changes in 2.3.0
=====================================
[BUGFIX]   Fixed feature to limit report to specific product IDs.
[ADDED]    Menu options to optionally specify product IDs. When checked, only
           orders with the specified IDs (comma separated list of numbers) are inlcuded.
[ADDED]    Menu options to optionally specify customer IDs. When checked, only
           orders with the specified IDs (comma separated list of numbers) are inlcuded.
=====================================
Changes in 2.2.0
=====================================
[ADDED]    Support for UK date formats added. Module now supports DD/MM/YYYY 
           format as well as MM/DD/YYYY.
[UPDATED]  Tax column renamed to "Goods Tax". The tax for this column is now 
           calculated directly from the product information for the order, in 
           the same manner as the order class calculates the tax.
[ADDED]    A second tax column, "Order Recorded Tax" has been added so that a 
           comparison can be made with the calculated tax so that any rounding 
           errors can be identified.
[ADDED]    Validation Column added for Order Total in " + Order Line Items" 
           view. If enabled, an extra column is displayed which highlights any 
           orders for which the tax recorded doesn't match that calculated 
           based on the products' prices and tax rates. This should aid those 
           who are finding that their official order totals don't quite match 
           up with the order totals reported by the Sales Report module (and 
           therefore avoid annoying their accountants!). This is a rare 
           problem but seems to appear from time to time with very slight 
           (0.01) rounding errors.
[UPDATED]  If no order information exists for a particular day in a report, no 
           row is generated for that day. (Previously an empty row was 
           displayed which is of little use to anyone!).
[BUGFIX]   Parsing problems with short tags disabled fixed.
[BUGFIX]   Currencies class wasn't being loaded at an appropriate point in the 
           main script.
[ADDED]    Order ID in " + Order Line Items" view now links directly to the 
           order edit functionality of the admin for the order. Allows quick 
           and easy look ups of the detailed order information.
[ADDED]    Support for That Software Guy's "Better Together" order total module 
           added.
[ADDED]    Support for Ceon's "Cashback" order total module added.

=====================================
Changes in 3.2.0, 2017-10-18 (lat9)
=====================================
[CHANGE]   Updated class selector for use under PHP 7.0 and later
[BUGFIX]   Column-count "off" for CSV output when taxes are included.
[BUGFIX]   Manufacturer filter was not being honored
[CHANGE]   Applied PSR-2 styling to the various modules.
[BUGFIX]   Correct year display for "Last Year" selection.

=====================================
Changes in 3.2.1, 2019-02-16 (lat9)
=====================================
[CHANGE]   Interoperability with zc156, given that the language-file load order has changed.

Modified:
/YOUR_ADMIN/stats_sales_report.php
/YOUR_ADMIN/includes/languages/english/stats_sales_report.php

=====================================
Changes in 3.3.0, 2019-07-03 (lat9) ... Drops support for Zen Cart versions prior to 1.5.5!
=====================================
[CHANGE]   zc156 Interoperability: "Custom" date, calendar display is 'off-screen'.
[CHANGE]   Remove "default" settings.
[CHANGE]   "Remember" admin's selection for 'Show report in a new window'.
[CHANGE]   Add orders' status to omit.
[BUGFIX]   Correct various PHP Notices for more recent versions of PHP
[CHANGE]   Convert javascript to jQuery; report now outputs HTML5-compatible HTML.
[CHANGE]   Use the customer's name from the 'order', not from their customer-record.
[BUGFIX]   Correct error when specific customer-/product-list indicated but not supplied.

Modified:
/YOUR_ADMIN/stats_sales_report.php
/YOUR_ADMIN/includes/classes/sales_report.php
/YOUR_ADMIN/includes/languages/english/stats_sales_report.php
/YOUR_ADMIN/includes/javascript/sales_report_js.php

No longer used/distributed:
/YOUR_ADMIN/images/icons/custom_range.gif
/YOUR_ADMIN/images/icons/preset_range.gif

=====================================
Changes in 3.3.1, 2019-08-25 (lat9)
=====================================
[BUGFIX]   Correct PHP 7.3 warning.
[BUGFIX]   Custom date-selection not 'remembered'.

Modified:
/YOUR_ADMIN/stats_sales_report.php
/YOUR_ADMIN/includes/classes/sales_report.php

=====================================
Changes in 3.3.2, 2019-10-22 (lat9)
=====================================
[BUGFIX]   Correct PHP notices during products-purchased CSV output.
[BUGFIX]   (Interoperation) Don't load extra-functions until an admin is logged in.

Modified:
/YOUR_ADMIN/includes/classes/sales_report.php
/YOUR_ADMIN/includes/functions/extra_functions/stats_sales_report.php

=====================================
Sponsored by Destination ImagiNation, Inc.
www.destinationimagination.org

Color scheme and icons by Kim
www.templates-for-zen-cart.com

This script is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY OR FITNESS FOR A PARTICULAR PURPOSE.

Released under the General Public License (see LICENSE.txt)

Always backup your files and database before making changes!

