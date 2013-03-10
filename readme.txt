=== Ban Hammer ===
Contributors: Ipstenu
Tags: email, ban, registration, buddypress, wpmu, multisite
Requires at least: 3.4
Tested up to: 3.6
Stable tag: 2.2
Donate Link: https://www.wepay.com/donations/halfelf-wp
License: GPLv2

Prevent people from registering via your comment moderation blacklist.

== Description ==

We've all had this problem.  A group of spammers from mail.ru are registering to your blog, but you want to keep registration open.  How do you kill the spammers without bothering your clientele?  While you could edit your `functions.php` and block the domain, once you get past a few bad eggs, you have to escalate.

Ban Hammer does that for you by preventing unwanted users from registering.

On a single install of WP, instead of using its own database table, Ban Hammer pulls from your list of blacklisted emails from the Comment Blacklist feature, native to WordPress.  Since emails never equal IP addresses, it simply skips over and ignores them. On a network instance, there's a network wide setting for banned emails and domains. This means you only have <em>one</em> place to update and maintain your blacklist.  When a blacklisted user attempts to register, they get a customizable message that they cannot register.

Ban Hammer <em>no longer</em> uses Stop Forum Spam. <a href="http://wordpress.org/extend/plugins/stop-spammer-registrations-plugin/">Stop Spammer Registrations</a> did it so much better, I bow to their genius.

* [Donate](https://www.wepay.com/donations/halfelf-wp)

= Credits =
Ban Hammer is a very weird fork of [Philippe Paquet's No Disposable Email plugin](http://www.joeswebtools.com/wordpress-plugins/no-disposable-email/). The original plugin was a straight forward .dat file that listed all the bad emails (generally ones like mailinator that are disposable) and while Ban Hammer doesn't do that, this would not have been possible without that which was done before.

Many thanks are due to WP-Deadbolt, for making me think about SQL and TTC for StopForumSpam integration. MASSIVE credit to Travis Hamera for the StopForumSpam/cURL fix! And then props to Helen Hou-Sandi for not using curl at all. Protip? Use <a href="http://planetozh.com/blog/2009/08/how-to-make-http-requests-with-wordpress/">WP_http</a> instead!

==Changelog==

= 2.2 =
* 15 January, 2013 by Ipstenu
* Fixed translation bug
* Dropping support for older versions

= 2.1 =
* 26 August, 2012 by Ipstenu
* The return of Multisite
* BuddyPress improvements
* Dropping support for older versions

= 2.0 =
* 30 May, 2012 by Ipstenu
* Removal of Stop Forum Spam.
* Whole plugin was consolidated to run faster, with fewer files, and with contextual help. Egad, I've learned a lot since 2009!

= 1.7 =
* 24 April, 2012 by Ipstenu
* Proper uninstallation.

= 1.6.1 =
* 17 April, 2012 by Ipstenu
* Cleanup. Nothing major here, just documentation and all.

= 1.6 =
* 05 August, 2011 by Ipstenu
* Internationalization.

= 1.5.2 =
* 09 March, 2011 by Ipstenu
* Bugfix.  Typo made it NOT enableable.

= 1.5 =
* 08 March, 2011 by Ipstenu
* Allows for deletion of spammers from the User List (credit mario_7)
* Added optional functionality to show spammer status on the normal users list.
* Moved Ban Hammer Users to the USERS menu (now called 'Ban Hammered')
* Works on BuddyPress!

= 1.4 =
* 16 August, 2010 by Ipstenu
* Checks for presence of the cURL extension. If not found, the option to use StopForumSpam is removed. (using http://cleverwp.com/function-curl-php-extension-loaded/ as recommended by kmaisch )

= 1.3 =
* 08 July, 2010  by Ipstenu
* Pulling out the WPMU stuff that's never going to happen now that it's MultiSite and doesn't work.

= 1.2 =
* 08 November, 2009  by Ipstenu
* This lists all users marked by StopForumSpam as spammers, if you're using that option (and not if not). (Thanks to obruchez for the suggestion!).

= 1.1 =
* 03 May, 2009 by Ipstenu
* Subversion before coffee = BAD.

=  1.0 =
* 03 May, 2009 by Ipstenu
* First public version.

=  0.3 =
* 30 March, 2009 by Ipstenu
* The error message is customizable.
* Added support for StopForumSpam.com
* Added in checkbox to use StopForumSpam (default to NO).
* Cleans up after itself on deactivation (deletes the banhammer_foo values from the wp_options table because I HATE when plugins leave themselves).

=  0.2 =
* 29 March, 2009 by Ipstenu
* Shifted to use the WordPress comment blacklist as source. This was pretty much an 80% re-write from NDE's basis, keeping only the basic check at registration code.

=  0.1 =
* 28 March, 2009 by Ipstenu
* First release using No Disposable Email's .dat file as a source.

== Upgrade Notice ==

= 2.0 =

This plugin no longer uses Stop Forum Spam. If you need that feature, please use <a href="http://wordpress.org/extend/plugins/stop-spammer-registrations-plugin/">Stop Spammer Registrations</a> instead. They did it way better.

= 1.5.2 =

Fixes problem with enabling NOT working at all. *sigh*

== Installation ==

<strong>Single Install</strong>
After installation, go to **Tools > Ban Hammer** to customize the error message (and banned emails, but it's the same list from your comment moderation so...).

<strong>Multisite</strong?
After installation, go to **Network Admin > Settings > Ban Hammer** to customize the error message and banned email list. This will ban users network wide.

== Screenshots ==

1. Default Error message
2. Admin screen
3. Ban Hammer Users
4. Users Menu, with Spammer Flag on
5. BuddyPress Error message

== Frequently Asked Questions ==

= If I change the blacklist via Ban Hammer, will it change the Comment Blacklist? =

Yes! They are the exact same list, they use the same fields and they update the same data.  The only reason I put it there was I felt having an all-in-one place to get the data would be better.

= Does this list the rejected registers? =

No.  Since WordPress doesn't list rejected comments (your blacklist goes to a blackhole), I didn't bother with trying to do that here. If enough people think it's a need, I may consider it.

= Where did Stop Forum Spam go? =

This plugin no longer uses Stop Forum Spam. If you need that feature, please use <a href="http://wordpress.org/extend/plugins/stop-spammer-registrations-plugin/">Stop Spammer Registrations</a> instead. They did it way better.

= Does this work on MultiSite? =

Surprise! Yes! If you're using multisite, instead of pulling from the comment blacklist (which is per site), you have a separate list off Network Admin -> Settings. This is because you only want to have the network admins determining who can register on the network.

= Does this work on BuddyPress? =

Yes. Caveat: I have not fully tested with Multisite and BuddyPress, so I want to warn you that it doesn't always give the pretty error message. It does ban hammer them, though, so ... yay?

= Can I block partials? =

You can block by domain by entering @example.com, but you cannot block all .com emails. This is because of the crossover between the Blacklist and the Ban List. Say, for example, you want to block the word cookie from being said in comments. If you did that, Ban Hammer would block cookiemonster@sesamestreet.org too!

= Why doesn't this work AT ALL on my site!? =

I'm not sure. I've gotten a handful of reports from people where it's not working, and for the life of me, I'm stumped. So far, it looks like Zend and/or eAccelerator aren't agreeing with this. If it's failing, please post on the wp.org forums with your server specs (PHP info, server type, etc) and any plugins you're running.