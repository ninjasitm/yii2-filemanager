/*
    Some utility functions for image handling
*/

function NitmFileManagerImages () {

	NitmEntity.call(this);

	var self = this;
	this.id = 'nitm-file-manager:images';
	this.classes = {
		extraImageFrame: 'file-preview-frame-sm',
		defaultImageFrame: 'file-preview-frame'
	};

	this.buttons = {
		roles :{
			setDefault: 'toggleDefaultImage',
			deleteImage: 'deleteImage',
		},
		upload: ["[class~='image-upload-button']"]
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

	/*
		Function to allow adding/uploading multiple images
	*/
	this.initImageActions = function (containerId){
		var container = $nitm.getObj((containerId === undefined) ? 'body' : containerId);
		$.map(this.buttons.roles, function (v, k) {
			var button = container.find("[role~='"+v+"']");
			button.off('click');
			button.on('click', function (e) {
				e.preventDefault();
				return self[k](this);
			});
		});
	};

	this.initAjaxUpload = function (containerId) {
		var container = $nitm.getObj((containerId === undefined) ? self.containers.imagesContainer : containerId);
		setTimeout(function () {
			$.map(self.buttons.upload, function (v, k) {
				container.find(v).each(function() {
					$(this).off('click');
					$(this).on('click', function(e) {
						e.preventDefault();
						var button = $(this);
						var buttonContainer = button.parents(self.containers.imageContainer);
						var postUrl = $(this).attr('href');
						var formData = new FormData();
						var input = $(this).parent().find(':file');
						if(input.get(0) !== undefined) {
							var name = input.attr("name");
							formData.append(name, input.get(0).files[0]);
							$.ajax({
								url: postUrl,
								type: 'post',
								data: formData, // send in your data
								processData: false,
								contentType: false,
								xhr: function () {
									var myXhr = $.ajaxSettings.xhr();
									if(myXhr.upload){
										myXhr.upload.addEventListener('progress', function(event){
											var percentComplete = (event.loaded/event.total) * 100;
											buttonContainer.find("#bar").width(percentComplete+'%').attr('aria-valuenow', percentComplete);
											buttonContainer.find("#percent").html(percentComplete+'%');

										}, false);
									} else {
										console.log("Upload progress is not supported.");
									}
									return myXhr;
								},
								beforeSend: function() {
									buttonContainer.find("#progress").fadeIn().attr('style', 'display:block');
									//clear everything
									buttonContainer.find("#bar").width('0%');
									buttonContainer.find("#percent").html("0%");
									button.addClass('disabled');
								},
								success: function(result){
									buttonContainer.find("#bar").width('100%').attr('aria-valuenow', 100);
									buttonContainer.find("#percent").html('100%');
									self.afterUpload(button, result);

								},
								complete: function(response){
									buttonContainer.find("#percent").html("<font color='green'>"+response.responseText+"</font>");
									button.removeClass('disabled');
								},
								error: function(){
									buttonContainer.find("#percent").html("<font color='red'> ERROR: unable to upload files</font>");
								}
							});
						}
					});
				});
			});
		}, 4000);
	};

	this.afterUpload = function (form, result) {
		var $form = $(form);
		switch(result !== false)
		{
			case true:
			switch(result.success)
			{
				case true:
				var container = $(self.containers.imagesContainer+"[data-id='"+result.remoteId+"']");
				var $newImage = $(result.data);
				container.append($(result.data));
				self.initImageActions($newImage.attr('id'));
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

	this.setDefault = function (elem) {
		var $element = $(elem);
		$.post($element.attr('href'), function(result) {
			switch(result)
			{
				case true:
				//swap out the default and new default images
				var newDefault = $nitm.getObj($element.data('parent'));
				var existingDefault = $nitm.getObj(self.containers.imagesContainer).find(self.containers.defaultImage);
				existingDefault.find('.thumbnail').removeClass('default');
				newDefault.find('.thumbnail').addClass('default');
				self.initImageActions(newDefault);
				self.setupParent(existingDefault, false);
				self.setupParent(newDefault, true);
				break;
			}
		});
		return false;
	};

	this.deleteImage = function (elem) {
		var $element = $(elem);
		switch(confirm("Are you sure you want to delete this image?"))
		{
			case true:
			$.post($element.attr('href'), function(result) {
				switch(result !== false)
				{
					case true:
					$($element.data('parent')).fadeOut().remove();
					break;
				}
			});
			break;
		}
		return false;
	};

	this.setupParent = function (elem, isDefault) {
		var $element = $(elem);
		var setDefault = $element.find("[role~='"+self.buttons.roles.setDefault+"']");
		var deleteImage = $element.find("[role~='"+self.buttons.roles.deleteImage+"']");
		switch(isDefault)
		{
			case true:
			setDefault.addClass('hidden');
			$element.attr('role', 'defaultImage');
			break;

			default:
			setDefault.removeClass('hidden');
			$element.attr('role', 'extraImage');
			break;
		}
	};

	this.swapMeta = function(from, to) {
		var oldMakeDefaultHref = $(from).find("[role='"+self.buttons.setDefault+"']").attr('href');
		var oldDeleteHref = $(from).find("[role='"+self.buttons.deleteImage+"']").attr('href');
		var newMakeDefaultHref = $(to).find("[role='"+self.buttons.setDefault+"']").attr('href');
		var newDeleteHref = $(to).find("[role='"+self.buttons.deleteImage+"']").attr('href');

		$.map(this.buttons, function (v, k) {
			var oldHref = $(from).find(v).attr('href');
			var newHref = $(to).find(v).attr('href');
			from.attr('href', newHref);
			to.attr('href', oldHref);
		});
	};
}

$nitm.initModule(new NitmFileManagerImages());
