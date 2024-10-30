=== Free Net of Moderators ===
Contributors: moderateit
Donate link: https://moderate-it.net/en/donate.php
Tags: comments, moderation, moderator, offtop, antispam, spam, flood, insults, discussion, check
Requires at least: 4.6
Tested up to: 5.3.2
Stable tag: 5.2
Requires PHP: 5.6
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

== Description ==

Maintaining a culture of online communication in the hands of the users themselves. 

The plugin allows you to reduce the load on a moderator of your site. Pre-moderation of comments available for you: new comments added by users through the standard WordPress comment field, will be sent for review to network of moderators.  Post-moderation of comments is also possible: this means that any user can send any violating comment from the comment feed for review to the network. Based on the results of this review, the comment status will be set to Approved or Spam.

### _DEMO_
Feel free to use [demo of user moderation](https://moderate-it.net/en/index.php#sec_try).

### _Why ModerateIt plugin?_
- A network of independent moderators can identify what automatic methods still cannot handle  and appointed moderators do not have time to cope. For example offtopic, flood, insults, etc.
- You create conditions for users on your site under which it is not profitable to violate:
    - Unauthorized users comment with pre-moderation.
    - Authorized users, if they do not violate, can comment without pre-moderation.
    - Readers can correct violations using post-moderation.
- A quick introduction to the rules of online communication.
- The plugin allows you to reduce the load on a comments moderator of your site.

### _Terms of Use_
- In the course of its work, the plugin sends a user comment for verification to network of moderators, and also receives the result of verification through [ModerateIt Net API](https://moderate-it.net/en/connect.php). 

- The network of moderators works on the following principle:
**_In short_**:   A user checks a some comment, other users check his comment or comment selected by the user.
**_More_**: With pre-moderation, when a site is connected to the network of moderators, and a user adding a comment to this site, before that him it is proposed to evaluate for violations several comments received from the network. Among them there is comment, the evaluation of which is not yet known. And there are also comments whose network evaluations are known, and by which the network evaluates the objectivity of the user. A biased user is not allowed to add a comment. After an objective evaluation, the user can add his comment, which also can be sent to the network for verification. Post-moderation of comments  working on the same principle. A user can pass any comment to the network for check.  Of course, after checking the comments of others.

- Users data transferred to the network of moderators is not disclosed.

 Read the terms of the [User Agreement](https://moderate-it.net/en/agreement.php) in more detail.
- By default, the plugin joins the network with a free public API key. For permanent work, we recommend getting [a free personal API key](https://moderate-it.net/en/start.php) for your site (access to basic statistics and increased load limits). With the free personal API key you can use the network in order that users post only comments that contain useful information about the topic of discussion! Also a [paid personal API key](https://moderate-it.net/en/rise.php) will allow you to receive additional features: such as extended rule set for post-moderation.
== Installation ==
1. Upload the plugin files to the `/wp-content/plugins/moderateIt directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Use the Settings->Net of Moderators  screen to configure the plugin
4. You can use plugin with not personal api-key: *public_key* for connection to network of moderators.
   But for permanent work we recommended to register free personal api-key for your site 
  (access to basic statistics and increased load limits).

== Frequently Asked Questions ==
You can  read the [most common questions](https://moderate-it.net/en/questions.php).

== Screenshots ==
1. Admin settings for network of moderators.
2. Frontend links for send comment to moderation.
3. Suggestion to a user to moderate a comments before commenting.
4. Example one part of  a moderation task.
5. Message to the user after successful completion of moderation task.
6. Message to the user after not successful completion of moderation task.
7. Message to the user after sending his new or selected by him comment to the network of moderators. 

== Changelog ==
= 1.0 =
* First  version 

== Upgrade Notice ==
= 1.0 =
* First  version 

