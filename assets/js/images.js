'use strict';
/*
    Some utility functions for image handling
*/

class NitmFileManagerImages extends NitmEntity
{
	constructor() {
		super('nitm-file-manager:images');
		this.classes = {
			extraImageFrame: 'file-preview-frame-sm',
			defaultImageFrame: 'file-preview-frame'
		};

		this.buttons = {
			roles :{
				$setDefault: 'toggleDefaultImage',
				$deleteImage: '$deleteImage',
			},
			upload: ["[class~='image-upload-$button']"]
		};

		this.containers = {
			defaultImage: "[role~='defaultImage']",
			extraImage: "[role~='extraImage']",
			imageContainer: "[role~='imageContainer']",
			imagesContainer: "[role~='imagesContainer']",
			uploadFile: "imageFile"
		};

		this.defaultInit = [
			'initImageActions',
			'initAjaxUpload'
		];
	}

	/*
		Function to allow adding/uploading multiple images
	*/
	initImageActions (containerId){
		let $container = $nitm.getObj((containerId === undefined) ? 'body' : containerId);
		$.map(this.buttons.roles, (v, k) => {
			let $button = $container.find("[role~='"+v+"']");
			$button.off('click');
			$button.on('click', (e) => {
				e.preventDefault();
				return this[k](e.currentTarget);
			});
		});
	};

	initAjaxUpload (containerId) {
		let $container = $nitm.getObj((containerId === undefined) ? this.containers.imagesContainer : containerId);
		setTimeout(() => {
			$.map(this.buttons.upload, (v, k) => {
				$container.find(v).each((i, elem) => {
					let $elem = $(elem);
					$elem.off('click');
					$elem.on('click', function(e) {
						e.preventDefault();
						let $button = $(e.currentTarget);
						let $buttonContainer = $button.parents(this.containers.imageContainer);
						let postUrl = $(this).attr('href');
						let formData = new FormData();
						let input = $(this).parent().find(':file');
						if(input.get(0) !== undefined) {
							let name = input.attr("name");
							formData.append(name, input.get(0).files[0]);
							$.ajax({
								url: postUrl,
								type: 'post',
								data: formData, // send in your data
								processData: false,
								contentType: false,
								xhr: function () {
									let myXhr = $.ajaxSettings.xhr();
									if(myXhr.upload){
										myXhr.upload.addEventListener('progress', function(event){
											let percentComplete = (event.loaded/event.total) * 100;
											$buttonContainer.find("#bar").width(percentComplete+'%').attr('aria-valuenow', percentComplete);
											$buttonContainer.find("#percent").html(percentComplete+'%');

										}, false);
									} else {
										console.log("Upload progress is not supported.");
									}
									return myXhr;
								},
								beforeSend: function() {
									$buttonContainer.find("#progress").fadeIn().attr('style', 'display:block');
									//clear everything
									$buttonContainer.find("#bar").width('0%');
									$buttonContainer.find("#percent").html("0%");
									$button.addClass('disabled');
								},
								success: function(result){
									$buttonContainer.find("#bar").width('100%').attr('aria-valuenow', 100);
									$buttonContainer.find("#percent").html('100%');
									this.afterUpload($button, result);

								},
								complete: function(response){
									$buttonContainer.find("#percent").html("<font color='green'>"+response.responseText+"</font>");
									$button.removeClass('disabled');
								},
								error: function(){
									$buttonContainer.find("#percent").html("<font color='red'> ERROR: unable to upload files</font>");
								}
							});
						}
					});
				});
			});
		}, 4000);
	};

	afterUpload (form, result) {
		let $form = $(form);
		switch(result !== false)
		{
			case true:
			switch(result.success)
			{
				case true:
				let $container = $(this.containers.imagesContainer+"[data-id='"+result.remoteId+"']");
				let $newImage = $(result.data);
				$container.append($(result.data));
				this.initImageActions($newImage.attr('id'));
				break;

				default:
				$form.find("#percent").html("<font color='red'>"+result.message+"</font>");
				break;
			}
			break;

			default:
			$form.find("#percent").html("<font color='red'>Upload failed</font>");
			break;
		}
	};

	$setDefault (elem) {
		let $element = $(elem);
		$.post($element.attr('href'), (result) => {
			if(result) {
				//swap out the default and new default images
				let newDefault = $nitm.getObj($element.data('parent'));
				let existingDefault = $nitm.getObj(this.containers.imagesContainer).find(this.containers.defaultImage);
				existingDefault.find('.thumbnail').removeClass('default');
				newDefault.find('.thumbnail').addClass('default');
				this.initImageActions(newDefault);
				this.setupParent(existingDefault, false);
				this.setupParent(newDefault, true);
			}
		});
		return false;
	};

	$deleteImage (elem) {
		let $element = $(elem);
		switch(confirm("Are you sure you want to delete this image?"))
		{
			case true:
			$.post($element.attr('href'), function(result) {
				if(result)
					$($element.data('parent')).fadeOut().remove();
			});
			break;
		}
		return false;
	};

	setupParent (elem, isDefault) {
		let $element = $(elem);
		let $setDefault = $element.find("[role~='"+this.buttons.roles.setDefault+"']");
		let $deleteImage = $element.find("[role~='"+this.buttons.roles.deleteImage+"']");
		switch(isDefault)
		{
			case true:
			$setDefault.addClass('hidden');
			$element.attr('role', 'defaultImage');
			break;

			default:
			$setDefault.removeClass('hidden');
			$element.attr('role', 'extraImage');
			break;
		}
	};

	swapMeta (from, to) {
		let oldMakeDefaultHref = $(from).find("[role='"+this.buttons.setDefault+"']").attr('href');
		let oldDeleteHref = $(from).find("[role='"+this.buttons.deleteImage+"']").attr('href');
		let newMakeDefaultHref = $(to).find("[role='"+this.buttons.setDefault+"']").attr('href');
		let newDeleteHref = $(to).find("[role='"+this.buttons.deleteImage+"']").attr('href');

		$.map(this.buttons, function (v, k) {
			let oldHref = $(from).find(v).attr('href');
			let newHref = $(to).find(v).attr('href');
			from.attr('href', newHref);
			to.attr('href', oldHref);
		});
	};
}

$nitm.onModuleLoad('entity', function (module) {
	module.initModule(new NitmFileManagerImages());
});
