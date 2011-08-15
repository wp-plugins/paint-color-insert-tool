// closure to avoid namespace collision
/**
* @package Paint Color Insert Tool
* @copyright Copyright (C) 2011 My Perfect Color. All rights reserved.
* @version 1.0
*/ 
(function(){
	// creates the plugin
	tinymce.create('tinymce.plugins.paint_color_inserter', {
		// creates control instances based on the control's id.
		createControl : function(id, controlManager) {
			if (id == 'paint_color_inserter_button') {
				// creates the button
				var button = controlManager.createButton('paint_color_inserter_button', {
					title : 'Paint Color Insert Tool by MyPerfectColor',
					image : plugin_dir + 'button.gif',
					onclick : function() {

						// triggers the thickbox
						var width = jQuery(window).width(), H = jQuery(window).height(), W = ( 720 < width ) ? 720 : width;
						W = W - 80;
						H = H - 84;
						tb_show( 'MyPerfectColor Paint Color Insert Tool', '#TB_inline?width=' + W + '&height=' + H + '&inlineId=paint-color-inserter-form' );
					}
				});
				return button;
			}
			return null;
		}
	});
	
	// registers the plugin.
	tinymce.PluginManager.add('paint_color_inserter', tinymce.plugins.paint_color_inserter);
	
	jQuery(function(){


		var form = jQuery('<div id="paint-color-inserter-form"><table id="paint-color-inserter-table" class="form-table">\
			<tbody>\
			<tr>\
				<th style="width:125px;"><label for="keyword-search">Keyword</label></th>\
				<td><input type="text" id="keyword-search" name="keyword-search" /><span class="submit"><input type="button" id="paint-color-inserter-search" class="button" value="Search" name="search" /></span><br />\
				<small id="small-keyword">Specify keyword to search.</small></td>\
			</tr>\
			<tr>\
				<th style="width:125px;"><label for="target-resource">Resource</label></th>\
				<td><select name="target-resource" id="target-resource">\
					<option value="all">All</option>\
					<option value="color">Color</option>\
					<option value="scheme">Scheme</option>\
					<option value="product">Product</option>\
				</select><br />\
			</tr>\
			<tr id="resource-tr">\
				<th style="width:125px;"><label for="resource-brand">Brand</label></th>\
				<td><select name="resource-brand" id="resource-brand">'
					+available_brands+
				'</select><br />\
			</tr>\
			<tr>\
				<th style="width:125px;"><label for="shortCode-type">ShortCode Type</label></th>\
				<td><select name="short-code-type" id="short-code-type">\
					<option value="image">Image</option>\
					<option value="image-and-title">Image & Title</option>\
					<option value="title">Title</option>\
					<option value="url">URL</option>\
					<option value="image-url">Image URL</option>\
				</select><br />\
				<small>specify the kind of shortcode you wanto to use.</small></td>\
			</tr>\
			<tr id="image-size">\
				<th style="width:125px;"><label for="image-size">Size</label></th>\
				<td><select name="size" id="image-size-select">\
					<option value="thumbnail">Thumbnail</option>\
					<option value="medium">Medium</option>\
					<option value="large">Large</option>\
					</select><br />\
				<small>specify the image size to use.</small></td>\
			</tr>\
		</tbody>\
		</table>\
		<p id="no-results"></p>\
		<p class="submit">\
			<input type="button" id="paint-color-inserter-submit" class="button-primary" value="Insert ShortCode" name="submit" />\
		</p>\
		</div>');

		var table = form.find('table');
		var pager = new Pagination;
		form.appendTo('body').hide();


		form.find('#short-code-type').change(function(){
			var optionType = jQuery(this).val();
			var imgSizeSelect = jQuery('#image-size');
			if(optionType == 'title' || optionType == 'url')
				imgSizeSelect.hide();
			else
				imgSizeSelect.show();

		});

		form.find('#target-resource').change(function(){
			var target_resource = jQuery(this).val();
			var brandSelect = jQuery('#resource-tr');
			if(target_resource == 'scheme')
				brandSelect.hide();
			else
				brandSelect.show();
		});

		form.find('#keyword-search').keypress(function(event){
			 if (event.which == '13') {
				jQuery('#paint-color-inserter-search').click();
			 }
		});

		jQuery(pager.getPageLinkClass()).live('click', function(pagination_link_event){
			pagination_link_event.preventDefault();
			var e = jQuery.Event("click");
			var page_link = jQuery(this);
			var href = page_link.attr('href');

			var page_selected = page_link.attr('rel');
			e.from = pager.url_param('from', href);
			e.step = pager.url_param('step', href);
			e.current_page = page_selected;
			jQuery("#paint-color-inserter-search").trigger(e);

		});

		// handles the click event of the search button
		form.find('#paint-color-inserter-search').click(function(e){

			jQuery('#no-results').text('loading.');
			iPCI = setInterval("loadingPCI()",1000);
			toPCI = setTimeout("intervalErrorPCI()",20000);
			var imageHost = 'http://images.myperfectcolor.com/';
			var siteUrl = 'http://www.myperfectcolor.com/index.php';
			var keyword = jQuery('#keyword-search').val();
			var resource = jQuery('#target-resource').val();
			var brand = jQuery('#resource-brand').val();


			if(!keyword){
				alert('Please, enter a keyword to search.');
				jQuery('#small-keyword').css('color','red');
				return false;
			}

			// Remove old results
			jQuery('tfoot#search-results').remove();


			jQuery.ajax({
				type: "GET",
				url: plugin_dir + "webService.php",
				data: {
					url : siteUrl,
					component : 'webservice',
					controller : 'CWebservice',
					action: 'search',
					keyword: keyword,
					resource: resource,
					brand: brand,
					from: e.from,
					to: e.step,
					plugin: pci_name,
					email: pci_email
				},
				dataType: "xml",
				success: function(xml){
					clearInterval(iPCI);
					clearTimeout(toPCI);
					jQuery('#no-results').text('');
					if(!jQuery(xml).find('[name=response]').children().length){
						jQuery('#no-results').html('No matchs for "<b>' + keyword + '</b>". Try another search.').css('text-align','center').insertAfter(table);
						return false;
					}

					var tfoot = jQuery('<tfoot id="search-results"></tfoot>');

					var result_rows = (jQuery(xml).find('result')).attr('numFound');


					
					jQuery(xml).find('doc').each(function(){
						var node = jQuery(this);
						var id,name,brand,title,colorNumber,colorCode,imgSrc,imgCode,row;
						
						var tab = node.find('[name=tab]').text();
						

						switch(tab){
							case 'color':
								id = node.find('[name=color_id]').text();
								name = node.find('[name=colorName]').text();
								brand = node.find('[name=brandName]').text();
								title = node.find('[name=title]').text();
								colorNumber = node.find('[name=colorNumber]').text();
								colorCode = node.find('[name=colorCode]').text();
								imgSrc = imageHost + 'repositories/images/colors/'+ colorCode + '-0.jpg';

								row = jQuery('<tr id="rrow_' + id +'"></tr>');
								row.append('<td style="display:none" class="id">' + id + '</td>');
								row.append('<td style="display:none" class="name">' + name + '</td>');
								row.append('<td style="display:none" class="brand">' + brand + '</td>');
								row.append('<td style="display:none" class="colornumber">' + colorNumber + '</td>');
								row.append('<td style="display:none" class="colorcode">' + colorCode + '</td>');
								row.append('<td style="display:none" class="tab">color</td>');


								break;

							case 'product':
								id = node.find('[name=product_id]').text();
								name = node.find('[name=product_name]').text();
								title = node.find('[name=title]').text();
								imgCode = node.find('[name=image] :first-child').text();
								imgSrc = imageHost + 'repositories/images/products/'+ imgCode + '-0.jpg';

								row = jQuery('<tr id="rrow_' + id +'"></tr>');
								row.append('<td style="display:none" class="id">' + id + '</td>');
								row.append('<td style="display:none" class="name">' + name + '</td>');
								row.append('<td style="display:none" class="imgcode">' + imgCode + '</td>');
								row.append('<td style="display:none" class="tab">product</td>');
								break;

							case 'scheme':
								id = node.find('[name=scheme_id]').text();
								name = node.find('[name=schemeName]').text();
								title = node.find('[name=title]').text();
								imgSrc = imageHost + 'repositories/images/schemes/scheme-'+ id + '-0.jpg';

								row = jQuery('<tr id="rrow_' + id +'"></tr>');
								row.append('<td style="display:none" class="id">' + id + '</td>');
								row.append('<td style="display:none" class="name">' + name + '</td>');
								row.append('<td style="display:none" class="tab">scheme</td>');
								
								break;


						}

						// VISIBLE ROWS
						row.append('<td><label for="'+ id +'"><img rel="'+ id +'" onerror="javascript:errorImgPCI(this)" src="' + imgSrc +'"/></label></td>');
						row.append('<td class="title"><label for="'+ id +'">' + title + '</label></td>');
						row.append('<td><input type="radio" name="result-row" value="' + id +'" id="'+ id +'"/></td>');


						table = jQuery('#paint-color-inserter-table');

						tfoot.append(row);



					});

					table.append(tfoot);
					pager.init(result_rows,e.current_page);
					tfoot.append(jQuery(pager.create_links()));

					return false;
				}
			});
		});


		// handles the click event of the submit button
		form.find('#paint-color-inserter-submit').click(function(){


			var rowSelected = jQuery('input[name=result-row]:checked');
			var imgSizeSelect = jQuery('#image-size-select');
			if(!rowSelected.val()){
				alert('Please, select a Product.');
				return false;
			}else if(imgSizeSelect.is(':visible') && rowSelected.attr('rel') == 'no-image'){
				alert('Images not available for this shortcode.');
				return false;

			}



			// defines the options for each kind of shortcode
			var colorOptions = ['brand','name','id','title','colornumber','colorcode','tab'];
			var schemeOptions = ['name','id','title','tab'];
			var productOptions = ['name','id','title','imgcode','tab'];

			var options;
			var tab = jQuery('tfoot#search-results tr#rrow_'+ rowSelected.val() + ' td.tab').text();

			switch(tab){

				case 'color':
					options = colorOptions;
					break;

				case 'scheme':
					options = schemeOptions;
					break;

				case 'product':
					options = productOptions;
					break;
			}


			var shortcode = '[mpc-paint-color-insert-tool';

			
			shortcode += ' ' + 'type' + '="' + jQuery('#short-code-type').val() + '" ';

			for( var index in options) {
				var search = 'tfoot#search-results tr#rrow_'+ rowSelected.val() + ' td.' + options[index];
				var value = jQuery(search).text();

				if(value != '')
					shortcode += ' ' + options[index] + '="' + value + '" ';
			}

			if(imgSizeSelect.is(':visible')){
				shortcode += ' ' + 'imgsize' + '="' + imgSizeSelect.val() + '" ';
			}

			shortcode += ' /]';
			
			// inserts the shortcode into the active editor
			tinyMCE.activeEditor.execCommand('mceInsertContent', 0, shortcode);

			jQuery('#small-keyword').css('color','black');
			// closes Thickbox
			tb_remove();
		});
	});
})()


function errorImgPCI(img){

	var noImgSrc = encodeURI('http://images.myperfectcolor.com/website/templates/1_default/images/no_image.png');
	var image = jQuery(img);
	image.attr('src',noImgSrc);

	var rrow = jQuery('input[name=result-row]').filter('#' + image.attr('rel'));
	rrow.attr('rel', 'no-image');
}

var iPCI;
var toPCI;
function loadingPCI(){
	jQuery('#no-results').text(jQuery('#no-results').text()+'.');
}

function intervalErrorPCI(){
	clearInterval(iPCI);
	jQuery('#no-results').text('>> connection error <<');
}
