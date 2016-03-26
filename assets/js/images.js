'use strict';
/*
    Some utility functions for image handling
*/

class NitmFileManagerImages extends NitmFileManagerFiles
{
	constructor() {
		super('nitm-filemanager:images');
		this.classes = {
			extraItemFrame: 'file-preview-frame-sm',
			defaultItemFrame: 'file-preview-frame'
		};

		this.buttons = {
			roles :{
				setDefault: 'toggleDefaultImage',
				deleteImage: 'deleteImage',
			},
			upload: ["[class~='image-upload-button']"]
		};

		this.containers = {
			defaultItem: "[role~='defaultImage']",
			extraItem: "[role~='extraImage']",
			item: "[role~='imageContainer']",
			container: "[role~='imagesContainer']",
			uploadFile: "imageFile"
		};

		this.roles = {
			defaultItem: "defaultImage imageContainer",
			extraItem: "extraImage imageContainer",
			item: "imageContainer",
			container: "imagesContainer",
			uploadFile: "uploadFile",
		};
	}
}

$nitm.onModuleLoad('nitm-filemanager', function (module) {
	module.initModule(new NitmFileManagerImages());
});
