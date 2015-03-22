jQuery(document).ready(function ($) {
	
	function list_gallery_thumbs(data_obj,index) {
		$.post(ajax_object.ajax_url, data_obj, function(response) {
			if(new_obj[index]) {
				loading[index].className = loading_classes[index];
				new_obj[index].innerHTML=response;
				new_obj[index].className = 'twobc_image_gallery_overlay_wrapper show_gallery';
				wrapper_obj[index].className = wrap_classes[index]+' hide_gallery';				
				attach_image_thumb_events(data_obj,new_obj,index,true);				
			}
		});
	}
	
	function twobcig_update_inner(obj,obj1,dobj) {
		var galid = obj.getAttribute('data-twobcig-gallery');
		var cindex = obj.getAttribute('data-twobcig-index');
				
		dobj['twobcig_index'] = cindex;
		dobj['twobcig_gallery'] = galid;		
		
		$.post(ajax_object.ajax_url, dobj, function(response2) {
			var innerwrap = obj1.getElementsByClassName('twobc_ig_modal_wrapper')[0];
			var newimg = innerwrap.getElementsByTagName('img')[0];
			var currentheight = $(newimg).height() + 'px';
			$(newimg).animate({opacity: 0}, 200,function(){
				innerwrap.outerHTML = response2;
				innerwrap = obj1.getElementsByClassName('twobc_ig_modal_wrapper')[0];
				newimg = innerwrap.getElementsByTagName('img')[0];
				newimg.style.height = currentheight;
				newimg.style.opacity = 0;
				newimg.src = newimg.src;
				newimg.onload = function() {
					this.style.height = 'auto';
					$(this).animate({opacity: 1}, 200);
				};
				twobcig_add_inner_listeners(dobj);
			});			
		});
	
	}
	
	function twobcig_image_cycler() {
		var nextb = pico_content.getElementsByClassName('twobc_ig_modal_next_button')[0];
		if(nextb) {$(nextb).trigger('click');}
	}
	
	function twobcig_add_inner_listeners(dataobj) {
		pico_content = document.getElementsByClassName('twobc-pico-content')[0];
					
		if(pico_content) {
			resize_handler(pico_content);
			$(window).resize(function() {
				resize_handler(pico_content);
			});
			var close_button = pico_content.getElementsByClassName('pico-close')[0];
			var overlay_listener = document.getElementsByClassName('pico-overlay')[0];
			if(overlay_listener) {overlay_listener.onclick=function(){$(window).off("resize");play_toggle=false;};}
			if(close_button) {close_button.onclick=function(){$(window).off("resize");play_toggle=false;};}
			
			var nextb = pico_content.getElementsByClassName('twobc_ig_modal_next_button')[0];
			var prevb = pico_content.getElementsByClassName('twobc_ig_modal_prev_button')[0];
			var playb = pico_content.getElementsByClassName('twobc_ig_modal_play_button')[0];
			
			if(nextb && prevb) {
				$(nextb).click(function(e){
					e.preventDefault();					
					twobcig_update_inner(this,pico_content,dataobj);
				});
				$(prevb).click(function(e){
					e.preventDefault();					
					twobcig_update_inner(this,pico_content,dataobj);
				});
			}
			
			if(playb) {
				
				if(isNaN(dataobj['twobcig_slideshow_delay'])) {
					if(isNaN(parseInt(dataobj['twobcig_slideshow_delay']))) {				
						dataobj['twobcig_slideshow_delay'] = 5000;
					} else {
						dataobj['twobcig_slideshow_delay'] = parseInt(dataobj['twobcig_slideshow_delay']);
					}
				}
				
				if(play_toggle == true) {
					clearTimeout(interval_cycle);
					playb.className += ' playing';
					interval_cycle = setTimeout(twobcig_image_cycler, dataobj['twobcig_slideshow_delay']);
				} else {
					clearTimeout(interval_cycle);
				}
				$(playb).click(function(e){
					e.preventDefault();
					if(play_toggle == false) {
						clearTimeout(interval_cycle);
						play_toggle = true;
						this.className += ' playing';
						twobcig_image_cycler();
						interval_cycle = setTimeout(twobcig_image_cycler, dataobj['twobcig_slideshow_delay']);
					} else {
						clearTimeout(interval_cycle);
						play_toggle = false;
						this.className = this.className.replace(' playing','');
					}
				});				
			}
		}
	}
	
	function attach_image_thumb_events(data_obj,new_obj,index,bb_listen) {
		
		//Back Button Listener if applicable
		
		var backbutton = new_obj[index].getElementsByClassName('twobc_galleries_back')[0];
		if(backbutton) {
			backbutton.originalindex = index;
			if(bb_listen != 'disable_back') {
				backbutton.onclick = function(e) {
					e.preventDefault();
					var index = this.originalindex;
					data_objs[index]['twobcig_page_num'] = 1;
					new_obj[index].className = 'twobc_image_gallery_overlay_wrapper hide_gallery';
					wrapper_obj[index].className = wrap_classes;
				};
			}
		}
		
		//Page Numbers Listeners
		
		var page_number_wrapper = new_obj[index].getElementsByClassName('gallery_page_buttons')[0];
		
		if(page_number_wrapper) {
			page_number_wrapper.id = 'twobcpnw_'+index;
			var page_numbers = page_number_wrapper.getElementsByTagName('a');
			for(var d=0;d<page_numbers.length;d++) {
				if(page_numbers[d].className == 'next_page' || page_numbers[d].className == 'previous_page') {
					page_numbers[d].onclick = function(e) {
						e.preventDefault();
						var index = this.parentNode.id.split('_')[1];
						var parsed = this.href.split('&');
						if(parsed[0]) {
							for(var e=0;e<parsed.length;e++) {
								if(parsed[e].indexOf('page_num')!=-1) {
									data_objs[index]['twobcig_page_num'] = parsed[e].split('=')[1];
									loading[index].className = loading_classes[index]+' show';
									list_gallery_thumbs(data_obj,index);
									break;
								}
							}
						}
					}
				} else {
					page_numbers[d].onclick = function(e) {
						e.preventDefault();
						var index = this.parentNode.id.split('_')[1];
						data_objs[index]['twobcig_page_num'] = this.innerHTML;
						loading[index].className = loading_classes[index]+' show';
						list_gallery_thumbs(data_obj,index);
					}
				}
			}
		}
		
		//Image Thumbs Listeners
		
		var image_thumbs = new_obj[index].getElementsByClassName('thumb_wrapper');
		for(var b=0;b<image_thumbs.length;b++) {
			image_thumbs[b].originalindex = index;
			image_thumbs[b].onclick = function(e) {
				e.preventDefault();
				var index = this.originalindex;
				loading[index].className = loading_classes[index]+' show';
				var str1 = this.className.split(' ')[1].split('_')[1];
				var galid = this.getAttribute('data-twobcig-gallery');
				var cindex = this.getAttribute('data-twobcig-index');								
				var current_count = this.getAttribute('data-twobcig-count');
				var data1 = {
					'action': 'twobc_image_gallery_image_generate',
					'ajax_nonce': ajax_object.ajax_nonce,
					'twobcig_gallery': galid,
					'twobcig_index': cindex,
					'twobcig_sort_method': data_obj['twobcig_sort_method'],
					'twobcig_sort_order': data_obj['twobcig_sort_order'],
					'twobcig_gallery_count' : current_count,
					'twobcig_parents': data_obj['twobcig_parents'],
					'twobcig_paginate_galleries' : data_obj['twobcig_paginate_galleries'],
					'twobcig_images_per_page' : data_obj['twobcig_images_per_page'],
					'twobcig_view_style' : data_obj['twobcig_view_style'],
					'twobcig_separate_galleries' : data_obj['twobcig_separate_galleries'],
					'twobcig_show_months' : data_obj['twobcig_show_months'],
					'twobcig_slideshow_delay' : data_obj['twobcig_slideshow_delay'],
					'twobcig_page_num' : data_obj['twobcig_page_num'],
					'twobcig_page_id' : data_obj['twobcig_page_id'],
					'twobcig_back_button' : data_obj['twobcig_back_button']
				};			
								
				$.post(ajax_object.ajax_url, data1, function(response1) {
					loading[index].className = loading_classes[index];
					twobc_picoModal(response1);
					twobcig_add_inner_listeners(data1);								
				});
			}
		}
	}
	
	//Resize handler for when viewing an image in modal
	
	function resize_handler(obj) {
		var obj1 = obj.getElementsByClassName('twobc_ig_modal_image')[0];
		if(obj1) {			
			var parsed = obj1.outerHTML.split(' ');
			var iwidth = 0;
			var iheight = 0;		
			var swidth = $(window).width();
			var sheight = $(window).height();			
			var adminbar = document.getElementById('wpadminbar');
			var adminoffset = 0;
			if(adminbar) {adminoffset=32;sheight-=adminoffset;}
			var actual_sheight = sheight;
			var des_height = 45;
			var extras = 50;
			swidth-=extras;
			sheight-=extras;
			for(var c=0;c<parsed.length;c++) {
				if(parsed[c].indexOf('width=') != -1) {iwidth = Number(parsed[c].split('"')[1]);}
				if(parsed[c].indexOf('height=') != -1) {iheight = Number(parsed[c].split('"')[1]);}
			}		
			var irat = iwidth/iheight;
			var outside_width = iwidth+extras;
			var outside_height = iheight+des_height+extras;
			var nwidth = iwidth;
			var nheight = iheight+des_height;
			if(outside_width > swidth) {
				var diff = swidth - outside_width;
				nwidth+=diff;
				nheight = (nwidth/irat)+des_height;
				outside_width = nwidth+extras;
				outside_height = nheight+extras;
				if(outside_height > sheight) {
					var diffy = sheight-outside_height;
					var snheight = (nheight-des_height)+diffy;
					nwidth = snheight*irat;
					nheight = snheight+des_height;
					outside_width = nwidth+extras;
					outside_height = nheight+extras;					
				}				
			} else {
				if(outside_height > sheight) {
					var diffy = sheight-outside_height;
					var snheight = (nheight-des_height)+diffy;
					nwidth = snheight*irat;
					nheight = snheight+des_height;
					outside_width = nwidth+extras;
					outside_height = nheight+extras;
					if(outside_width > swidth) {
						var diff = swidth - outside_width;
						nwidth+=diff;
						nheight = (nwidth/irat)+des_height;
						outside_width = nwidth+extras;
						outside_height = nheight+extras;
					}
				}
			}
			obj.style.width = (nwidth)+'px';
			obj.style.marginLeft = -(outside_width/2)+'px';
			obj.style.top = ((actual_sheight-outside_height)/2)+adminoffset+'px';		
		}
	}
	
	var wrapper_objects = document.getElementsByClassName('twobc_image_gallery_wrapper categories_wrapper');
	
	if(wrapper_objects[0]) {
		
		var wrapper_obj = [];
		var wrap_classes = [];
		var new_obj = [];
		var new_obj_classes = [];
		var loading = [];
		var loading_classes = [];
		var data_objs = [];
		var play_toggle = false;
		var interval_cycle;
		
		for(var i=0;i<wrapper_objects.length;i++) {
			wrapper_obj.push(wrapper_objects[i]);
			wrap_classes.push(wrapper_objects[i].className);
			var gals = wrapper_objects[i].getElementsByClassName('thumb_wrapper');
			var imgs = wrapper_objects[i].parentNode.getElementsByClassName('twobc_image_gallery_wrapper images_wrapper');
			var tno = wrapper_objects[i].parentNode.getElementsByClassName('twobc_image_gallery_overlay_wrapper')[0];
			new_obj.push(tno);
			new_obj_classes.push(tno.className);
			var tload = wrapper_objects[i].parentNode.getElementsByClassName('twobc_image_gallery_loading')[0];
			loading.push(tload);
			loading_classes.push(tload.className);
			
			var data = {
				'action': 'twobc_image_gallery_generate',
				'twobcig_gallery': '',
				'twobcig_parents': '',
				'ajax_nonce': ajax_object.ajax_nonce,
				'twobcig_sort_method' : script_options.sort_method,
				'twobcig_sort_order' : script_options.sort_order,
				'twobcig_paginate_galleries' : script_options.paginate_galleries,
				'twobcig_images_per_page' : script_options.images_per_page,
				'twobcig_view_style' : script_options.view_style,
				'twobcig_separate_galleries' : script_options.separate_galleries,
				'twobcig_show_months' : script_options.show_months,
				'twobcig_page_num' : script_options.page_num,
				'twobcig_page_id' : script_options.page_id,
				'twobcig_slideshow_delay' : 5000,
				'twobcig_back_button' : ''
			};
			
			var data_args = $(wrapper_objects[i]).data('twobcig-args');
			
			if(data_args) {				
				$.each(data_args, function(i, val) {
					if(val != '') {							
						data['twobcig_'+i] = val;						
					}
				});				
			} else {
				console.log('2BC Image Gallery: Disabling AJAX for image gallery '+(i+1)+' (No data provided in data-twobcig-args)');
				if(wrapper_objects[i].className.indexOf('noajax') == -1){wrapper_objects[i].className+= ' noajax';}				
			}		
			
			data_objs.push(data);
			
			if(wrapper_objects[i].className.indexOf('noajax') != -1) { continue;}
			if(gals[0]) {
				for(var a=0;a<gals.length;a++) {
					gals[a].originalindex = i;
					gals[a].onclick = function(e) {
						e.preventDefault();
						var index = this.originalindex;
						loading[index].className = loading_classes[index]+' show';
						data_objs[index]['twobcig_gallery'] = this.className.split(' ')[1].split('_')[1];
						list_gallery_thumbs(data_objs[index],index);
					}
				} //End of for loop [a]
			} else {
				if(imgs[0]) {
					data_objs[i]['twobcig_gallery'] = imgs[0].className.split(' ')[2].split('_')[2];
					data_objs[i]['twobcig_back_button'] = 0;
					attach_image_thumb_events(data_objs[i],new_obj,i,'disable_back');
				}
			}
		} //End of for loop [i]
	}
});