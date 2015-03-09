/*
    Some utility functions for image handling
*/

function NitmFileManagerImages () {
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
		defaultImage: 'default-image',
		extraImage: 'extra-image',
		imageContainer: 'imageContainer',
		imagesContainer: 'imagesContainer',
		uploadFile: "imageFile"
	};
	
	this.defaultInit = [
		'initImageActions',
		'initAjaxUpload'
	];
	
	this.init = function (containerId) {
		this.defaultInit.map(function (method, key) {
			if(typeof self[method] == 'function')
			{
				self[method]();
			}
		});
	}

	/*
		Function to allow adding/uploading multiple images
	*/
	this.initImageActions = function (containerId){
		var container = $nitm.getObj((containerId == undefined) ? 'body' : containerId);
		$.map(this.buttons.roles, function (v, k) {
			var button = container.find("[role='"+v+"']");
			button.off('click');
			button.on('click', function (e) {
				e.preventDefault();
				return self[k](this);
			});
		});
	}
	
	this.initAjaxUpload = function (containerId) {
		var container = $nitm.getObj((containerId == undefined) ? '[role="'+self.containers.imagesContainer+'"]' : containerId);
		setTimeout(function () {
			$.map(self.buttons.upload, function (v, k) {
				container.find(v).each(function() {
					$(this).off('click');
					$(this).on('click', function(e) {
						e.preventDefault();
						var button = $(this);
						var buttonContainer = button.parents('[role="'+self.containers.imageContainer+'"]');
						var postUrl = $(this).attr('href');
						var formData = new FormData();
						var input = $(this).parent().find(':file');
						if(input.get(0) != undefined) {
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
	}
	
	this.afterUpload = function (form, result) {
		var _form = $(form);
		switch(result != false)
		{
			case true:
			var container = _form.parents('[role="'+self.containers.imageContainer+'"]');
			var existing = container.find('[id="existing\-image"]');
			switch(result.success)
			{
				case true:
				_form.find("#progress").delay(500).fadeOut().attr('style', 'display:none');
				container.find('[role="'+self.containers.uploadFile+'"]').fadeOut();
				existing.html('').html($(result.data));
				self.initImageActions(container.attr('id'));
				break;
				
				default:
				_form.find("#percent").html("<font color='red'>"+result.message+"</font>");
				break;
			}
			break;
			
			default:
			_form.find("#percent").html("<font color='red'>UUpload failed</font>");
			break;
		}
	}
	
	this.setDefault = function (elem) {
		var element = $(elem);
		$.post(element.attr('href'), function(result) {
			switch(result)
			{
				case true:
				//swap out the default and new default images
				var defaultParent = $nitm.getObj(self.containers.defaultImage);
				var swappedParent = $nitm.getObj(element.data('parent'));
				var currentDefault = defaultParent.html();
				var newDefault = swappedParent.html();
				switch(currentDefault == "")
				{
					case true:
					defaultParent.html(newDefault);
					swappedParent.parent().html('');
					self.initImageActions('#'+self.containers.defaultImage);
					self.setupParent(defaultParent, true);
					break;
					
					default:
					//Swap the data-parent values in the remove and setDefault activators
					self.swapMeta(defaultParent, swappedParent);
					swappedParent.html(currentDefault);
					defaultParent.html(newDefault);
					self.initImageActions('#'+self.containers.defaultImage);
					self.initImageActions(element.data('parent'));
					self.setupParent(defaultParent, true);
					self.setupParent(swappedParent);
					break;
				}
				break;
			}
		});
		return false;
	}
	
	this.deleteImage = function (elem) {
		var element = $(elem);
		switch(confirm("Are you sure you want to delete this image?"))
		{
			case true:
			$.post(element.attr('href'), function(result) {
				switch(result != false)
				{
					case true:
					element.parents('[role="'+self.containers.imageContainer+'"]').find('[role="'+self.containers.uploadFile+'"]').fadeIn();
					element.parents('[role="'+self.containers.imageContainer+'"]').find('[id="existing\-image"]').html('');
					break;
				}
			});
			break;
		}
		return false;
	}
	
	this.setupParent = function (elem, isDefault) {
		var element = $(elem);
		var setDefault = element.find("[role~='"+self.buttons.roles.setDefault+"']");
		var deleteImage = element.find("[role~='"+self.buttons.roles.deleteImage+"']");
		switch(isDefault)
		{
			case true:
			setDefault.addClass('hidden');
			setDefault.attr('data-parent', 'default-image');
			deleteImage.attr('data-parent', 'default-image');
			element.find('[class="file\-preview\-sm"]').attr('class', 'file-preview');
			element.attr('id', self.containers.defaultImage);
			break;
			
			default:
			var parentId = self.containers.extraImage+setDefault.data('id');
			setDefault.removeClass('hidden');
			setDefault.attr('data-parent', parentId);
			deleteImage.attr('data-parent', parentId);
			element.find('[class="file\-preview"]').attr('class', 'file-preview-sm');
			element.attr('id', parentId);
			break;
		}
	}
	
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
	}
}

$nitm.initModule(new NitmFileManagerImages());