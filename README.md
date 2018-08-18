Manage all RSS feeds
====================

[![Build Status](https://api.travis-ci.org/LafColITS/moodle-tool_rssfeeds.png)](https://api.travis-ci.org/LafColITS/moodle-ttool_rssfeeds)

This admin tool allows managers to view all RSS feeds on a Moodle site. It shows who owns the feed, which courses have the feed exposed via an RSS block, and gives the manager the option to globally delete the feed.

Requirements
------------
- Moodle 3.5 (build 2017051500 or later)

Installation
------------
Copy the rssfeeds folder into your /admin/tool directory and visit your Admin Notification page to complete the installation.

Usage
-----
The tool is accessed via Site administration > Reports > Manage all RSS feeds. Each feed will list the owner, courses which have it added via a block, and provide a deletion link. Unlike the core rss feed deletion, this will also reprocess the configuration data for each block instance.

Configuration
-------------
The tool has no options.

Author
------
Charles Fulton (fultonc@lafayette.edu)
