/*
    Some utility functions for file handling
*/

function NitmFileManagerFiles () {

	NitmEntity.call(this);

	var self = this;
	this.id = 'nitm-file-manager:files';
	this.classes = {
		extraFileFrame: 'file-preview-frame-sm',
		defaultFileFrame: 'file-preview-frame'
	};

	this.buttons = {
		roles :{
			setDefault: 'toggleDefaultFile',
			deleteFile: 'deleteFile',
		},
		upload: ["[class~='file-upload-button']"]
	};

	this.containers = {
		defaultFile: "[role~='defaultFile']",
		extraFile: "[role~='extraFile']",
		fileContainer: "[role~='fileContainer']",
		filesContainer: "[role~='filesContainer']",
		uploadFile: "fileFile"
	};

	this.defaultInit = [
		'initFileActions',
		'initAjaxUpload'
	];

	/*
		Function to allow adding/uploading multiple files
	*/
	this.initFileActions = function (containerId){
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
		var container = $nitm.getObj((containerId === undefined) ? self.containers.filesContainer : containerId);
		setTimeout(function () {
			$.map(self.buttons.upload, function (v, k) {
				container.find(v).each(function() {
					$(this).off('click');
					$(this).on('click', function(e) {
						e.preventDefault();
						var button = $(this);
						var buttonContainer = button.parents(self.containers.fileContainer);
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
				var container = $(self.containers.filesContainer+"[data-id='"+result.remoteId+"']");
				var $newFile = $(result.data);
				container.append($(result.data));
				self.initFileActions($newFile.attr('id'));
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
				//swap out the default and new default files
				var newDefault = $nitm.getObj($element.data('parent'));
				var existingDefault = $nitm.getObj(self.containers.filesContainer).find(self.containers.defaultFile);
				existingDefault.find('.thumbnail').removeClass('default');
				newDefault.find('.thumbnail').addClass('default');
				self.initFileActions(newDefault);
				self.setupParent(existingDefault, false);
				self.setupParent(newDefault, true);
				break;
			}
		});
		return false;
	};

	this.deleteFile = function (elem) {
		var $element = $(elem);
		switch(confirm("Are you sure you want to delete this file?"))
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
		var deleteFile = $element.find("[role~='"+self.buttons.roles.deleteFile+"']");
		switch(isDefault)
		{
			case true:
			setDefault.addClass('hidden');
			$element.attr('role', 'defaultFile');
			break;

			default:
			setDefault.removeClass('hidden');
			$element.attr('role', 'extraFile');
			break;
		}
	};

	this.swapMeta = function(from, to) {
		var oldMakeDefaultHref = $(from).find("[role='"+self.buttons.setDefault+"']").attr('href');
		var oldDeleteHref = $(from).find("[role='"+self.buttons.deleteFile+"']").attr('href');
		var newMakeDefaultHref = $(to).find("[role='"+self.buttons.setDefault+"']").attr('href');
		var newDeleteHref = $(to).find("[role='"+self.buttons.deleteFile+"']").attr('href');

		$.map(this.buttons, function (v, k) {
			var oldHref = $(from).find(v).attr('href');
			var newHref = $(to).find(v).attr('href');
			from.attr('href', newHref);
			to.attr('href', oldHref);
		});
	};
}

$nitm.initModule(new NitmFileManagerFiles());
