# CAP #
Citizen Archive Platform

The Citizen Archive Platform is an open-source project designed to empower communities and individuals to collaboratively document, upload, and share historical and cultural data with archives and cultural heritage intstitutions for long term preservation. It provides a user-friendly interface for uploading, managing, and categorizing multimedia content such as photos, videos, documents, and audio recordings.

__Key Features__

Technical specifications: CMS WordPress, SIP Plugin
Collaborative Archiving: Multiple users can contribute and curate content within a secure, community-driven environment.
Rich File Support: Upload and manage diverse media types including images, videos, audio, and text documents.
Categorization and Tagging: Organize content using customizable categories and tags for efficient retrieval.
Search and Filtering: Quickly find archived items through search and filtering capabilities.
Data Privacy: Robust data protection measures to safeguard user contributions and data.

__Use Cases__

Community History and Cultural Projects
Digital Preservation Initiatives
Cultural Heritage Institutions
Educational Purpose

# Installation Guide SIP Plugin #  
The WordPress plugin creates a Submission Information Package (SIP) as a ZIP archive from the archived material uploaded by users. 
This contains all files and an XML with all meta information.   
Translations for German, English, French, Italian, and Greek are included. For multilingual websites, we recommend using the Polylang plugin. https://polylang.pro/  
The plugin uses page templates and therefore does not work with block template themes for WordPress.

## Plugin Installation ##

1. Download SIP.zip
2. Go to Plugins -> Add Plugin and Upload the Plugin
3. Activate the SIP Plugin

or

1. Clone the repository in the WordPress plugins folder
2. Activate the SIP Plugin

## Plugin Settings ##
After installation, there is a new menu item in the WordPress admin area:  
__Archival Materials__

### Necessary content ###

__Archive__

An archive or organization must be created. Users and archived items are assigned to these.  
Go to Archival Materials -> Archive and add new. Enter a name for the new Archive. The Description should be the Address of the Archive.  
The Institution Name abbreviation is used to generate the SIP name and the institution logo ist used in the PDF header.

For example  
Name "Stadtarchiv Graz"  
Description "Schiffgasse 4 Graz, Austria"  
Institution Name abbreviation "GRAZ"

__Pages__

4 pages with specific page templates must be created. All of these pages can also contain content. This content is displayed above the plugin output. For example, an info text above the upload form. 

1. Archive - Template "SIP Archive": Lists all archive entries with filters and search functions. The list is only visible to users with the role defined under General Options.
2. View - Template "SIP Archival": Shows all files and metadata of an archive entry. Should be a subpage of the "Archive" page.
3. Submission - Template "SIP Upload": Shows the upload form.
4. My Profile - Template "SIP Profile": Shows user archive entries and userdata. Users can list their submissions, drafts and edit personal information or change their password.  

### General options ###
Here you will find all basic settings and information texts.

__Application Settings__

1. Archive  
Role: Which user role has access to the archives in the frontend.
2. Upload  
Archival Upload Path: Server path in which the archives are stored. Default WP upload path  
Max SIP Size in Byte: Maximum upload size for archives in Byte. Default 50000000 Byte  
Supported file MIME Types: Permitted file formats for archival materials. Default 
image/jpeg image/png audio/mp3 audio/mp4 video/mp4  
Virus Check with ClamAV: Activate Virus Check with ClamAV. ClamAV must be installed on the server https://www.clamav.net/  
Automatically delete uploaded files: Enables the automatic deletion of archives via cron job. You can specify the number of days after which archives with a certain status are deleted. 
3. Map  
Google API Key for reverse Geocoding: The plugin uses reverse geocoding to determine the coordinates of an address. Open Street Map is used by default. To use the Google API, an API key must be stored here.  
Default Map Settings: Latitude und Longitude for the Center and Zoom. Default is Graz and zoom 10
4. Style  
Add your custom styles here.

__Information Texts__

All texts can be stored in German, English, French, Italian, and Greek. When using polylang, the activated languages are automatically detected.

1. Register Text: Displayed when a not logged in user wants to create an archive.  
For example "To submit something to the archive, you must first log in. If you have not yet created a user account, please click on ‘Register’ first."
2. Update Profile: Displayed when a user with an incomplete profile wants to create an archive.  
For example "Before you can upload material to the archive you have selected, you must first complete your profile.
   To do this, please provide your full name, home address and date of birth. You must also agree to the terms of use of the Citizen Archive Platform.
Click on My Profile here to start entering information."
3. Privacy Policy Approval: For example "I agree to the terms of use." 
4. SIP Folder deleted Text: Displayed when an archive is accessed after it has been deleted. Contains a placeholder for the number of days.  
For example "The files were automatically deleted after %s days."

### Form Fields ###
Here you can define some fields for the archive upload and edit form.

__Users__

These fields are for users and archivists.

1. Upload Purpose Options: For example "General pre- or post-collection" and "Collection calls"
2. Blocking Time Options: Archive blocking time in years. For example "No blocking time", "5", "10", "15", "Year of birth + 100 years"
3. Blocking Time Upload Purpose: Which Upload Purpose activates the Blocking Time Options. Use a comma seperated list. For example "General pre- or post-collection"
4. Blocking Time Calculate: This blocking time is calculated. For example "Year of birth + 100 years"
5. Right Transfer Text: For example "I hereby agree to the transfer of rights (donation contract) of the submitted file(s) to the archive I have selected."
5. Custom Meta Data: You can define your own fields. For example an textarea field for additional notes.

__Archival Users__
These fields are for archivists only.

1. Custom Meta Data: You can define your own fields. For example an textarea field for additional notes.




