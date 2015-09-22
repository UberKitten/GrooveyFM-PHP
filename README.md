# GrooveyFM
## Old PHP Version

A PHP application I made in 2011-2013 to sync radio station playlists with Grooveshark. Scraped playlists from radio stations ([example playlist](http://www.mediabase.com/whatsong/whatsong.asp?var_s=087082087068045070077)). This was one of my last PHP applications, as I quickly realized that C# was much easier to develop complicated programs in. I [partially completed a rewrite in C#](https://github.com/T3hUb3rK1tten/GrooveyFM).

Alas, [Grooveshark closed in May 2015](http://www.inquisitr.com/2056893/grooveshark-forced-to-close-due-to-courts-ruling/).

Code is organized for [NearlyFreeSpeech.NET](https://www.nearlyfreespeech.net/)'s directory structure. While it was running Grooveshark severely limited the use of their API. I could search songs about ~30 times per hour. This meant that the application had a fairly complex and annoying caching system, duplicating Grooveshark's database.
