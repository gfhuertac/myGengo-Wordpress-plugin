=== MyGengo ===
Contributors: gfhuertac
Link: http://www.mygengo.com/
Tags: translate, translation, language, international, revenue
Requires at least: 2.7
Tested up to: 3.0.3
Stable tag: 1.0

Simple Human Translation for your blog

== Description ==

myGengo is the revolutionary new way to order translations online. We get rid of the hassle, providing you with accurate, timely translations at an unbeatable price.

We are backed by a global team of 1200+ qualified translators. Our interface is simple and easy to use. Just enter your text and go!

[Homepage](http://translatemyblog.com/)

[Live Demo](http://www.pamahres.com/wordpress/)

Note that you can deactivate the service at any time, and keep any existing translations.

== Installation ==

Installation instructions:

1. **Download** the plugin here.
1. **Install Plugin** Go to Plugins > Add New.  Click Browse, and select "mygengo_plugin.zip" for upload.  Click "Install Now"..
1. **Activate plugin** Once the upload is complete, click "Activate Plugin"
1. **Install widgets** Go to Appearance > Widgets in the left sidebar of your Dashboard.  Drag the two "Translations" widgets to your sidebar and click "Save Changes".  
1. **Add code** to your templates (recommended).  You can display myGengo translation and/or order any translation in any part of your code!! It is easy and convenient. Check the instructions below.
1. **Add myGengo keys** in your account. Log-in and go to the administration panel. You'll see a myGengo widget where you can add your settings.

**Code to add to your site**:

The easiest way to integrate myGengo to your site is by adding short codes to your posts/pages or anything you want.
There are two types of short codes:
1. **mygengo_st** that shows the translation to the element you desired
2. **mygengo_t4e** that allows you to add a translation to textarea

The former sort code can be used with any text inside a 'div' tag.
You only need to add the following short code next to your element:
`[mygengo_st post_type="post" post_id="" `

You can also add links to translations (based on translatemyblog code):

To display translation links under the title of the post (where they'll be more visible to users), go to Appearence > Editor, edit your Main Index Template (index.php) and insert

`<div><?php if(function_exists('mygengo_display_translations')) {mygengo_display_translations($id);} ?></div>`

...before the line <div class="entry"> or after the post title.

To display links back to the original post on the translation page (along with links to translations in other languages) edit your Single Post (single.php) template and insert

`<div><?php if(function_exists('mygengo_display_translations')) {mygengo_display_translations($id);} ?></div>
<div><?php if(function_exists('mygengo_display_parent_link')) {mygengo_display_parent_link($id);} ?></div>`

...before the line <div class="entry"> or after the post title.

== Frequently Asked Questions ==

== Screenshots ==

1. Translation links in the title of a blog post
2. Translation links in a sidebar widget
