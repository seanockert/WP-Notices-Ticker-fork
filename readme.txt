=== Notices Ticker ===
Contributors: peterwsterling
Donate link: http://www.sterling-adventures.co.uk/blog/2008/06/01/notices-ticker-plugin/
Author URI: http://www.sterling-adventures.co.uk/
Plugin URI: http://www.sterling-adventures.co.uk/blog/2008/06/01/notices-ticker-plugin/
Tags: notes, notices, message, ticker
Requires at least: 2.5
Tested up to: 3.4.1
Stable tag: trunk

This plug-in allows short notices to be displayed on your blog.

== Description ==

This plug-in allows short notices to be scrolled (ticker-tape style) or faded (in and out) on your blog.  Each message is valid for a set number of days (0 = no expiry) from its creation (or update) date and can also be de-activated to keep it from being displayed until you are ready to let the world see it.

There are two ways to display the notices:
- Using a simple sidebar widget.
- Embed the ticker into one (or more) of your theme’s template files.

== Installation ==

* Put the complete plug-in folder into your WordPress plug-in directory (if this doesn’t make sense it probably isn’t something you should be trying) and activate it.
* Define the text of your notices using the <b>Notices</b> form at <i>Tools &raquo; Notices</i>. Note that HTML is allowed in the text but be careful to avoid the <code>"</code> (double quote) character, use <code>'</code> (single quote) instead. Each notice has these attributes:
- <i>Notice</i> text (the notice itself).
- The number of days the notice will be <i>valid</i> for.
- Once created, a checkbox indicating if the notice is <i>active</i>.
- The <i>date</i> is the date the notice is valid from and is the date the notice was created (or last updated).
* Use the Notices widget (<i>Appearance &raquo; Widgets</i>) to show a sidebar widget that scrolls the chosen number of the most recent notices. The widget has just two options - the first (repeated from the main <i>Settings &raquo; Notices</i> page) the number of notices to show, and the second, a title for the widget.
* Use this <code>&lt;?php put_ticker( [true | false] ); ?&gt;</code> in your theme’s template files. Where <code>true</code> or <code>false</code> determines if the ticker should be hidden when there are no notices to scroll.
For example, <code>&lt;?php put_ticker(false); ?&gt;</code> only shows the ticker when there are notices to scroll, whereas <code>&lt;?php put_ticker(true); ?&gt;</code> always shows the ticker - even an empty one.
* There is a small CSS document (<code>notice.css</code>) in the plug-in folder to help style your notices ticker. The three styles defined are:
- <code>.ticker</code> - style for the text used in the notices.
- <code>.ticker img</code> - style for images (use <code>&lt;img src=... /&gt;</code> in the notice text) in the notices. The example lines images up with the bottom of the notice text.
- <code>.ticker-div</code> - style for the ticker container.
