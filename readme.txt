=== Music Store ===
Contributors: codepeople
Donate link: http://wordpress.dwbooster.com/content-tools/music-store
Tags: ecommerce, e-commerce, audio, paypal, music, shop
Requires at least: 3.0.5
Tested up to: 3.5.1
Stable tag: 1.0.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Music Store is an online store for selling audio files: music, speeches, narratives, everything audio. With Music Store your sales will be safe, with all the security PayPal offers.

== Description ==

**Music Store** is an online store for selling audio files: music, speeches, narratives, everything audio. With **Music Store** your sales will be safe, with all the security PayPal offers.

**Music Store** protects your audio files, preventing them from being downloaded without permission.

**Music Store** includes an audio player compatible with all major browsers: Internet Explorer, Firefox, Opera, Safari, Chrome and mobile devices: iPhone, iPad, Android. The **Music Store** audio player supports the following file formats: MP3, WAV, WMA and OGA.

**Features:**

*	Allows selling audio files via PayPal.
*	Allows selling collections. Audio files can be grouped into collections or albums.
*	Allows a custom setup of the online store, with ability to filter products by types of files: Singles or Collections, paging and sorting the results by popularity.
*	Allows to associate additional information to the products. 
*	Includes an audio player that supports formats: OGA, MP3, WAV, WMA.
*	Offers secure Audio Playback that prevents unauthorized downloading of audio files.
*	Supports all most popular web browsers: Internet Explorer, Firefox, Chrome, Safari, Opera, and mobile devices such as iPhone, iPad and Android. For older browsers, the media player provides support for Flash and Silverlight.
*	Includes a module to track sales statistics.

If you want more information about this plugin or another one don't doubt to visit my website:

