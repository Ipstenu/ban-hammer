=== Ban Hammer ===
Contributors: Ipstenu
Tags: email, ban, registration, buddypress, wpmu, multisite
Requires at least: 4.0
Tested up to: 4.7
Stable tag: 2.6
Donate Link: https://store.halfelf.org/donate/
License: GPLv2

Prevent people from registering via your comment moderation blacklist.

== Description ==

We've all had this problem.  A group of spammers from mail.ru are registering to your blog, but you want to keep registration open.  How do you kill the spammers without bothering your clientele?  While you could edit your `functions.php` and block the domain, once you get past a few bad eggs, you have to escalate.

Ban Hammer does that for you by preventing unwanted users from registering.

On a single install of WP, instead of using its own database table, Ban Hammer pulls from your list of blacklisted emails from the Comment Blacklist feature, native to WordPress.  Since emails never equal IP addresses, it simply skips over and ignores them. On a network instance, there's a network wide setting for banned emails and domains. This means you only have <em>one</em> place to update and maintain your blacklist.  When a blacklisted user attempts to register, they get a customizable message that they cannot register.

For advanced documentation, including how to use on WooCommerce, please visit [the Ban Hammer Wiki](https://github.com/Ipstenu/ban-hammer/wiki).

* [Donate](https://store.halfelf.org/donate/)

= Credits =

Ban Hammer is a very weird fork of [Philippe Paquet's No Disposable Email plugin](http://www.joeswebtools.com/wordpress-plugins/no-disposable-email/). The original plugin was a straight forward .dat file that listed all the bad emails (generally ones like mailinator that are disposable) and while Ban Hammer doesn't do that, this would not have been possible without that which was done before.

Many thanks are due to WP-Deadbolt, for making me think about SQL and TTC for StopForumSpam integration. MASSIVE credit to Travis Hamera for the StopForumSpam/cURL fix! And then props to Helen Hou-Sandí for not using curl at all. Protip? Use <a href="http://planetozh.com/blog/2009/08/how-to-make-http-requests-with-wordpress/">WP_http</a> instead!

==Changelog==

= 2.6 =
* February 2017 by Ipstenu
* Allow redirection to custom URLs on failed login.
* Move plugin to Settings API
* Combine options
* Fixed BuddyPress
* Optimized multisite
* Removed check for WP 3.4 (only 4.0 and up get updates anyway)

== Upgrade Notice ==
Version 2.6 is a major update. You MAY need to reset your error messages in some situations.

== Installation ==

<strong>Single Install</strong>

After installation, go to **Tools > Ban Hammer** to customize the error message (and banned emails, but it's the same list from your comment moderation so...).

<strong>Multisite</strong>

After installation, go to **Network Admin > Settings > Ban Hammer** to customize the error message and banned email list. This will ban users network wide.

== Screenshots ==

1. Default Error message
2. Admin screen
3. Ban Hammer Users
4. Users Menu, with Spammer Flag on
5. BuddyPress Error message

== Frequently Asked Questions ==

= If I change the blacklist via Ban Hammer, will it change the Comment Blacklist? =

On **single site installs**, yes. They are the exact same list, they use the same fields and they update the same data. The only reason I put it there was I felt having an all-in-one place to get the data would be better.

= Does this list the rejected registers? =

No. Since WordPress doesn't list rejected comments (your blacklist goes to a blackhole), the rejected users are similarly lost forever.

= Where did Stop Forum Spam go? =

This plugin no longer uses Stop Forum Spam. If you need that feature, please use <a href="http://wordpress.org/extend/plugins/stop-spammer-registrations-plugin/">Stop Spammer Registrations</a> instead. They did it way better.

= Does this work on MultiSite? =

Yes it does, but a little differently If you're using multisite, instead of pulling from the comment blacklist (which is per site), you have a separate list off Network Admin -> Settings. This is because you only want to have the network admins determining who can register on the network.

= Does this work on BuddyPress? =

Currently yes.

= Does this work on WooCommerce? =

Yes but... You have to make your own hook because WooCommerce doesn't use the normal registration functions. Don't panic. I have [directions here](https://github.com/Ipstenu/ban-hammer/wiki#woocommerce).

= Can I block partials? =

Yes but not wildcards. If you put in `viagra` for example, you will block `viagrajones@gmail.com` _and_ `john@viagra.com` so please use this carefully. If you put in `cookie` then you'll block `cookiemonster@sesamestreet.edu` and everyone would be sad.

If you want to block everyone from all subdomains (like `joe@bar.example.com`) then you can block `.example.com` and that will block all the subdomains.

= Why doesn't this work AT ALL on my site!? =

I'm not sure. I've gotten a handful of reports from people where it's not working, and for the life of me, I'm stumped. So far, it looks like Zend and/or eAccelerator aren't agreeing with this. If it's failing, please post on the wp.org forums with your server specs (PHP info, server type, etc) and any plugins you're running.


