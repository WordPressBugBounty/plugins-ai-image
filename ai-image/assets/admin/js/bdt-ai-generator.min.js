(function ($) {
	$(document).ready(function () {

		if (!$('.ai-image-tabs-search-wrap').length) {
			return;
		}

		const restURL = BDT_AI_IMG.rest_url;
		const api_pexels = 'l7Pk56fQ7sjfslcgFBUXVuggY5sZ2EIRLtSvM1pBwLyzpIWjdQ93gVpH';
		const api_pixabay = '27427772-5e3b7770787f4e0e591d5d2eb';
		var page = 1;
		var page_pixabay = 1;
		var per_page = 15;
		let loading = false;
		var loading_pixabay = false;
		var search = '';
		var searchMode = false;
		var searchMode_pixabay = false;

		const App = {
			tabs: function () {
				$(document).ready(function () {
					$('.ai-image-tabs__nav-item').click(function () {
						loading = false;
						loading_pixabay = false;
						var targetTab = $(this).data('tab-target');
						console.log(targetTab);

						if (targetTab === 'pexels') {
							App.loadPexelsImages();
							console.log('pexels');
						}

						if (targetTab === 'pixabay') {
							App.loadPixabayImages(false);
							console.log('pixabay');
						}

						$('.ai-image-tabs__nav-item').removeClass('active');
						$(this).addClass('active');

						$('.ai-image-tabs__content-item').removeClass('active');
						$('#' + targetTab).addClass('active');
					});
				});
			},
			preparePexelsImages: function (images) {
				var output = [];

				$.each(images, function (index, image) {
					output.push({
						src: {
							original: {
								name: 'original',
								url: image.src.original
							},
							large: {
								name: 'large',
								url: image.src.large
							},
							medium: {
								name: 'medium',
								url: image.src.medium
							},
							small: {
								name: 'small',
								url: image.src.small
							},
							// portrait: {
							// 	name: 'portrait',
							// 	url: image.src.portrait
							// },
							// landscape: {
							// 	name: 'landscape',
							// 	url: image.src.landscape
							// },
							tiny: {
								name: 'tiny',
								url: image.src.tiny
							}
						},
						photographer: image.photographer,
						photographer_url: image.photographer_url,
						url: image.url,
						thumbnail: image.src.tiny,
						user: {
							name: image.photographer ? image.photographer : '',
							avatar: BDT_AI_IMG.assets_url + 'imgs/avatar-human.svg',
							profile: image.photographer_url ? image.photographer_url : ''
						}
					});
				});

				return output;
			},
			preparePixabayImages: function (images) {
				let output = [];

				$.each(images, function (index, image) {

					output.push({
						src: {
							original: {
								name: 'original',
								url: image.webformatURL
							},
							large: {
								name: 'large',
								url: image.largeImageURL
							},
							// medium: {
							// 	name: 'medium',
							// 	url: image.medium
							// },
							// small: {
							// 	name: 'small',
							// 	url: image.small
							// },
							// portrait: {
							// 	name: 'portrait',
							// 	url: image.portrait
							// },
							// landscape: {
							// 	name: 'landscape',
							// 	url: image.landscape
							// },
							tiny: {
								name: 'tiny',
								url: image.tiny
							}
						},
						photographer: image.photographer,
						photographer_url: image.photographer_url,
						url: image.pageURL,
						thumbnail: image.previewURL,
						user: {
							name: image.user,
							avatar: image.userImageURL ? image.userImageURL : BDT_AI_IMG.assets_url + 'imgs/avatar-human.svg',
							profile: image.userURL
						}
					});
				});

				return output;
			},
			prepareOpenAiImages: function (images) {
				let output = [];

				$.each(images, function (index, image) {

					output.push({
						src: {
							original: {
								name: 'original',
								url: image.url
							},
							large: {
								name: 'large',
								url: image.url
							},
							// medium: {
							// 	name: 'medium',
							// 	url: image.medium
							// },
							// small: {
							// 	name: 'small',
							// 	url: image.small
							// },
							tiny: {
								name: 'tiny',
								url: image.url
							}
						},
						photographer: 'OpenAI',
						photographer_url: 'https://openai.com/',
						url: image.url,
						thumbnail: image.output_url,
						user: {
							name: 'OpenAI',
							avatar: BDT_AI_IMG.assets_url + 'imgs/avatar-human.svg',
							profile: 'https://openai.com/'
						}
					});
				});

				return output;
			},
			renderDownloadBtns: function (sources) {
				let downloadBtns = '';
				for (let key in sources) {
					downloadBtns +=
						`<button download="" class="btn btn-primary bdt-aimg-download-btn" data-url="${sources[key]['url']}">
						<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none"
							viewBox="0 0 24 24">
							<path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
								d="M4 15v2a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3v-2m-8 1V4m0 12-4-4m4 4 4-4" />
						</svg>
						<span>${sources[key]['name']}</span>
					</button>`;
				}
				return downloadBtns;
			},
			showImages: function (imgData, type) {
				let output = '';
				let getImgData = [];

				if('openai' === type) {
					getImgData = App.prepareOpenAiImages(imgData);
				}

				if ('pexels' === type) {
					getImgData = App.preparePexelsImages(imgData);
				}

				if ('pixabay' === type) {
					getImgData = App.preparePixabayImages(imgData);

				}
				console.log('typeX');

				$.each(getImgData, function (index, image) {
					let sources = image.src;
					output += `
					<div class="card">
						<div class="aiImg-image-wrap">
							<img src="${image.src.large.url}" class="card-img-top" alt="${image.photographer}">
							<div class="aiImg-download-view-wrap">
								<a href="${image.url}" target="_blank"
									title="View More" class="dropbtn aiImg-view-btn">
									<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none"
										viewBox="0 0 24 24">
										<path stroke="currentColor" stroke-width="2"
											d="M21 12c0 1.2-4.03 6-9 6s-9-4.8-9-6c0-1.2 4.03-6 9-6s9 4.8 9 6Z" />
										<path stroke="currentColor" stroke-width="2" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
									</svg>
								</a>
							</div>
						</div>

						<div class="aiImg-content-wrap">
							<a href="#" class="aiImg-author-wrap">
								<img src="${image.user.avatar}" class="aiImg-author-img" alt="${image.user.name}">
								<span class="aiImg-author-name">${image.user.name}</span>
							</a>
							<div class="aiImg-download-and-drop-wrap">
						    	<button class="dropbtn aiImg-drop-btn bdt-aimg-download-btn-large bdt-aimg-download-btn" data-url="${image.src.large.url}">Import
									<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none"
									viewBox="0 0 24 24">
									<path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
										d="M4 15v2a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3v-2m-8 1V4m0 12-4-4m4 4 4-4" />
							    	</svg>
								</button>
								<div class="download-button-dropdown">
									<button class="dropbtn aiImg-drop-btn ai-image-drop-btn" data-url="${image.src.large.url}">
										<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none"
											viewBox="0 0 24 24">
											<path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
												d="m19 9-7 7-7-7" />
										</svg>
									</button>

									<div class="download-button-content">
										${App.renderDownloadBtns(sources)}
									</div>
								</div>
							</div>
						</div>
					</div>
					`;
				});

				return output;
			},
			loadPexelsImages: function (reset = false) {
				if (loading) return;
				loading = true;

				$('#pexels-loading-indicator').show();

				var url = searchMode ? restURL + 'pexels/search' : restURL + 'pexels/curated';
				var data = {
					api_key: api_pexels,
					page: page,
					per_page: per_page
				};

				if (searchMode) {
					data.search = search;
				}

				fetch(url, {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json'
					},
					body: JSON.stringify(data)
				})
					.then(response => response.json())
					.then(response => {

						$('#pexels-loading-indicator').hide();

						var output = App.showImages(response, 'pexels');

						if (reset) {
							document.getElementById('pexels-loaded-images').innerHTML = output;
						} else {
							document.getElementById('pexels-loaded-images').insertAdjacentHTML('beforeend', output);
						}

						// Hide the loading indicator
						document.getElementById('pexels-loading-indicator').style.display = 'none';

						loading = false;
						page++;
					})
					.catch(() => {
						// Hide the loading indicator
						document.getElementById('pexels-loading-indicator').style.display = 'none';

						loading = false;
					});
			},
			loadPixabayImages: function (reset = false) {

				if (loading_pixabay) return;
				loading_pixabay = true;
				console.log('loadPixabayImages');

				$('#pixabay-loading-indicator').show();

				// Show the loading indicator
				document.getElementById('pixabay-loading-indicator').style.display = 'block';

				var url = searchMode_pixabay ? restURL + 'pixabay/search' : restURL + 'pixabay/search';
				var data = {
					api_key: api_pixabay,
					page: page_pixabay,
					per_page: per_page
				};

				if (searchMode_pixabay) {
					data.search = search;
				} else {
					data.search = 'nature';
				}
				console.log(data);
				fetch(url, {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json'
					},
					body: JSON.stringify(data)
				})
					.then(response => response.json())
					.then(response => {
						let output = App.showImages(response, 'pixabay');
						$('#pixabay-loading-indicator').hide();

						if (reset) {
							document.getElementById('pixabay-loaded-images').innerHTML = output;
						} else {
							document.getElementById('pixabay-loaded-images').insertAdjacentHTML('beforeend', output);
						}


						// Hide the loading indicator
						document.getElementById('pixabay-loading-indicator').style.display = 'none';

						loading_pixabay = false;
						page_pixabay++;
					})
					.catch(() => {
						// Hide the loading indicator
						document.getElementById('pixabay-loading-indicator').style.display = 'none';

						loading_pixabay = false;
					});
			},
			loadOpenAI: function () {
				$('#openai-loading-indicator').show();
				document.getElementById('openai-loaded-images').innerHTML = '';
				let url = restURL + 'openai/images/generations?prompt=' + search + '&n=2&size=1024x1024&method=generator';
				fetch(url, {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json'
					}
				})
					.then(response => response.json())
					.then(response => {

						if(response.error) {
							$('#openai-loading-indicator').hide();
							alert(response.error.message);
							return;
						}

						let output = App.showImages(response.data, 'openai');
						document.getElementById('openai-loaded-images').innerHTML = output;
						$('#openai-loading-indicator').hide();
					})
					.catch(() => {
						console.log('error');
					});
			},
			checkScroll: function () {
				if ($(window).scrollTop() + $(window).height() > $(document).height() - 100) {
					// App.loadPexelsImages();
					// App.loadPixabayImages();

					if ($('.ai-image-tabs__nav .ai-image-tabs__nav-item.pexels').hasClass('active')) {
						App.loadPexelsImages();
					} else if ($('.ai-image-tabs__nav .ai-image-tabs__nav-item.pixabay').hasClass('active')) {
						App.loadPixabayImages();
					}
				}
			},
			searchForm: function () {
				$('#aiImage-search-form').on('submit', function (e) {
					e.preventDefault();
					search = $('#aiImage-search-input').val().trim();
					page = 1;
					page_pixabay = 1;

					$('#openai-loaded-images').html('');
					$('#pexels-loaded-images').html('');
					$('#pixabay-loaded-images').html('');

					if ($('.ai-image-tabs__nav .ai-image-tabs__nav-item.openai').hasClass('active')) {
						App.loadOpenAI();
					}
					if ($('.ai-image-tabs__nav .ai-image-tabs__nav-item.pexels').hasClass('active')) {
						App.loadPexelsImages(true);
						searchMode = true;
					}
					if ($('.ai-image-tabs__nav .ai-image-tabs__nav-item.pixabay').hasClass('active')) {
						App.loadPixabayImages(true);
						searchMode_pixabay = true;
					}
				});
			},
			downLoad: function (img) {
				var imageUrl = $(img).data('url');
				var button = $(img);
				// Show loading indicator on the button
				button.text('Uploading...');
				$.ajax({
					url: BDT_AI_IMG.ajax_url, // Replace with your WordPress AJAX URL
					method: 'POST',
					data: {
						action: 'upload_image_to_wp',
						image_url: imageUrl,
						nonce: BDT_AI_IMG.nonce
					},
					success: function (response) {
						if (response.success) {
							button.text('Uploaded');
						} else {
							button.text('Failed');
						}
					},
					error: function () {
						button.text('Failed');
					}
				});
			},
			load_images_default: function () {
				// App.loadPexelsImages();
				// App.loadPixabayImages();
				// App.loadOpenAI();
			},
			init: function () {
				/**
				 * Layout
				 */
				App.tabs();
				/**
				 * /Layout
				 */
				App.load_images_default();

				$(window).on('scroll', App.checkScroll);
				App.searchForm();

				$(document).on('click', '.bdt-aimg-download-btn', function () {
					App.downLoad(this);
				});
			},

		}

		App.init();


		// $('button').on('click', function () {
		// 	console.log('click');
		// 	// $(this).parent().find('.download-button-content').toggleClass('active');
		// 	// $(this).parent().addClass('active');
		// });

	});

})(jQuery);
