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
	
	function attach_image_thumb_events(data_obj,new_obj,index,bb_listen) {
		
		//Back Button Listener if applicable
		
		var backbutton = new_obj[index].getElementsByClassName('twobc_galleries_back')[0];
		if(backbutton) {
			backbutton.originalindex = index;
			if(bb_listen != 'disable_back') {
				backbutton.onclick = function(e) {
					e.preventDefault();
					var index = this.originalindex;
					data_objs[index]['page_num'] = 1;
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
						var parsed = this.href.split('&')[1];
						if(parsed) {
							data_objs[index]['page_num'] = parsed.split('=')[1];
							loading[index].className = loading_classes[index]+' show';
							list_gallery_thumbs(data_obj,index);
						}
					}
				} else {
					page_numbers[d].onclick = function(e) {
						e.preventDefault();
						var index = this.parentNode.id.split('_')[1];
						data_objs[index]['page_num'] = this.innerHTML;
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
				var str1 = this.className.split(' ')[1].split('_')[1]
				var data1 = {
					'action': 'twobc_image_gallery_image_generate',
					'image_id': str1,
					'ajax_nonce': ajax_object.ajax_nonce
				};
				$.post(ajax_object.ajax_url, data1, function(response1) {
					loading[index].className = loading_classes[index];
					picoModal(response1);
					var pico_content = document.getElementsByClassName('twobc-pico-content')[0];
					
					if(pico_content) {
						resize_handler(pico_content);
						$(window).resize(function() {
							resize_handler(pico_content);
						});
						var close_button = pico_content.getElementsByClassName('pico-close')[0];
						var overlay_listener = document.getElementsByClassName('pico-overlay')[0];
						if(overlay_listener) {
							overlay_listener.onclick=function(){$(window).off("resize");};
						}
						if(close_button) {
							close_button.onclick=function(){$(window).off("resize");};
						}
					}
				});
			}
		}
	}
	
	//Resize handler for when viewing an image in modal
	
	function resize_handler(obj) {
			
		var parsed = obj.innerHTML.split(' ');
		
		var iwidth = 0;
		var iheight = 0;
		var swidth = $(window).width();
		var sheight = $(window).height();
		for(var c=0;c<parsed.length;c++) {
			if(parsed[c].indexOf('width=') != -1) {iwidth = Number(parsed[c].split('"')[1]);}
			if(parsed[c].indexOf('height=') != -1) {iheight = Number(parsed[c].split('"')[1]);}
		}
		var irat = iwidth/iheight;
		var nwidth = iwidth;
		var nheight = iheight;
		var finished = false;
		
		if(iwidth > swidth*.8) {
			nwidth = swidth*.8;
			nheight = nwidth/irat;
			if(nheight > sheight*.8) {
				nheight = sheight*.8;
				nwidth = nheight*irat;
				finished = true;
			}
		}
		if(iheight > sheight*.8 && finished == false) {
			nheight = sheight*.8;
			nwidth = nheight*irat;

			if(nwidth > swidth*.8) {
				nwidth = swidth*.8;
				nheight = nwidth/irat;
			}
		}
		obj.style.width = nwidth+'px';
		obj.style.height = nheight+'px';
		obj.style.marginLeft = '-'+nwidth/2+'px';
		obj.style.top = (sheight-nheight)/2+'px';
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
				'gallery': '',
				'parents': '',
				'ajax_nonce': ajax_object.ajax_nonce,
				'sort_method' : script_options.sort_method,
				'sort_order' : script_options.sort_order,
				'paginate_galleries' : script_options.paginate_galleries,
				'images_per_page' : script_options.images_per_page,
				'view_style' : script_options.view_style,
				'separate_galleries' : script_options.separate_galleries,
				'show_months' : script_options.show_months,
				'page_num' : script_options.page_num,
				'page_id' : script_options.page_id,
				'back_button' : ''
			};
			
			var class_parser = wrapper_objects[i].className.split(' ');
			for(var d=0;d<class_parser.length;d++) {
				var option_value = class_parser[d].split('_');
				option_value = option_value[option_value.length-1];
				switch(true) {
					case(class_parser[d].indexOf('sort_method_') != -1): 
						data['sort_method'] = option_value;
						break;
					case(class_parser[d].indexOf('sort_order_') != -1): 
						data['sort_order'] = option_value;
						break;
					case(class_parser[d].indexOf('paginate_galleries') != -1): 
						data['paginate_galleries'] = option_value;
						break;
					case(class_parser[d].indexOf('images_per_page') != -1): 
						data['images_per_page'] = option_value;
						break;
					case(class_parser[d].indexOf('view_style') != -1): 
						data['view_style'] = option_value;
						break;
					case(class_parser[d].indexOf('separate_galleries') != -1): 
						data['separate_galleries'] = option_value;
						break;
					case(class_parser[d].indexOf('show_months') != -1): 
						data['show_months'] = option_value;
						break;
					case(class_parser[d].indexOf('parents') != -1):
						var listing = class_parser[d].split('_');
						var parent_list_local = '';
						for(var h=1;h<listing.length;h++) {
							if(h>1) { parent_list_local += ','; }
							parent_list_local += listing[h];
						} //End of for loop [h]
						data['parents'] = parent_list_local;
					default:
				}
			} //End of for loop [d]
			
			data_objs.push(data);
			
			if(wrapper_objects[i].className.indexOf('noajax') != -1) { continue;}
			if(gals[0]) {
				for(var a=0;a<gals.length;a++) {
					gals[a].originalindex = i;
					gals[a].onclick = function(e) {
						e.preventDefault();
						var index = this.originalindex;
						loading[index].className = loading_classes[index]+' show';
						var str=this.className.split(' ')[1].split('_')[1];
						data_objs[index]['gallery'] = str;
						list_gallery_thumbs(data_objs[index],index);
					}
				} //End of for loop [a]
			} else {
				if(imgs[0]) {
					var gal = imgs[0].className.split(' ')[2].split('_')[2];
					data_objs[i]['gallery'] = gal;
					data_objs[i]['back_button'] = 0;
					attach_image_thumb_events(data_objs[i],new_obj,i,'disable_back');
				}
			}
		} //End of for loop [i]
	}
});