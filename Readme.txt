=== WordPress Security Guard ===
Contributors: themology
Donate link: http://themology.net/donate
Tags: website defender, security scan, 0-day, 0day, attack, authentication, chmod, exploit, hack, hackers, password, protect, script kiddie, security, shellshock
Tested up to: 4.2.1

Don't let script kiddies hack your site!


== Description ==

WordPress Security Guard helps to stay safe and prevent downtime due to security issues. It takes less than a minute for WordPress Security Guard to perform the scan after which you'll immediately see the color coded results along with links to detailed explanation of the problem and ways to fix it.

**Some awesome features:**

* Perform security tests including brute-force attacks
* Check your site for security vulnerabilities and holes
* Checks for Timthumb vulnerability
* Take preventive measures against attacks
* Prevent 0-day exploit attacks
* Checks for Shellshock server bug
* Use included code snippets for quick fixes
* Extensive help and descriptions of tests included


**Don't forget to say Thanks to <a href="http://themology.net">Themology</a> and also don't forget to share!**



**Sources and Credits**

* **jQuery ScrollTo plugin**
	(c) Ariel Flesler http://flesler.blogspot.com/
	Dual licensed under the MIT and GPL licenses

* **jQuery blockUI plugin**
	Copyright (c) M. Alsup
	http://malsup.com/jquery/block/ Dual licensed under the MIT and GPL licenses


== Installation ==

This section describes how to install the plugin and get it working.

1. Download the ZIP package.
2. Open WordPress admin and go to Plugins -> Add New -> Upload. Browse for the ZIP file security-guard.zip on your computer and hit “Install Now”.
3. Activate the plugin.

**Usage tutorial**

1. Open Tools => Security Guard
2. Click "Run Tests" to analyze your site

== Frequently Asked Questions ==

= Will this plugin slow my site down? =

Absolutely not. You may experience a slight slow down while tests are being run but that takes less than a minute.

= Will it work on my theme? =

Sure! Security Guard works with all themes.

= Will it work with my plugins? =

Sure! Security Guard works with all plugins.

= What changes will Security Guard make to my site? =

None! Security Guard will just give you the test results and suggest corrective measures with precise instruction. It will not make any changes to your site.

= Is this plugin safe to use? =

Of course. It's just a reporting tool.

= Is this plugin legal to use? =

Yes. It's your site you can do whatever you want with it. Running tests on other people's sites is illegal but Security Guard can only perform tests on the WP it's installed on.

== Screenshots ==

1. Admin of Security Guard, after running a test.

== Usage ==

Security Guard contains 30+ separate security tests. Once you click the "Run Tests" button all tests will be run. Depending on various parameters of your site this can take from ten seconds to 2-3 minutes. Please don't reload the page until testing is done.

If no test results show up after the page reloads or you get a "Bad AJAX response" please configure max script execution time.

Each test comes with a detailed explanation which you should use to determine whether it affects your site or not. Most test have simple to follow instructions on how to strengthen your site's security. Please read the instruction carefully and follow them only if you feel comfortable doing so.

**Configuring maximum script execution time**

In order to minimize the impact on your server Security Guard has a limit on the maximum number of seconds it's tests can run. If you have a very slow server there may be a need to increase this limit.

If you want to increase this limit open security-guard.php and change the line #21 which defines it.

<code>
// maximum number of seconds tests are allowed to run
define('THEMO_SG_MAX_EXEC_SEC', 200);
</code>

**Configuring maximum number of user accounts to perform brute-force attacks on**

By default Security Guard tests only the first 25 users (starting from administrators) when doing brute-force attacks. This limit is imposed to be sure we don't kill the database while doing the attack.

If you want to test more or all users open security-guard.php and change the line #20 which defines this limit.

<code>
// maximum number of user accounts that are brute-force tested for weak passwords
define('THEMO_SG_MAX_USERS_ATTACK', 25);
</code>