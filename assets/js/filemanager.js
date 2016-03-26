/*
    Some utility functions for file handling
*/

class NitmFileManagerFiles extends NitmEntity
{
	constructor(id) {
		super(id);
		this.classes = {
			extraItemFrame: 'file-preview-frame-sm',
			defaultItemFrame: 'file-preview-frame'
		};

		this.buttons = {
			roles :{
				addUrl: 'addUrl',
				setDefault: 'toggleDefaultFile',
				deleteFile: 'deleteFile',
			},
			upload: ["[class~='file-upload-button']"]
		};

		this.containers = {
			defaultItem: "[role~='defaultFile']",
			extraItem: "[role~='extraFile']",
			item: "[role~='filesContainer']",
			container: "[role~='filesContainer']",
			uploadFile: "uploadFile",
		};

		this.roles = {
			defaultItem: "defaultFile fileContainer",
			extraItem: "extraFile fileContainer",
			item: "filesContainer",
			container: "filesContainer",
			uploadFile: "uploadFile",
		};

		this.defaultInit = [
			'initActions',
			'initAjaxUpload'
		];
	}

	/*
		Function to allow adding/uploading multiple images
	*/
	initActions (containerId){
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
		let $container = $nitm.getObj(containerId || this.containers.container);
		$.map(this.buttons.upload, (v, k) => {
			$container.find(v).each((i, elem) => {
				let $elem = $(elem);
				$elem.off('click');
				$elem.on('click', function(e) {
					e.preventDefault();
					let $button = $(e.currentTarget);
					let $buttonContainer = $button.parents(this.containers.item);
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
	};

	afterUpload (form, result) {
		let $form = $(form);
		switch(result !== false)
		{
			case true:
			switch(result.success)
			{
				case true:
				let $container = $(this.containers.container+"[data-id='"+result.remoteId+"']");
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

	addUrl (elem) {
		let modalId = '',
		 	inputId = Date.now()+'url',
			$saveButton = $('<button type="button" class="btn btn-success">Add</button>');
		$saveButton .on('click', (e) => {
			let url = $($(e.target).data('input-id')).val(),
				$modal = $($(e.target).data('modal-id')),
				$input = $($(e.target).data('input-id')),
				$ui = $($(e.target).data('file-upload-ui')),
				$notification;
			if(url) {
				let file = new File([url], url, {type: 'file/url'});
				$ui.fileupload('add', {files: [file]});
				$input.val('');
				$notification = $('<br><div class="alert alert-sm alert-success">Added url! Add another</div>');
			} else {
				$notification = $('<br><div class="alert alert-sm alert-warning">No url Specified!</div>');
			}
			$modal.find('.modal-body').append($notification);
			setTimeout(() => {$notification.slideUp()}, 2500);
		});
		 modalId = $nitm.m('utils').dialog('<input class="form-control" id="'+inputId+'">', {
			title: 'Add a url for a file',
			actions: [
				$saveButton
			]
		});
		$saveButton.data({
			'modal-id': '#'+modalId,
			'input-id': '#'+inputId,
			'file-upload-ui': $(elem).data('file-upload-ui')
		});
	}

	setDefault (elem) {
		let $element = $(elem);
		$.post($element.attr('href'), (result) => {
			if(result) {
				let $container = $nitm.getObj(this.containers.container);
				//swap out the default and new default images
				let $newDefault = $nitm.getObj($element.data('parent'));
				let $existingDefault = $container.find(this.containers.defaultItem);
				$existingDefault.find('.thumbnail').removeClass('default');
				$newDefault.find('.thumbnail').addClass('default');
				this.initActions($newDefault);
				this.setupParent($existingDefault, false);
				this.setupParent($newDefault, true);
			}
		});
		return false;
	};

	deleteImage (elem) {
		let $element = $(elem);
		switch(confirm("Are you sure you want to delete this image?"))
		{
			case true:
			$.post($element.data('url') || $element.attr('href'), function(result) {
				if(result)
					$($element.data('parent')).fadeOut().remove();
			});
			break;
		}
		return false;
	};

	setupParent (elem, isDefault) {
		let $element = $(elem),
			$setDefault = $element.find("[role~='"+this.buttons.roles.setDefault+"']"),
			$deleteImage = $element.find("[role~='"+this.buttons.roles.deleteImage+"']");
		switch(isDefault)
		{
			case true:
			$setDefault.addClass('hidden');
			$element.attr('role', this.roles.defaultItem);
			let $container = $nitm.getObj(this.containers.container),
				$existingDefault = $container.find(this.containers.item).get(0);
			$element.insertBefore($existingDefault);
			break;

			default:
			$setDefault.removeClass('hidden');
			$element.attr('role', this.roles.extraItem);
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

$nitm.oml('entity', (module) => module.initModule(new NitmFileManagerFiles('nitm-filemanager')));
