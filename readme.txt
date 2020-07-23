=== Tempus Fugit Intranet ===
Contributors: Benoît Huftier
Tags: intranet
Requires at least: 5.1
Tested up to: 5.2
Requires PHP: 7.3
Stable tag: 1.2.4
 
Intranet to allow every students to add their own datas at home whitout passing by the wordpress admin page.

== Changelog ==

= 1.0.0 =
* First version of the plugin

= 1.1.0 =
* You can add a specific domain for links
* You can add a specific required size for images

= 1.1.1 =
* Add some usefull functions to select users

= 1.1.2 =
* Image are not resize if the size is good. A resize png

= 1.1.3 =
* Add parallax usage for the tempus-fugit site (css and js)

= 1.1.4 =
* Correction of the ugly png resize.

= 1.1.5 =
* The resize has been corrected and add gif support and fatal errors on  the user form

= 1.2.0 =
* Add the filter which allows to get new datas of a user, once send into database. 
* Add files folders, to sort files inside the upload directory

= 1.2.1 =
* Add color and number field types

= 1.2.2 =
* Add multiple fields type

= 1.2.3 =
* Some fixes

= 1.2.4 =
* Multiple fields are not send when the value didn't change

== How to use ==

= After installation =
First, you have to create a new page with the Tempus Fugit Intranet Template.
To begin you can put the title and on content write "[tfi_user_form]". This is the shortcode to display the form on the intranet page.
After doing that, go on Settings > Tempus Fugit Intranet
Choose your new page on the General user options and submit.
You are now able to use the plugin.

= Intranet User Page =
Once a page is set in the option, you can do a shortcode (see 'Options customization' section below to know how customize it), by default CTRL + ALT + L.
It will show you a form connection or another message depend if you're connected or not.
If you don't have access to intranet, add you to the users before (see 'Options customization' below)
If you are the admin which acivate the plugin, you're automatically set to the user list so you can access the page.
Once you are connected, you can see the intranet form and submit your own datas.

= Intranet Users =
To add a new user, you simply need to give him the 'access_intranet' capability.
By default admins have this capability and the new 'Intranet user' role.

= Options customization =
Inside the Settings > Tempus Fugit Intranet page, you have multiple options that you can customize
* The shortcode to display the connection form
* The intranet user page
* The user types
	* To delete a user type just check the box
	* To add a user type add their name separated by '%' inside the option field
* New fields, you can choose :
	* The slug -> It NEEDS to be UNIQUE. Be careful once you have a lot of datas saved on a field, do not change this value, because the field will be reset for all users (but if you change it again, values will be back)
	* The name -> Name to display on the form for this field
	* The type -> You can choose between some field types (text, link, image, multiple...)
	* The default value -> The first value which is set in database while users don't change it
	* Then choose which user type will be able to have this field
	* For some type field, you can have special parameters which will be displayed. Do not hesitate to hover them to have an explanation.
* New users :
	* The user name (don't choose a user which is already in the list because it won't change). You can only choose a user with the 'access_intranet' capability (see 'Intranet Users' section above)
	* The user type to know which field he will have.
	* Special fields -> Fields that the user type do not have but you want that this specific user have them

Do not forget to submit (Be careful you have 2 submit buttons !)

= Use datas =
Now the display part : how to use datas on you site ? You just have to use the shortcode '[tfi_user_data]'.
This shortcode can have some attributes :
* user_id -> the id of the wanted user
* user_slug -> the slug of the wanted user
* field -> [mandatory] the field to get datas.

If user_id is set, the user_slug is useless
If neither user_id or user_slug are set, the user will be the current one (it means the user which is on the site or nothing happend if he is not register)

= Actions =
You can use one action calls 'tfi_user_datas_changed' which accept 3 arguments.
* The first is the id of the user which changed his data.
* The second is an array of all fields which changed, with all their options.
* The third is an array of all values for each fields.

= Other points =
* The upload max size of a file is, by default, 2 Mb on most wordpress site. So to allow higher image size, you need to change your server configuration about upload file max size.
* The max file upload is 20 by default on a server. When using more than 20 files, you need to change it or only the 20 first files will be updated (when you use echo plugin for example).
* The max input vars is 1000 by default (variable which can be send in post method). You need to increase this number if you have a lot of users and fields to update in the admin panel.