[http://wordpress.dwbooster.com](http://wordpress.dwbooster.com "CodePeople WordPress Repository")

== Installation ==

**To install Music Store, follow these steps:**

1. Download and unzip the plugin
2. Upload the entire "music-store" directory to the "/wp-content/plugins /" directory
3. Activate the plugin through the 'Plugins' menu in "WordPress"
4. Go to Settings > Music Store and set up your store. 

== Interface ==

**Setting up Music Store**

Music Store can be set up via the menu: "Settings / Music Store". The setup screen offers general settings for the Music Store, allows to enter PayPal data to process sales, and texts necessary for email notifications.

**Settings Interface**

The setup interface  includes the following fields:

*   Music Store URL: Enter the URL of the webpage where the Music Store is inserted. The URL of the store will be used to return from the product page to the store page.
*   Allow to filter by type: Inserts a field into the Music Store webpage that allows to filter products by type (including Singles, Collections or all products)
*   Allow to filter by genre: Inserts a field into the Music Store webpage that allows to filter products by their genre.
*   Allow multiple pages: Allows paging of music store products.
*   Items per page: Defines the number of products per page in the Music Store.
*   Player style: Select the audio player style from the list.

**Payment gateway data**

*   Enable PayPal Payments: Allows the sale of products through PayPal.
*   PayPal email: Enter the email address associated with the PayPal account.
*   Currency: Symbol of the currency in which payments are accepted.
*   PayPal language: Preferred language of the PayPal interface. 
*   PayPal button: Select the PayPal button design. 

**Notification Settings**, both for buyers to complete a payment, and the store manager
 
*   Notification "from" email: E-mail address that will appear as the sender of notifications.
*   Send notification to email: Email address where a notification is sent after each sale.
*   Subject of user confirmation email: Subject of the confirmation email sent to the customer when making the purchase.
*   Email confirmation to user: Body of message sent to the client when making the purchase. The message should include the tag  %INFORMATION% which will be replaced by the purchase data.
*   Subject of email notification to admin: Subject of email notification sent to the administrator when a purchase is made.
*   Email notification to admin: Body of the email message sent to the administrator when a purchase is made. The message text should include the tag  %INFORMATION%, which will be replaced by the purchase data.

**Creating content**

Two types of products can be sold through the Music Store: songs or collections.

**Creating songs**

To enter a song in the store please press the menu option "Music Store Song" to open the relevant section. Initially it displays the list of songs entered previously and a set of data associated with the song (screenshot-2)

To enter a new song press "Add New".

The interface for entering data pertaining to a song is described below (screenshot-3):

*   Enter Title Here: Enter the title of the song.
*   Description: Description of the song. This field is optional, but offers the opportunity to provide additional information about the song or the authors.
*   Sales Price: Retail price of the song.
*   Comes as a single: To allow sale of song as a single, mark the checkbox. If the checkbox is left unchecked, the song can only be sold as part of a collection.
*   Audio file for sale: URL of the audio file to sell. The button associated with the field displays the WordPress media gallery making it easy to select the file.
*   Audio file for demo: URL file audio demo. The button associated with the field displays the WordPress media gallery making it easy to select the file.
*   Protect File: Checkbox that enables secure playback of the song to avoid being downloaded while testing. The safe playback is created by cutting the track and not allowing it to download completely. Users who try to steal audio files, only get a snippet of the song.

Note: If a song is not defined as demo but the Protect File field is marked, then this file will also be used for demo purposes.

*   Artist: Select the artist (or artists) from the list or enter a new one if it is not yet on the list.
*   Album including the song: Select the album or albums where the song is included or enter a new one.

Note: The album field is purely informative and has no impact on collections for sale. 

*   Cover: URL of the cover image. The button associated with the field displays the WordPress media gallery making it easy to select the file.
*   Duration: Enter the duration of the song.
*   Publication Year: Enter the year of the song.
*   Additional Information: URL of a webpage with additional information about the song.

The column on the right includes a form to enter the song's genre.

**Creating collections**

To enter a song in the store, please press the menu option "Music Store Collection" to open the relevant section. It initially displays the list of collections entered previously, as well as a set of data associated with the track (screenshot-4)

To enter a new song press the "Add New".

Collection setup interface: (screenshot-5):

*   Enter Title Here: Enter the title of the collection.
*   Description: Description of the collection. This field is optional, but provides the opportunity to enter additional information on the collection or authors.
*   Sales Price: Retail price of the collection.
*   Songs of collection: Select songs to be sold as part of the collection. The songs must have been previously defined in the section of songs and be public. If the song is still being edited,  it can not be added to the collection.
*   Artist: Select the artist (or artists for the collection) from the artists list or enter a new one if it is not yet on the list.
*   Cover: URL of the cover image. The button associated with the field displays the WordPress media gallery making it easy to select the file.
*   Publication Year: Enter the year of the collection in case it represents an album.
*   Additional Information: URL to a webpage with additional information on the collection.

The column on the right includes a form to enter the collection’s genre.

**Publishing the Music Store**

The Music Store can be posted on a page or post of WordPress. To insert the Music Store go to the relevant section (page or post) and select where you want the Music Store, or create a new page / post.

In the editing section of the page/post, press the Music Store insertion button (screenshot-6), the action displays a setup screen (screenshot-7)

**Interface for insertion dialog**

*   Filter results by products type: by default, displays only products that belong to a specified type.
*   Columns: Defines the number of columns for the store products.
*   Filter results by genres: By default, displays the products filtered by specified genre.
*   Filter results by artist: Displays the products filtered by artist.
*   Filter results by album: Displays the products filtered by album.

The insertion process generates a shortcode which will be replaced by the store when it is displayed on the website.

Note: After inserting the store on a page of your WordPress, it is advisable to copy the URL of the relevant page, and enter in the Music Store's setup section, to allow the users to return to the store from the product page.

**Sale Statistics**

When a sale takes place, a notification email is sent to the Music Store administrator. However, sales can also be reviewed in Sales Reports. To do this, go to the stores' setup page: "Settings / Music Store" and once there, open the section "Sales Reports" (screenshot-8)

The Reports section allows you to filter sales reports over a specific period, by default it shows the current day's sales. It also shows sales' totals for the selected period and the currency of the sales (screenshot-9)

You can delete a sales report from the list of sales. This may be useful in case of a refund granted to a buyer, and allows to keep your sales statistics updated with the actual purchases.

== Frequently Asked Questions ==

= Q: Why the sales button don't show? =

A: First, go to the settings page of music store and be sure the PayPal checkbox is checked, and has defined the seller's email. Second, in case of collections, be sure the collection has a price defined and songs associated. Third, in case of songs, be sure the song has a price defined and a audio file associated.

= Q: Why the song don't displays on music store? =

A: If you want to sale a song as a single, it is required to check the "Sell as a single" checkbox in the song data form.

= Q: Why the audio file is played partially? =

A: If you decide to protect the audio file, the audio file is played partially in demo to avoid its copy by users and softwares unauthorized.

== Screenshots ==
1. Music Store Item
2. Music Store Song Section
3. Song Edition Interface
4. Music Store Collection Section
5. Collection Edition Interface
6. Music Store Insertion Button
7. Music Store Insertion Interface
8. Sales Reports
9. Filtering Sales Report