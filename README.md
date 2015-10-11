Backorder
=========
Adds dispatch estimate notifications to product pages, the cart page, frontend order view, backend order view and order confirmation emails.

Description
-----------
Adds a dispatch lead time attribute to each product which should be entered as a human readable text string describing the lead time for the product, i.e. 1 week and 3 days.  Weekends are ignored so for instance 1 week would be interpreted as 7 working days (1 week and 2 days).  For parent products (grouped, configurable and bundle) the lead time should be entered for the child simple products rather than the parent product - any lead time on a parent product will be ignored.

Usage
-----
Admin settings can be found under System -> Configuration -> Hussey Coding -> Backorder and are as follows:

**Enable Backorder**
Entirely enable or disable extension functionality

**Ignore Stock**
Add dispatch estimates even if the product is in stock

**Require Agreement**
The customer must agree to estimated dispatch dates for products with a lead time before they can checkout

**Enable For Non Stock Managed**
Add dispatch estimates for products not using stock management

**Show Dispatch Today/Tomorrow**
For products with no lead time show 'dispatched today if ordered within' notice

**Order Cut Off Time**
Time after which a product will no longer be dispatched the same day

**Fixed Holidays**
Comma separated list of holiday dates which occur on the same date each year in day-month format i.e. 25-12,26-12

**Dynamic Holidays**
Comma separated list of holiday dates which occur on different dates each year.  Enter in format day of week (1-7 for Mon-Sun)-week in the month-month i.e. 1-1-5 for May Day (first Monday in May). Use 'last' for last week of the month i.e. 1-last-5

**Handling Time**
Optional global or per product handling time in a human readable text string i.e. 1 day.  Per product setting overrides global setting

**Send Backorder Notification**
Send an email notification to the sales representative when an item is backordered

**Backorder Notification Template**
Template to use when sending the backorder notification email.  Default template supplied or can be modified by creating your own transaction email template

Support
-------
If you have any problems with this extension, open an issue on GitHub

Contribution
------------
Contributions are welcomed, just open a [pull request on GitHub](https://help.github.com/articles/using-pull-requests).

Developer
---------
Jonathan Hussey
[http://www.husseycoding.co.uk](http://www.husseycoding.co.uk)
[@husseycoding](https://twitter.com/husseycoding)

Licence
-------
[OSL - Open Software Licence 3.0](http://opensource.org/licenses/osl-3.0.php)

Copyright
---------
(c) 2015 Hussey Coding
