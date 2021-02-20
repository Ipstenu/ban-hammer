=== Ban Hammer ===
Contributors: Ipstenu
Tags: email, ban, registration, buddypress, wpmu, multisite
Requires at least: 5.5
Tested up to: 5.7
Stable tag: 2.8
Donate Link: https://ko-fi.com/A236CEN/
License: GPLv2

Prevent people from registering via your disallowed comment keys.

== Description ==

We've all had this problem: a group of spammers from `mail.ru` are registering to your blog, but you want to keep registration open.  How do you kill the spammers without bothering your clientele?  While you could edit your theme's `functions.php` and block the domain, once you get past a few bad eggs, you have to escalate.

Ban Hammer helps you do that by preventing unwanted users from registering.

On a single install of WordPress, instead of using its own database table, Ban Hammer pulls from your list of prohibited emails from the Disallowed Comment Keys feature, native to WordPress.  Since emails never equal IP addresses, it simply skips over and ignores them.

On a network instance, there's a network wide setting for banned emails and domains. This means you only have <em>one</em> place to update and maintain your blocked list.  When a listed user attempts to register, they get a customizable message that they cannot register.

For advanced documentation, including how to use on WooCommerce, please visit [the Ban Hammer Wiki](https://github.com/Ipstenu/ban-hammer/wiki).

* [Development](https://github.com/Ipstenu/ban-hammer/)
* [Wiki](https://github.com/Ipstenu/ban-hammer/wiki)
* [Donate](https://ko-fi.com/A236CEN/)

= Privacy Policy =

This plugin does not track data outside of what WordPress already collects. It utilizes the submitted email address to validate the domain and compares it to the list of prohibited domains and emails. No additional data is processed.

= Credits =

Ban Hammer is a very weird fork of [Philippe Paquet's No Disposable Email plugin](http://www.joeswebtools.com/wordpress-plugins/no-disposable-email/). The original plugin was a straight forward .dat file that listed all the bad emails (generally ones like mailinator that are disposable) and while Ban Hammer doesn't do that, this would not have been possible without that which was done before.

Many thanks are due to WP-Deadbolt, for making me think about SQL and TTC for StopForumSpam integration. MASSIVE credit to Travis Hamera for the StopForumSpam/cURL fix! And then props to Helen Hou-Sandí for not using curl at all. Protip? Use <a href="http://planetozh.com/blog/2009/08/how-to-make-http-requests-with-wordpress/">WP_http</a> instead!

==Changelog==

= 2.8 =
* February 2021 by Ipstenu
* Removing check for if registration is active

== Installation ==

<strong>Single Site (Traditional) Install</strong>

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

= If I change the Blocklist via Ban Hammer, will it change the Disallowed Comment Keys? =

On **single site installs**, yes. They are the exact same list, they use the same fields and they update the same data. The only reason I put it there was I felt having an all-in-one place to get the data would be better.

= Does this list the rejected registers? =

No. Since WordPress itself doesn't list rejected comments, the rejected users are similarly lost forever.

= Where did Stop Forum Spam go? =

This plugin no longer uses Stop Forum Spam. If you need that feature, please use <a href="http://wordpress.org/extend/plugins/stop-spammer-registrations-plugin/">Stop Spammer Registrations</a> instead. They did it way better.

= Does this work on MultiSite? =

Yes it does, but a little differently. If you're using multisite, instead of pulling from the Disallowed Comment Keys (which is per site), you have a separate list off Network Admin -> Settings. This is because you only want to have the network admins determining who can register on the network.

= Does this work on BuddyPress? =

I believe so.

= Does this work on WooCommerce? =

You have to make your own hook because WooCommerce doesn't use the normal registration functions. Don't panic. I have [directions here](https://github.com/Ipstenu/ban-hammer/wiki#woocommerce).

= Can I block partials? =

Yes but not wildcards. If you put in `viagra` for example, you will block `viagrajones@gmail.com` _and_ `john@viagra.com` so please use this carefully. If you put in `cookie` then you'll block `cookiemonster@sesamestreet.edu` and everyone would be sad.

If you want to block everyone from all subdomains (like `joe@bar.example.com`) then you can block `.example.com` and that will block all the subdomains.
