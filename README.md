# d8media

A helper repository with libraries, modules, ~~patches~~ for our Drupal 8 media workshop ( http://2016.drupalaton.hu/schedule#speaker-221 ).

You can download a full copy of the state of the local demo site after the workshop:
http://d8paragraphs.brainsum.com/sites/default/files/d8media/d8media-full.tgz
http://d8paragraphs.brainsum.com/sites/default/files/d8media/d8media-full.dump.gz

To prepare for the workshop install a fresh Drupal 8.1.8 standard profile and then copy with overwrite the contents of this repository onto it.

on Linux or OS-X:

```
wget --no-check-certificate https://ftp.drupal.org/files/projects/drupal-8.1.8.tar.gz 
tar xzf drupal-8.1.8.tar.gz
wget --no-check-certificate https://github.com/brainsum/d8media/archive/master.zip
unzip master.zip
cd d8media-master && cp -a * ../drupal-8.1.8 && cd ../drupal-8.1.8
echo "Codebase ready for D8 media workshop. Install Drupal and join the workshop."
```
# Outline of the workshop

## Stage 1 media bundles
* Drupal 8 core installation
*	https://github.com/brainsum/d8media
*	Enabling media with dependencies + cex!
*	Creating media images, videos - media admin
*	Creating a gallery
*	Customizing the gallery slider
  *	Vanilla slick - checked
  *	Optionset main - Default
  *	Skin main - Default
  *	Cache - no caching
  *	Override main optionset
  *	Dots
  *	Draggable
  *	Infinite

## Stage 2 re-use media
*	enable File browser
*	change the form display settings of the Image media bundle to Entity browser: 
*	Browser for files (modal)
  *	Uncheck the Display Remove button
  *	Selection mode: Edit selection
*	add a media image field to the article content type
*	Set Form display to Entity browser + Gallery media library, Append to selection, Entity Display plugin: Rendered entity, View mode: Media Library
*	create / re-use images + upload with drag’n’drop

## Stage 3 CKEDITOR embed
*	Add Media embed button
  * Entity browser: autocomplete
*	Add icons at text formats and editors
  * Display embedded entities CHECK
  * Restrict images to this site UNCHECK

## Stage 4 responsive images + crop
*	Enable Drupalaton Crop Config
*	crop types, image styles, responsive image type
*	bonus round: add the responsive image widget to the entity browser
	
## Stage 5 paragraphs
*	Enable Paragraphs demo
*	Show paragraph types
*	Create a paragraphed article
*	Change the form display of an image field in a paragraph type to Entity browser / File browser (modal)
*	http://d8paragraphs.brainsum.com/pulp-fiction - Happy Vincent?

## Bonus example: config entity browsers, filter for media bundles.
