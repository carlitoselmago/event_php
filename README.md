# event_php
A class for making simple register for events with tracking landing pages

## Notes

Some extra template files will be required, but they will create automatically and show their path
Also database table will be created automatically from the fields in settings.xml

## Important

The event_php folder has a .htaccess that prevents accessing to xml files (for security reasons) if the web is on a nginx server do the equivalent in the .conf file of the website

## TODO:
- Add createdon field in _users in the createifnotexists
- Develop an easier implementation of php-web-analytics

## Instructions

- Clone/download the event_php respository in the root of the website, IMPORTANT: this is not meant to be the root itself, it should stay as a folder of /, and index.php should be created at the root, loading the class from the event_php folder

- Create a file for custom css
/template/template.less

- Create a file for custom js
/template/template.js

- create a index.php with something like this

```
<?php 
include_once("event_php/event.php");
$E=new Event();

$E->HTML->head();

//your content here

$E->form("REGÍSTRATE");

//Other infos
$E->program();

$E->HTML->bottom();
?>
```

- Create the files in the eventphp root settings.xml and program.xml, use the templates as reference

- Create a .htaccess at root so the ics files get processed with this:
```
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /
    RewriteRule ^index\.php$ - [L]
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule . /index.php [L]
</IfModule>
```
