$(function(){
	var ajax_css = "<style type='text/css'>";
		ajax_css += "#categories ul ul { display: none }";
		ajax_css += "#wait { background:url(images/wait.gif) no-repeat center center; height:100%; width:100%; position:fixed; top:30px;}";
		ajax_css += "</style>";
	$("head").append($(ajax_css));
	anime_delay = 300;
	ajax_init();
});

function ajax_init() {
	$.getJSON('index.php',{ 
		ajax : 'ajax_init'
	},
	function(data){
		inject(data);
		init_menu();
		anime_menu(true);
		// $(window).bind('hashchanged', function(){
		$(window).bind('end_anime_menu', function(){
			get_content("GET", window.location.href.replace(/#/, ''),"ajax=get_content");
		}); 

		$(window).bind('hashchanged', function(){
			anime_menu(true);
		});
		
		parse_link(document.body);
		parse_form(document.body);
		check_hash();
	});
}
 
function check_hash() {
	if (typeof prev_hash == 'undefined') prev_hash = window.location.hash;
	var hash = window.location.hash;
	if (hash != prev_hash) {
		prev_hash = hash;
		$(window).trigger('hashchanged');
	}
	setTimeout(check_hash, 100);
}

function inject(data){
i=0;
	for(id in data){
		for(attr in data[id]){
			var el = $('#'+id);
			switch(attr) {
				case 'html' :
					switch(el.attr('tagName')) {
						case 'TITLE': $(document).attr('title', data[id][attr]); break;
						default:
							if(el.hasClass("main-content")) {
								el.fadeOut(100, function(){
								// alert(id+"!="+$(this).attr('id')); //WTF ?
									$(this).html(data[$(this).attr('id')][attr]);
									$(this).fadeIn(100, function(){
										parse_link('#'+$(this).attr('id'));
										parse_form('#'+$(this).attr('id'));
									});
								});
							}
							else {
								try{
									el.html(data[id][attr]);
								}
								catch(e){
									//Pb <style> innerHTHL IE
									alert(e.description);
									$('#debug_main').prepend("Error injecting data:"+e.description);
								}
								parse_link('#'+el.attr('id'));
								parse_form('#'+el.attr('id'));
							};
						break;
					};
				break;
				case 'action' :
					$('#'+id+' input:first').each(function(){
						this.form.setAttribute("action", data[id][attr]);
					});
				break;
				default:
					el.attr(attr, data[id][attr]);
				break;
			}
		}
	}
}

function init_menu(menu) {
	$('#categories a').each(function(){
		if (typeof $(this).attr('href') != 'undefined') $(this).attr('id', id_cat($(this).attr('href')));
	});
}

function parse_form(container) {
	$('form', container).each(function(){
		$('input:first',this).each(function(){
			
		try {
			this.form.submit = function (){
				$(this).trigger('submit');
				return false;
			};
		}
		catch (e){ //IE 6+
			try {
					this.form.setAttribute('submit', function (){
					$(this).trigger('submit');
					return false;
				});
			}
			catch (e){ //IE 6
				//
			}
		}
		});
	}).bind('submit', ajax_submit); 
}

function ajax_submit() {
	var action_tab = $(this).attr('action').split(/\?/);
	var data = new Array();
	var ind=action_tab.length-1;
	if (typeof action_tab[ind]!="undefined" && action_tab[ind]!="") {
		var action = "#"+action_tab[ind];
		data.push(action_tab[ind]);
		if (!action_tab[ind].match(/ajax=get_content/)) 
		data.push('ajax=get_content');
	}
	else{
		var action = "#";
		data.push('ajax=get_content');
	}

	prev_hash = action;
	window.location.hash = action;
	var accepted_input = new Array('checked','text','password','hidden', 'submit','image');
	var inputs_selector = 'select,textarea,input:'+ accepted_input.join(', input:');

	$(inputs_selector, this).each(function(){
		if($(this).attr('name')!=""&&$(this).val()!="")
		data.push($(this).attr('name')+"="+$(this).val());
	});

	var datastring = data.join('&');
	get_content("POST", $(this).attr('action'), datastring);
	return false;
}

function parse_link(container) {
	var baseHref = $('base').attr('href');
	var regBase = new RegExp(baseHref,"g");
	$('a', container)
	.filter(function() {
		return (typeof this.href != 'undefined') && // Avoid error message if href is undefined
		(this.href.match(regBase)) && // Only links to current site
		($(this).attr('target')!='_blank') && 
		(this.href!=baseHref+"admin/index.php");
	})
	.each(function() {
			if (this.hash=='') $(this).attr('href', this.href.replace(baseHref, '#'));
			else $(this).attr('href', this.hash);
	});
}

function get_content(type, url, data){
	start_wait();
	setTimeout("stop_wait()",5000);
	$.ajax({
		type: type,
		url: url,
		dataType:"json",
		data: data,
		success:function(data){
			inject(data);
			$(window).trigger('end_get_content');
			stop_wait();
			if(window.location.hash.match(/change_lang=true/)){ 
				init_menu('#categories');
				anime_menu(false);
			}
		},
		error:function (xhr, ajaxOptions, thrownError){
			stop_wait();
			var handle_response = xhr.responseText.split('{');
			var err_response = handle_response[0];
			handle_response.shift();
			var rebuild_json= '({'+handle_response.join('{')+')';
			try {
				inject(eval(rebuild_json));
			}
			catch(e) {
				// alert(e.description)
				$('#debug_main').prepend("Error getting JSON data:"+xhr.responseText);
			}
			if($('#debug_main').length)
			// alert(err_response);
			$('#debug_main').prepend(err_response);
		}    
	});
}

function id_cat(hash) {
	var base='cat_';
	var reg1=new RegExp('(#category/|#content/)([0-9]+)/(.*)(\.htm(l?))');
	var reg2=new RegExp('#(.+)?\.htm(l?)');
	if (hash.match(reg1)) {
		return base+reg1.exec(hash)[2];
	} else if (hash.match(reg2)) {
		return base+reg2.exec(hash)[1];
	}
}
function anime_menu (trigger_end_anime) {
	var cat = $('#'+id_cat(window.location.hash));
	var sh0_cat = $('.sh0_cat');
	var anime_hide = 0;
	var anime_show = 0;
	
	$('#categories .active').removeClass('active');
	sh0_cat.addClass('hid_cat');// On marque les catégories affichés pour un éventuel effacement
	cat.addClass('active');
	cat.parents('#categories ul ul').addClass('sh1_cat').removeClass('hid_cat');// On marque le groupe qui doit être affiché et 
	cat.parent().children('#categories ul ul').addClass('sh1_cat').removeClass('hid_cat'); // on retire le marqueur d'effacement
	var hid_cat = $('#categories .hid_cat');
	var sh1_cat = $('.sh1_cat');
	sh1_cat.not('#categories .sh0_cat').addClass('app_cat'); // On marque les classe qui doivent apparaissent
	var app_cat = $('#categories .app_cat');
	sh0_cat.removeClass('sh0_cat');// On supprime cette classe obsoléte
	if(app_cat.length) {
		app_cat.not(':first').css('display', 'block');
		app_cat.filter(':first').animate({ "height": "show" },anime_delay, function() {
			if (anime_hide == 1 && trigger_end_anime) {
				$(window).trigger('end_anime_menu');
			}
			else{ 
				anime_show = 1;
			}
		});
		app_cat.removeClass('app_cat');// On supprime cette classe obsoléte
	}
	else {
		if (anime_hide == 1 && trigger_end_anime) {
			$(window).trigger('end_anime_menu');
		}
		else{ anime_show = 1;}
	}
	if(hid_cat.length) {
		hid_cat.filter(':first').animate({ "height": "hide" },anime_delay, function() {
			hid_cat.not(':first').css('display', 'none');
			if (anime_show == 1 && trigger_end_anime) {
				$(window).trigger('end_anime_menu');
			}
			else {
				anime_hide = 1;
			}
		});
		hid_cat.removeClass('hid_cat');// On supprime cette classe obsoléte
	} else {
		if (anime_show == 1 && trigger_end_anime) {
			$(window).trigger('end_anime_menu')
		}
		else {
			anime_hide = 1;
		}
	}
	sh1_cat.addClass('sh0_cat').removeClass('sh1_cat');// On supprime cette classe obsoléte
}

function start_wait() {
	if(!$('#wait').length) {
		var div_wait = document.createElement('div');
		document.body.appendChild(div_wait);
		$(div_wait).attr('id', 'wait');
	}
	else {
		$('#wait').show();
	}
}

function stop_wait() {
	$('#wait').fadeOut(anime_delay);
}