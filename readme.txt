=== Tempus Fugit Intranet ===
Contributors: Benoît Huftier
Tags: intranet
Requires at least: 5.1
Tested up to: 5.2
Requires PHP: 7.3
Stable tag: 1.0
 
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
	* The type -> You can choose between 3 types
	* The default value -> The first value which is set in database while users don't change it
	* Then choose which user type will be able to have this field
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

= Other points =
* The upload max size of a file is, by default, 2 Mb on most wordpress site. So to allow higher image size, you need to change your server configuration about upload file max size.