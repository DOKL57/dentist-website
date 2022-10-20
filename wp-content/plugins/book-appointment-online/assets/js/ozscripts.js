/**************************************************************************
 * oz_scripts.min.js - jQuery Plugin for Book appointment
 * @version: 3.1.0 (27.12.2021)
 * @requires jQuery v1.7 or later (tested on 1.9)
 * @author Ozplugin
**************************************************************************/
jQuery.noConflict();

/**
 *  @brief Disable date if it's employee dayoff
 *  
 *  @param [in] fal 1 or 0
 *  @param [in] date current Date
 *  @param [in] daysoff list comma separated days off
 *  @param [in] ktorabotaet array with employee schedule on current day
 *  @param [in] popup string element class with schedule and id of employee
 *  @return fal
 *  
 *  @details 2.0.5
 */
function book_oz_daysOff(fal,date, daysoff, ktorabotaet = '', popup = '') {
	if (fal > 0 && daysoff ) {
		var skipPers = false;
		 if (typeof daysoff == 'string' && daysoff.indexOf('{') > -1) {
		var daysoff = JSON.parse(daysoff);
		var skipPers = true;
		 }
		else if (typeof daysoff == 'object') {
		var skipPers = true;
		}		
		else {
		var daysoff = daysoff.split(',');
		}
		if (skipPers) {
			for (i=0; i<ktorabotaet.length; i++) {
				var pers = ktorabotaet[i].pId;
				var date = moment(ktorabotaet[i].day,'YYYY-MM-DD').format('DD.MM.YYYY');
				for (k=0; k < daysoff.length; k++) {
					if (pers in daysoff[k] && daysoff[k][pers].indexOf(date) > -1) {
						var excludeSotr = popup.trim().split(' ');
						var excludeSotrArr = excludeSotr.map(function(obj,ind) { return obj.split('-'); });
						var excludeSotrArrIndex = excludeSotrArr.map(function(obj,ind) { return parseInt(obj[obj.length-1]); }).indexOf(pers);
						if (excludeSotrArrIndex > -1) {
							if (excludeSotrArr.length == 1) {
							var includeSotrArr = '';
							}
							else {
								var incSotr = [];
								for (j=0; j<excludeSotrArr.length; j++) { 
								if (j != excludeSotrArrIndex) {
								incSotr.push(excludeSotrArr[j]);
								}
								}
							var includeSotrArr = incSotr;
							}
							//var includeSotrArr = (excludeSotrArr.length == 1) ? '' : excludeSotrArr.splice(excludeSotrArrIndex,1);
							popup = (typeof includeSotrArr == 'string') ? includeSotrArr : includeSotrArr.map(function(obj,ind) { return obj.join('-')}).join(' ');					
}
					}
				}
			}
		var popup = (popup == '') ? 0 : popup; 
		return popup;
		}
		else {
		var date = moment(date).format('DD.MM.YYYY');
				if (daysoff.indexOf(date) > -1) {
				return 0;
			}
		}
	}
	return fal;
}

/**
var oz_alang - array of translate strings
**/

 /**
 *  @brief Select text on click
 *  
 *  @param [in] containerid DOM element
 *  @return void
 *  
 *  @details 2.2.6
 */
 function selectText(containerid) {
    if (document.selection) { // IE
        var range = document.body.createTextRange();
        range.moveToElementText(containerid);
        range.select();
		document.execCommand("copy");
    } else if (window.getSelection) {
        var range = document.createRange();
        range.selectNode(containerid);
        window.getSelection().removeAllRanges();
        window.getSelection().addRange(range);
		document.execCommand("copy");
    }
}

document.addEventListener('click', function(e) {
	if (e.target.className && e.target.className == 'oz_code') {
			selectText(e.target);
	}
});


/**
 *  @brief AJAX request with fetch
 *  
 *  @param [in] content query selector for answer text
 *  @param [in] url ajax url
 *  @return true of false;
 *  
 *  @details 2.1.6
 */
 
const post_types = {};

function oz_postRequest(content = null, data = {}, url, echo = true) {
	if (data.post_type && typeof post_types[data.post_type] != 'undefined') return post_types[data.post_type];
	if(typeof Promise !== "undefined" && Promise.toString().indexOf("[native code]") !== -1){
		if (content) {
		var content = document.querySelector(content);
		var url =  url || oz_vars.adminAjax; 
		var params = new URLSearchParams();
		if (data) {
		for(var key in data){
		params.set(key, data[key]) 
		}
		}
		return fetch(url, {
		credentials: 'same-origin', // 'include', default: 'omit'
		method: 'POST', // 'GET', 'PUT', 'DELETE', etc.
		body: params, // Coordinate the body type with 'Content-Type'
		headers: new Headers({
		  'Content-Type':'application/x-www-form-urlencoded'
		}),
	  })
	  .then(
		function(Promise) {
			return Promise.json();
		}, 
		function(err) {
			return err.json();
		})
		
		.then(function(response) {
		var event= new CustomEvent('onOzAjaxEnd', {detail:{response:response, content:content}});
		document.addEventListener('onOzAjaxEnd',function(){},false);
		document.dispatchEvent(event);
		if (!echo) { 
		if (data.post_type) {
			post_types[data.post_type] = response;
		}
		return response; }
		else {
			content.innerHTML = typeof(response.text) == 'object' ? JSON.stringify(response.text) : response.text;
		}
		})
		}
	}
	else {
		return false;
	}
}


Array.prototype.objIndexOf = function(val) {
    var cnt =-1;
    for (var i=0, n=this.length;i<n;i++) {
      cnt++;
      for(var o in this[i]) {
        if (this[i].hasOwnProperty(o) && this[i][o]==val) return cnt;
      }
    }
    return -1;
}

Array.prototype.next = function() {
    return this[++this.current];
};

Array.prototype.prev = function() {
    return this[--this.current];
};

Array.prototype.current = 0;

if (typeof book_oz_timeFormat === 'undefined') {
	function book_oz_timeFormat(time) {
		if (typeof oz_vars !== 'undefined' && typeof oz_vars.AMPM !== 'undefined' && oz_vars.AMPM ) {
			var time = moment(time, 'HH:mm').format('hh:mm a'); // ampm
		}
		return time;
	}
}

/*select исключаем услуги*/

function oz_book_changeSelect($this) {
	switch($this) {
		case 'all' :
		jQuery('#book_oz_worktime').hide();
		jQuery('#book_oz_worktime .at-repater-block').remove();
		break;
		case 'exclude' :
		jQuery('#book_oz_worktime').show();
		break;
		case 'include' :
		jQuery('#book_oz_worktime').show();
		break;
		}
	}
	
oz_book_changeSelect(jQuery('#oz_book_provides_services').val());
 
 /*******************************сотрудники**************************************/
 
  /*генерируем рабочие дни для календаря по графику 2 через 2 */
  
  function book_oz_cli_plav_bus_days(rasp) {
	 var now = moment(rasp[0].dayF,'DD.MM.YYYY');
	 var rabd = rasp[0].graph.split('/')[0];
	 var vihd = rasp[0].graph.split('/')[1];
	 var start = new Date();
	 var rab = 0;
	 var plav_bus_days = [];
	 plav_bus_days.push({
		 day: start
	 });
	 for (i=0; i < 180; i++) {
		 if (rab == rabd) {
		now.add(vihd, 'days');	
		rab = 0;
		 }
 		 plav_bus_days.push({
			 day: now.format('YYYY-MM-DD'),
/* 			 start: v.start,
			 end: v.end */
		 }) 
		 rab++;
		 now.add(1, 'days');
	 }
	 
	 plav_bus_days.push({
		 day: new Date() - start
	 });
	 
	 return plav_bus_days;
  }
 
 
 /*генерируем рабочие дни для календаря*/
 
 function oz_bus_days(nrasp) {
	 var bus_days = [];
jQuery.each(nrasp, function(i,v) {
	 var now = moment();
	 for (i=0; i < 180; i++) {
	var dayName = now.locale('en').format('ddd').toLowerCase();
 		 if (jQuery.inArray('oz_'+dayName,v.days) > -1) {
			 now.format('YYYY-MM-DD');
 		 bus_days.push({
			 day: now.format('YYYY-MM-DD'),
			 start: v.start,
			 end: v.end
		 }) 
		 } 
		 now.add(1, 'days');
	 }
	})	
	return bus_days;	
 }
 
 function showDefault(table) {
		if (table.find('tr').length > 2) {
	table.find('tr.remove').hide();
	}
	else {
	table.find('tr.remove').show();
	}
}
function oz_days(id) {
		var days = [];
		jQuery('#'+id+' .oz_day input:checked').each(function() {
			days.push(jQuery(this).attr('name'));
		});
		return days;
	}
	
function checkChecked(table = false) {
		if (table) {
				table.find('.add-date').removeClass('hide');
		}
		else {
		jQuery('.oz_worktime, .oz_table_time').each(function() {
			if (jQuery(this).find('.times-line .oz_day input:checked').length < 7) {
				jQuery(this).find('.add-date').removeClass('hide');
				}
			});
		}
}
	
function removeDate() {
	jQuery('.remove-date').click(function() {
	var table = jQuery(this).parents('table');
	if (removeClick) {
		return false;
	}
		var rod = jQuery(this).parent().parent();
		var rodId = rod.attr('id');
		rod.remove();
		showDefault(table);
		if (table.find('.times-line').length <= 2) {
		table.find('.add-date').removeClass('hide');
		}
		if (table.hasClass('oz_worktime')) {
		rasp = rasp.filter(function(e, i) {
			return rasp[i]['id'] !== rodId;
		});	
		jQuery('#oz_raspis').val(JSON.stringify(rasp));
		}
		else {
			var values = jQuery('input#'+table.attr('data-values'));
			addValueTimelist(table,values);
		}
		checkChecked(table);
	});
}

/* выводим постоянный график*/

function postGraph(rasp) {
if (!rasp.hasOwnProperty('day') && typeof rasp[0] == 'undefined') {
	rasp = [];
	}
	

 	rasp.sort(function(a, b) {
    return a.id.valueOf() > b.id.valueOf();
}) 
	nrasp = [];
	tr = '';
	days1 = [];
	jQuery.each(rasp, function(i,v) {
		days1.push(v.day);
		tr =  rasp.next();
		if (typeof tr !== 'undefined' && tr['id'] != v.id) {
		nrasp.push({
			id: v.id,
			days: days1 ,
			start:v.start,
			end:v.end,
		})
		days1 = [];
		}
		if (typeof tr == 'undefined') {
		nrasp.push({
			id: v.id,
			days: days1 ,
			start:v.start,
			end:v.end,
		})
		}
	})
jQuery.each(nrasp, function(i,v) {
		var week = ['mon','tue','wed','thu','fri','sat','sun'];
		td = '';
		jQuery.each(week, function(g,z) {
		checked = (jQuery.inArray('oz_'+z,v.days) > -1) ? 'checked="checked"' : '';
		td += '<td class="oz_day oz_'+z+'"><input name="oz_'+z+'" disabled id="oz_'+z+'" '+checked+' type="checkbox"></td>';
		});
		jQuery('.oz_worktime tbody').append(
		'<tr id="'+v.id+'" class="times-line">'+
		'<td class="time-show">'+v.start+' - '+v.end+'</td>'+td+
		'<td><span class="remove-date dashicons dashicons-no-alt"></span></td>'+
		'</tr>');
		td = '';
		});
}

/* функция выводит расписание в админке в персонале. на данный момент выводит расписание перерыров*/
function generateRaspisanie(table) {
	var values = table.attr('data-values');
	var raspisanie = (jQuery('input#'+values).length && jQuery('input#'+values).val() !== '') ? JSON.parse(jQuery('input#'+values).val()) : false;
		if (raspisanie) {
	jQuery.each(raspisanie, function(i,line) {
		var week = ['mon','tue','wed','thu','fri','sat','sun'];
		td = '';
		jQuery.each(week, function(g,z) {
		checked = ('oz_'+z === line.day) ? 'checked="checked"' : '';
		td += '<td class="oz_day oz_'+z+'"><input name="oz_'+z+'" disabled id="oz_'+z+'" '+checked+' type="checkbox"></td>';
		});
		table.find('tbody').append(
		'<tr id="'+line.id+'" class="times-line">'+
		'<td class="time-show" data-start="'+line.start+'" data-end="'+line.end+'">'+line.start+' - '+line.end+'</td>'+td+
		'<td><span class="remove-date dashicons dashicons-no-alt"></span></td>'+
		'</tr>');
		td = '';
		});
		}
}

/* обновляем массив значений строк расписания (перерыров) и добавляем этот массив в значение */
function addValueTimelist(table,values) {
	var massiv = (typeof massiv === 'undefined') ? [] : massiv;
	jQuery(table).find('tr.times-line .oz_day input:checked').each(function() {
	var parents = jQuery(this).parents('.times-line');
	massiv.push({
		day:jQuery(this).attr('name'),
		start:parents.find('.time-show').attr('data-start'),
		end:parents.find('.time-show').attr('data-end'),
		id: parents.attr('id'),
		pId: postId
		});	
	});
	values.val(JSON.stringify(massiv));
}

generateRaspisanie(jQuery('.oz_breaktime'));

/****************************начало скриптов*****************************/

(function( $ ) {
	
$(document).ready(function(e){
	jQuery('.oz_select_ajax').each(function() {
		var id = '#'+jQuery(this).attr('id');
		var value = jQuery(this).attr('data-value');
		var type = jQuery(this).attr('data-type') || jQuery(this).attr('name');
		var services = oz_postRequest(id, {'post_type':type, 'action': 'oz_types'}, null, false);
		services.then(function(services) {
			if (typeof services == 'object') {
				for (service in services) {
					let selected = value && services[service].ID == value ? 'selected="selected"' : '';
					jQuery(id).append('<option '+selected+' value="'+services[service].ID+'">'+services[service].post_title+'</option>');
				}
				jQuery(id).select2();
			}
		},
		function(e) {
			console.log(e);
		});
		jQuery(id).select2();	
	});

});

/**
 *  @brief Auto updates response
 *  
 *  @param [in] e event
 *  @return void
 *  
 *  @details 2.2.0
 */
document.addEventListener('onOzAjaxEnd',function(e){
	if (typeof e.detail.response.text == 'object') {
		if (e.detail.response.text.hasOwnProperty('activated')) {
			var content = document.querySelector('.oz_activateMess');
			switch (e.detail.response.text.activated) {
				case 200 :
				content.innerHTML = 'Auto update until: '+e.detail.response.text.text;
				break;
				case 300 :
				content.innerHTML = 'This code is activated on other domain';
				break;
				case 400 :
				content.innerHTML = 'Wrong purchase code';
				break;
				case 405 :
				content.innerHTML = 'Problem with the activation. Contact our support team';
				break;
			}
		}
		
	}
},false);
	
$(document).ready(function() {
	

removeClick = false;
if ( typeof rasp  != 'undefined' && rasp instanceof Array) {
if (typeof rasp[0] !== 'undefined' && typeof rasp[0].dayF !== 'undefined' ) {
	$('input#oz_stab').prop('checked', false); 
	$('input#oz_plav').prop('checked',true);
	$('.plav_grafh, .oz_worktime').toggleClass('hide');
	
 	var bus_hour = book_oz_cli_plav_bus_days(rasp);
	$('.oz_worktime').addClass('hide');
	$('.plav_time_text b').text(rasp[0].graph+' с '+rasp[0].start+' до '+rasp[0].end+'. '+oz_alang.str1+' '+rasp[0].dayF);
}

else if (typeof rasp[0] !== 'undefined' && typeof rasp[0].days !== 'undefined' ) {
	$('#oz_custom').prop('checked',true);
	$('input#oz_plav, #oz_stab').prop('checked',false);
	$('.plav_grafh, .oz_worktime').addClass('hide');	
	$('.custom_grafh').removeClass('hide');	
			var bus_hour = [];
			for (k=0; k<rasp.length; k++) {
				var dates = rasp[k].days;
				
				for (l=0; l<dates.length; l++) {
					var date = dates[l];
					bus_hour.push({
					 day: moment(date,'DD.MM.YYYY').format('YYYY-MM-DD'),
					 start: rasp[k].time.start,
					 end: rasp[k].time.end,
					 pId: rasp[k].pId
					})
				}
			}
}

else {
	$('#oz_stab').prop('checked',true);
	$('input#oz_plav').prop('checked',false);
	$('.plav_grafh').addClass('hide');
	postGraph(rasp);
	var bus_hour = oz_bus_days(nrasp);
}
}

	$('.plav_time_text span').click(function() {
		$('.plav_grafh_block').removeClass('hide');
	if (rasp) {
	if (rasp[0].hasOwnProperty('day')) {
			rasp = [];
		}
	}
	});

if ($('#calendar').length) {
	$('#calendar').fullCalendar({
	header: {
		left: 'prev,next today',
		center: 'title',
		right: 'month,agendaWeek,agendaDay,listDay'
	},
	locale: oz_vars.lang ? oz_vars.lang.split('_')[0] : 'en',
	defaultView: 'listDay',
	navLinks: true, // can click day/week names to navigate views
	selectable: false,
	selectHelper: true,
	select: function(start, end) {
		var title = prompt('Event Title:');
		var eventData;
		if (title) {
			eventData = {
				title: title,
				start: start,
				end: end
			};
			$('#calendar').fullCalendar('renderEvent', eventData, true); // stick? = true
		}
		$('#calendar').fullCalendar('unselect');
	},
	slotDuration: '00:15:00',
	editable: true,
	eventLimit: true, // allow "more" link when too many events
	minTime: dayStart,
	maxTime: dayFinish == '00:00' ? '24:00:00' : dayFinish,
	events: clients,
	eventRender: function(event, element) {
			var title = [event.tel, event.email, event.usl].filter(val => val != '').join(', ');
			$('<div class="fc-ther_info"> '+title+'</div>').insertAfter($(element).find('.fc-title, .fc-list-item-title a'));
		},
	eventDurationEditable: false,
	dayRender: function( date, cell ) {
		if (bus_hour.objIndexOf(moment(date._d).format('YYYY-MM-DD')) < 0) {
		$(cell).addClass('fc-nonbusiness');
		}
	},
	eventDrop: function(event, delta, revertFunc) {
		if (bus_hour.objIndexOf(moment(event.start._d).format('YYYY-MM-DD')) < 0) {
		revertFunc();
		}
		else {
	// var data = {
			// action: 'saveDropChange',
			// id: event.id,
			// date: moment(event.start).format('DD.MM.YYYY HH:mm')
			
		// };
		// jQuery.ajax( {
		// url:oz_vars.adminAjax,
		// data:data,
		// method:'POST',
		// beforeSend: function() {
			// $('body').addClass('oz_calen_load');
		// },
		// success: function(response,status) {
		// $('body').removeClass('oz_calen_load');
		// },
		// error: function(err,st) {
			// $('body').removeClass('oz_calen_load');
			// alert('Error! '+st+' '+oz_alang.str2);
		// }
		// }); 
		}
	},
	eventResizeStop:  function( event, jsEvent, ui, view ) { }
});
}

jQuery('input.plav_gr').on('change', function() {
	jQuery('input.plav_gr').not(this).prop('checked', false);
		$('[data-graphs]').addClass('hide');
		$('[data-graphs="'+$(this).attr('name')+'"]').removeClass('hide');
});

	jQuery('.add-date').click(function() {
		removeClick = true;
		var table = $(this).parents('table');
		table.find('.add-block').removeClass('hide');
		table.find('#oz_start, #oz_end').val('');
		d = Math.round(Math.random() * 1000000); //jQuery('.times-line').length;
		table.data('oz_lines',d);
		if (!table.find('tbody tr.times-line').hasClass('empty')) {
		var name = (table.attr('data-text')) ? table.attr('data-text') : oz_alang.str3;
		table.find('tbody').append(
		'<tr id="line-'+d+'" class="times-line empty">'+
		'<td class="time-show">'+name+'</td>'+
		'<td class="oz_day oz_mon"><input name="oz_mon" id="oz_mon" type="checkbox"></td>'+
		'<td class="oz_day oz_tue"><input name="oz_tue" id="oz_tue" type="checkbox"></td>'+
		'<td class="oz_day oz_wed"><input name="oz_wed" id="oz_wed" type="checkbox"></td>'+
		'<td class="oz_day oz_thu"><input name="oz_thu" id="oz_thu" type="checkbox"></td>'+
		'<td class="oz_day oz_fri"><input name="oz_fri" id="oz_fri" type="checkbox"></td>'+
		'<td class="oz_day oz_sat"><input name="oz_sat" id="oz_sat" type="checkbox"></td>'+
		'<td class="oz_day oz_sun"><input name="oz_sun" id="oz_sun" type="checkbox"></td>'+
		'<td><span class="remove-date dashicons dashicons-no-alt"></span></td>'+
		'</tr>');
		}
		showDefault(table);
		
		if (table.hasClass('oz_worktime')) {
		jQuery.each(rasp,function(i,v) {
			jQuery(".oz_worktime tr#line-"+d+" .oz_day input#"+rasp[i].day).attr("disabled", true);
		});
	}
		
		table.find('.add-block').show();
	
	jQuery(this).addClass('hide');
	
	removeDate(table);

	});
	
jQuery('.add-date-time').click(function(e) {
		i = 0;
		time = '';
		var table = $(this).parents('table');
		if ($('#oz_stab').prop('checked')) {
		table.find('.at-time').each(function() {
			if (jQuery(this).val().length === 0) {
				jQuery(this).addClass('warning');
			}
			
			else {
			jQuery(this).removeClass('warning');
			i++;
			}
		});
		
		var oz_lines = jQuery(".oz_worktime").data('oz_lines');
		jQuery(".oz_worktime tr#line-"+oz_lines+" .oz_day input").each(function() {
			if (!jQuery(this).is(':checked') ) {
				jQuery(".oz_worktime tr#line-"+oz_lines).addClass('warning');
				setTimeout(function() {jQuery(".oz_worktime tr#line-"+oz_lines).removeClass('warning')},1000);
			}
			
			else {
				i++;
			}
		});
		
		
		if (i > 2) {
		jQuery(".oz_worktime tr#line-"+oz_lines).removeClass('warning');
		jQuery('.times-line .oz_day input:checked').each(function(){
			if (rasp.length) {
			if (rasp[0].hasOwnProperty('dayF')) {
				rasp = [];
			}
			}
	if (typeof rasp == 'string' || rasp.filter(function(v) {return v.days}).length > 0) {
		rasp = [];
		rasp.push({
			day:jQuery(this).attr('name'),
			start:jQuery('#oz_ras_start').val(),
			end:jQuery('#oz_ras_end').val(),
			id: jQuery(this).parents('.times-line').attr('id'),
			pId: postId
			})	
	}
	else {
	if (rasp.objIndexOf(jQuery(this).attr('name')) < 0) {
		rasp.push({
			day:jQuery(this).attr('name'),
			start:jQuery('#oz_ras_start').val(),
			end:jQuery('#oz_ras_end').val(),
			id: jQuery(this).parents('.times-line').attr('id'),
			pId: postId
			})
	}
	}
		});
		checkChecked(table);
		
		jQuery('.oz_worktime tr#line-'+oz_lines+' .time-show').text();
		jQuery('.oz_worktime .add-block').hide();
		$('.oz_worktime .times-line').removeClass('empty');
		jQuery('.oz_worktime .times-line td input').attr("disabled", true);
		jQuery(".oz_worktime tr#line-"+oz_lines+" .time-show").text(jQuery('#oz_ras_start').val()+' - '+jQuery('#oz_ras_end').val());
		jQuery('#time_arr').text(JSON.stringify(rasp)); //oz_raspis
		jQuery('#oz_raspis').val(JSON.stringify(rasp));
		removeClick = false;
		}
		
		}
		
		else {
		rasp = [];
		var v = 0;
		jQuery('.plav_grafh_block .oz_flex_container input').each(function() {
			if (jQuery(this).val().length === 0) {
				jQuery(this).addClass('warning');
			}
			
			else {
			jQuery(this).removeClass('warning');
			v++;
			}
		});
		if (v > 0) {
		rasp.push({
			dayF:jQuery('#oz_first_day').val(),
			start:jQuery('#oz_ras_start1').val(),
			end:jQuery('#oz_ras_end1').val(),
			graph: jQuery('#oz_rab1').val()+'/'+jQuery('#oz_rab2').val(),
			pId: postId
			});
		jQuery('#oz_raspis').val(JSON.stringify(rasp));	
		$('.plav_grafh_block').addClass('hide');
		$('.plav_time_text b').text(rasp[0].graph+' '+oz_alang.str4+' '+rasp[0].start+' '+oz_alang.str5+' '+rasp[0].end+'. '+oz_alang.str6+' '+rasp[0].dayF);
		
		}
		}
	});
	
/* старался сделать функционал назначения времени универсальным. это для break time*/
jQuery('.add-break-time').click(function(e) {
	i = 0;
		time = '';
		var table = $(this).parents('table');
		table.find('.at-time').each(function() {
			if (jQuery(this).val().length === 0) {
				jQuery(this).addClass('warning');
			}
			
			else {
			jQuery(this).removeClass('warning');
			i++;
			}
		});
		var oz_lines = table.data('oz_lines');
		table.find("tr#line-"+oz_lines+" .oz_day input").each(function() {
			if (!jQuery(this).is(':checked') ) {
				table.find("tr#line-"+oz_lines).addClass('warning');
				setTimeout(function() {table.find("tr#line-"+oz_lines).removeClass('warning')},1000);
			}
			
			else {
				i++;
			}
		});
		
		if (i > 2) {
		table.find('tr#line-'+oz_lines+' .time-show').text();
		table.find('.add-block').hide();
		table.find('.times-line').removeClass('empty');
		table.find('.times-line td input').attr("disabled", true);
		
		table.find('tr#line-'+oz_lines+' .time-show')
		.attr('data-start',table.find('#oz_start').val())
		.attr('data-end',table.find('#oz_end').val())
		.text(table.find('#oz_start').val()+' - '+table.find('#oz_end').val());
		
		
		table.find("tr#line-"+oz_lines).removeClass('warning');
		var values = jQuery('input#'+table.attr('data-values'));
		addValueTimelist(table,values);
		
		checkChecked(table);
		
		removeClick = false;
		}
});
	
	removeDate();
	
	if (jQuery('.at-time').length) {
	let stepMin = (typeof oz_vars.timeslot !== 'undefined') ? parseInt(oz_vars.timeslot) : 15
		stepMin = $('[name="oz_ind_timeslot"]').val() > 0 ? parseInt($('[name="oz_ind_timeslot"]').val()) : stepMin
	jQuery('.at-time').timepicker({
		controlType: 'select',
		stepMinute: 5, //stepMin,
		oneLine: true,
		onSelect: function (datetimeText, datepickerInstance) {
			if ($(this).attr('id') == 'oz_ras_start') {
			var se = parseInt($(this).val().split(':')[0]);
			$('#oz_ras_end').timepicker('option', 'hourMin', se);
			}
		}
		});
	}
			checkChecked();
});

 })(jQuery);

/*********************end сотрудники*****************************/

/*********************клиенты*****************************/

(function( $ ) {

$(document).ready(function() {
		$('.filter-datepicker').datepicker({dateFormat: 'dd.mm.yy'});
	
// ver 2.1.8
$('.post-type-personal #publish').click(function(event) {
 if ($('input#oz_raspis').val() == '') {
	alert(oz_alang.str9);
	event.preventDefault();
 }	 
});
	
	// ver 2.1.1 colorpickers
	if ($('.oz_colors').length > 0) {
		$('.oz_colors').wpColorPicker();
	}
	if ($('#oz_custom_tel_placeholder_flags').length > 0) {
	$('#oz_custom_tel_placeholder_flags').intlTelInput({
	nationalMode: false,
	utilsScript: oz_vars.scriptPath+"/js/utils.intlTelInput.min.js",
	initialCountry: 'auto',
	geoIpLookup: function(callback) {
				var countryCode = '';
				var defaultCountry = (typeof oz_vars.telCountry !== 'undefined') ? oz_vars.telCountry : '';
				if (defaultCountry !== '') {
					var countryCode = defaultCountry;
				}
				else {
					$.ajax("https://ipinfo.io", {dataType:"json"}).done(function(resp) {
					var countryCode = (resp && resp.country) ? resp.country.toLowerCase() : "";
					$('input[name="oz_custom_tel_country"]').val(countryCode);
					});
				}
				callback(countryCode);
				},
	autoHideDialCode:true,
	formatOnDisplay: 'nationalMode',
	customPlaceholder: function(selectedCountryPlaceholder, selectedCountryData) {
		var selectedCountryPlaceholder = ($('#oz_custom_tel_placeholder').val() !== '') ? $('#oz_custom_tel_placeholder').val() :  selectedCountryPlaceholder;
		return selectedCountryPlaceholder;
	}
	});

		$('#oz_custom_tel_placeholder_flags').on("countrychange", function(e) { 
		 var code = $('#oz_custom_tel_placeholder_flags').intlTelInput('getSelectedCountryData');
		 $('input[name="oz_custom_tel_country"]').val(code.iso2);
		});	
	}
});

	$('.oz_select .oz_li_def').click(function() {
		var par = $(this).parent();
		if (par.hasClass('open')) {
			par.removeClass('open');
			return;
		}
		$('.oz_select').removeClass('open');
		par.addClass('open');
	});
	
	$('body').click(function(e) {
		if (!e.target.closest('.oz_select')) {
			$('.oz_select').removeClass('open');
		}
	});
	
	$('.oz_li_sub_li').click(function() {
		var li = $(this);
		var url = li.attr('data-url');
		$(this).parent().parent().siblings('.oz_li_def').attr('data-values', li.attr('data-value')).html(li.html());
		$(this).parent().find('.oz_li_sub_li').removeClass('active');
		$(this).addClass('active');
		var status_url = $(this).parent().parent().find('.oz_status_url');
		$('.oz_notify-icon').each(function() {
			var noty = $(this).attr('data-url');
			$(this).removeClass('active');
			var replace_url = status_url.attr('href').replace(noty,''); 
			status_url.attr('href',replace_url);
		});
		if (status_url.attr('href').indexOf(url) > -1) {
			var replace_url = status_url.attr('href').replace(url, '');
			status_url.attr('href',replace_url);
		}
		else {
			status_url.attr('href',url);
		}
	});
	
	$('.oz_notify-icon').click(function() {
		var status_url = $(this).parents('.oz_li_sub_li_buttons').find('.oz_status_url');
		var this_url = $(this).attr('data-url');
		if (status_url.attr('href').indexOf(this_url) > -1) {
			var remove_noty = status_url.attr('href').replace(this_url, '');
			status_url.attr('href',remove_noty);
		}
		else {
		  status_url.attr('href',status_url.attr('href')+this_url);
		}
		$(this).toggleClass('active');
	});
	
	$('.oz_has_sub_options').on('change ready', function() {
		let isSelect = $(this).is('select')
		var tr = $(this).attr('name');
		let val = $('.oz_sub_option[data-show="'+tr+'"]')
		if (isSelect) {
			let select = $(this)
			val = $('.oz_sub_option[data-select="'+select.attr('name')+'"][data-show="'+select.val()+'"]')
			$('.oz_sub_option[data-select="'+select.attr('name')+'"]').addClass('hide')
			val.removeClass('hide')
		}
		else {
			if ($(this).prop('checked')) {
			$('.oz_sub_option[data-show="'+tr+'"]').removeClass('hide');
			}
			else {
			$('.oz_sub_option[data-show="'+tr+'"]').addClass('hide');
			}
		}
	});
	
	$(document).ready(function() {
		let data = window.intlTelInputGlobals ? window.intlTelInputGlobals.getCountryData().map(el => {return {text:el.name, id:el.iso2}}) : []
		if (jQuery('.oz_tel_select2').length) {
			jQuery('.oz_tel_select2').select2({
				multiple: true,
				data 
			});
		}	

		$('.oz_has_sub_options').each(function() {
		let isSelect = $(this).is('select')
		var tr = $(this).attr('name');
		let val = $('.oz_sub_option[data-show="'+tr+'"]')
		if (isSelect) {
			let select = $(this)
			val = $('.oz_sub_option[data-select="'+select.attr('name')+'"][data-show="'+select.val()+'"]')
			$('.oz_sub_option[data-select="'+select.attr('name')+'"]').addClass('hide')
			val.removeClass('hide')
		}
		else {
			if ($(this).prop('checked')) {
			val.removeClass('hide');
			}
			else {
			val.addClass('hide');
			}			
		}			
		});
		
		if ($('.oz_steps_seq').length) {
			$('.oz_steps_seq').sortable({
			items: "div:not(.ui-state-disabled)",
			update: function(event, ui) {
				console.log(event, ui)
				let steps = []
				$('.oz_steps_seq .oz_step:not(.ui-sortable-placeholder)').each(function(i,val) {
					steps.push($(this).attr('data-step'))
				})
				var event= new CustomEvent('onDragChanged', {detail:steps});
				document.addEventListener('onDragChanged',function(){},false);
				document.dispatchEvent(event);
				$('[name="oz_step_sequence"]').val(JSON.stringify(steps))
			}
			})
		}
	});
	
	
	
/* чтоб не исполнялось в других местах. только в клиентах */
if (oz_vars.post_type !== 'clients') { 
	return;
}
time = '';
function book_oz_plav_bus_days(rasp) {
	
	 var start = moment();
	
		var postG = [];
		var postV = [];
		var postG_plav_bus_days = [];
		var postV_bus_days = [];
		var plav_bus_days = [];
		var postC = []; // custom graph
		var postC_custom_days = []; // custom graph
		 $.each(rasp, function(index) {
			if (typeof rasp[index].dayF != 'undefined') {
				postG.push(rasp[index]);
			}
			
			else if (typeof rasp[index].day != 'undefined') {
				postV.push(rasp[index]);
			}
			
			else if (typeof rasp[index].days != 'undefined') {
				postC.push(rasp[index]);
			}
		});
		
		if (postC.length) {
			for (k=0; k<postC.length; k++) {
				var dates = postC[k].days;
				
				for (l=0; l<dates.length; l++) {
					var date = dates[l];
					postC_custom_days.push({
					 day: moment(date,'DD.MM.YYYY').format('YYYY-MM-DD'),
					 start: postC[k].time.start,
					 end: postC[k].time.end,
					 pId: postC[k].pId
					})
				}
			}
		}
		
		if (postG.length) {
			$.each(postG, function(i,v) {
			 var now = moment(v.dayF,'DD.MM.YYYY');
			 var rabd = v.graph.split('/')[0];
			 var vihd = v.graph.split('/')[1];		
			 var rab = 0;
			 var pId = (v.pId) ? v.pId : '';
			 for (i=0; i < 180; i++) {
				 if (rab == rabd) {
				now.add(vihd, 'days');	
				rab = 0;
				 }
				 postG_plav_bus_days.push({
					 day: now.format('YYYY-MM-DD'),
					 start: v.start,
					 end: v.end,
					 pId
				 }) 
				 rab++;
				 now.add(1, 'days');
			 }			 
			});
		}
		
		if (postV.length) {
			jQuery.each(postV, function(i,v) {
			var now = moment();
		 for (i=0; i < 180; i++) {
		var dayName = now.locale('en').format('ddd').toLowerCase();
			 if ('oz_'+dayName == v.day) {
				 now.format('YYYY-MM-DD');
				 var pId = (v.pId) ? v.pId : '';
			 postV_bus_days.push({
				 day: now.format('YYYY-MM-DD'),
				 start: v.start,
				 end: v.end,
				 pId
			 }) 
			 } 
			 now.add(1, 'days');
		 }
			})
		}
		plav_bus_days = postG_plav_bus_days.concat(postV_bus_days, postC_custom_days);
			

		var end = moment() - start;
		console.log('Время генерации рабочих дней: '+end+'мс');
	 
	 return plav_bus_days;
  }
 
/* рандомное значение из массива */ 
function book_oz_randFromArray(arr) {
	return arr[Math.floor(Math.random()*arr.length)];
}

function book_oz_checkCurrentZapisi(dateText,emp, currentClient = false) {
	/*
	currentClient - вычитаем этого клиента из списка записанных к специалисту. 
	Используется для вывода времени у уже записанных
	*/
	var CurrentZapisi = [];
	var currentClient = (currentClient) ? currentClient : '';
	var data = {
		action: 'checkCurrentZapisi',
		dateText: dateText,
		currentClient,
		};
		$.ajax( {
		data:data,
		type:'POST',
		url:oz_vars.adminAjax,
		success: function(zapisi,status) {
		book_oz_onCheckCurrentZapisi(dateText,emp,zapisi);
		}
		})
}

function book_oz_onCheckCurrentZapisi (dateText,emp,zapisi) {
	var timeList = book_oz_forTime(dateText,emp,zapisi);
		book_oz_printTime(timeList);
}

function book_oz_forTime(dateText = 0, emp = 0, zapisi = 0) {
		/*
		@ генерация массива времени
		dateText  	- выбранная дата
		emp 		- массив с персоналом
		*/
				console.log('Найдено сотрудников: '+emp.length);
				var timeList = []; // лист доступного времени на выбранную дату
				timeB		= JSON.parse(zapisi);
		
		for (s=0; s < emp.length; s++) {
			var dHstart 	= emp[s].start.split(':')[0],
				dMStart 	= parseInt(emp[s].start.split(':')[1]),
				dHFinish 	= emp[s].end.split(':')[0],
				dMFinish 	= emp[s].end.split(':')[1],
				pId			= emp[s].pId,
				b			= (typeof oz_vars.timeslot !== 'undefined') ? parseInt(oz_vars.timeslot) : 15; // посколько минут прибавлять
			
		/*если работает до 24 часов, то округляем часы на 24:00*/
		if (dHFinish == 0 && dMFinish == 0) {
			dHFinish = 24
		}
		let addTime = 0
		/* проверка текущего времени */
		var today = moment().format('DD.MM.YYYY');
		timeB.forEach(function (img) {
				if (typeof img.buffer != 'undefined') {
					if (img.buffer[0]) {
						img.start = moment(img.start, 'HH:mm').subtract(img.buffer[0], 'minutes').format('HH:mm')
						img.w_time = Number(img.w_time) + Number(img.buffer[0])
					}

					if (img.buffer[1]) {
						img.w_time = Number(img.w_time) + Number(img.buffer[1])
					}

				}
			})
    for(var i = (dHstart*60)+dMStart; i <= 1440; i += b){
		addTime = i
		
        hours = Math.floor(i / 60);
        minutes = i % 60;
        if (minutes < 10){
            minutes = '0' + minutes;
        }
		
		if (hours < 10) {
			hours= '0' + hours;
		}
		
if (
	hours === 24 || 
	hours < dHstart || 
	(hours == dHstart && minutes < dMStart ) ||
	hours > dHFinish ||
	(hours == dHFinish && minutes > dMFinish)
	) {
		continue;	
		}
		
		let max_w_time = 0; // если в одно и тоже время несколько записей, ищем самую длинную
		let busy = false;
		timeB.forEach(function (img) {
			if ((typeof img.pId != 'undefined' && img.pId == pId)) {
				let app = moment(img.start, 'HH:mm');
				app = (app.hour()*60) + app.minute();
				if (app == addTime || addTime > app && addTime < app + img.w_time) {
						if (img.w_time > max_w_time) {
						max_w_time = img.w_time;
						busy = Number(app) + Number(img.w_time)
						}
				}
			}
			})
		var index = timeB.map(function (img) { 
		let st = img.start
		if (typeof img.buffer != 'undefined' && img.buffer[0]) {
			st = moment(st, 'HH:mm').subtract(img.buffer[0], 'minutes').format('HH:mm')
		}
		return st; 
		}).indexOf(hours + ':' + minutes);
		if (busy > addTime) {
				// nothing
		}
		
		else {
			busy = false
			if ($('select[name="oz_uslug_set"]').length) {
				var mtime = $('select[name="oz_uslug_set"] option:selected').attr('data-servtime');
				var z = 0;
				var tectime = hours+':'+minutes;
				/* не выводить время если конец рабочего дня*/
				var forEnd = Math.abs(parseInt(moment(dHFinish+':'+dMFinish, "h:mm").format('X')) - parseInt(moment(tectime, "h:mm").format('X')) )/60;
				if (forEnd < mtime) {
					continue;
				}
				/* не выводить время если выбранная услуга длится дольше ближайшей записи */
				for (k=0; k < timeB.length; k++ ) {
					let start = moment(timeB[k].start, "h:mm")
					if (typeof timeB[k].buffer != 'undefined') {
						start.subtract(timeB[k].buffer[0], 'minutes').format('HH:mm')
					}
					var fornow =  (parseInt(start.format('X')) - parseInt(moment(tectime, "h:mm").format('X')) )/60;
					let isSame = typeof timeB[k].buffer != 'undefined' && timeB[k].buffer[0] ? fornow <= mtime : fornow < mtime
					if (fornow > 0 && isSame && timeB[k].pId == pId) {
						z++;		
					}
				}
				
				if (z) {
					continue;
				}
			}
				if (pId) {
					timeList.push({'time':hours + ':' + minutes,pId});
				}
				else {
					timeList.push(hours + ':' + minutes);
				}	
		}
		
    }
		}
	/*убираем из массива уже прошедшее за сегодня время*/
	if (today == dateText) {
	timeList = book_oz_remove_prosh_vr(timeList);
	}
	return timeList;
		}
		
/*убираем из текущего дня уже прошедшее время*/
function book_oz_remove_prosh_vr(time) {
	var ttime = new Array();
	var tnow = moment().format('X');
	for (i=0;i<time.length;i++) {
	var t = (typeof time[i].time == 'undefined') ? time[i] : time[i].time;
	var tech = moment(t, "h:mm").format('X');
		if (tech > tnow) {
		ttime.push(time[i]);
		}
	}
	return ttime;
}

function book_oz_printTime(timeList) {
	var checked = '';
	timeList = timeList.sort(function(a,b){
		var a = (a.time) ? a.time : a;
		var b = (b.time) ? b.time : b;		
		return parseInt(a.replace(':','')) - parseInt(b.replace(':',''));
	});
	jQuery('.timeRange').remove();
	$('#oz_time_rot').parents('.at-field').append('<ul class="timeRange" />');
	for (var i=0; i<timeList.length; i++) {
		if (typeof timeList[i].time !== 'undefined' && $('input.checkb[value="'+timeList[i].time+'"]').length) {
			$('input.checkb[value="'+timeList[i].time+'"]').attr('data-pId',$('input.checkb[value="'+timeList[i].time+'"]').attr('data-Pid')+','+timeList[i].pId);
		}
		else {
	jQuery('.timeRange').append(' <li class="squaredThree">'+
      '<input class="checkb" data-pId="'+timeList[i].pId+'" type="checkbox" '+checked+' value="'+timeList[i].time+'" name="oz_time_rot_block" />'+
		'<label for="squaredThree">'+book_oz_timeFormat(timeList[i].time)+'</label>'+
	  '</li>');
		}
	}
	
	book_oz_viborVremeni();
	var event= new CustomEvent('onTimeListRender');
	document.addEventListener('onTimeListRender',function(){},false);
	document.dispatchEvent(event);
}

function book_oz_checkFreeTimeSpec(spec,t) {
		var post_ID = ($('input[name="post_ID"]')) ? $('input[name="post_ID"]').val() : '';
 		var data = {
					action: 'checkSvobTime',
					nonce : oz_vars.nonce,
					spec:spec,
					time:t,
					date: $('input[name="oz_start_date_field_id"]').val(),
					servtime: $('select[name="oz_uslug_set"] option:selected').attr('data-servtime'),
					post_ID
				};
		$.ajax( {
		data:data,
		type:'POST',
		url:oz_vars.adminAjax,
		success: function(response,status) {
		if (response && response !== 'ok' ) {
			alert(response);
			$('.squaredThree').removeClass('active');
			$('input[name="oz_time_rot"]').val('');
		}
		}
		}); 
}

function book_oz_viborVremeni() {
	$('.squaredThree').click(function() {
		$('.squaredThree').removeClass('active');
		$(this).addClass('active');
		var t = $(this).find('input').val();
		var speci = $(this).find('input').attr('data-pid').split(',');
		$('input[name="oz_time_rot"]').val(t);
		jQuery('.at-posts-select[name="oz_personal_field_id"] option').not(':first').prop('disabled',true);
		for (var i = 0; i< speci.length; i++) {
			jQuery('.at-posts-select[name="oz_personal_field_id"] option[value="'+speci[i]+'"]').prop('disabled',false);
			book_oz_checkFreeTimeSpec(speci[i],t);
		}
	});
}

function book_oz_disabledSpec() {
	if ( jQuery('.at-posts-select[name="oz_uslug_set"]').val() == -1 ) {
		jQuery('.at-posts-select[name="oz_personal_field_id"]').prop('disabled',true);
	}
	
	else {
		jQuery('.at-posts-select[name="oz_personal_field_id"]').prop('disabled',false);
	}
}

book_oz_disabledSpec();

function book_oz_checkIfClientExist() {
	/*
	проверяем если клиент создан, то выдаем выбранное дату и время
	*/
	var emp = [];
	var dateText;
	var flagTime = 1;
	if ($('.hidenextTr').length) {
		$('.hidenextTr').next().hide();
		$('.hidenextTr span').click(function() {
		$('.hidenextTr').hide().next().show();	
		});
	}
	var select = $('.at-posts-select[name="oz_personal_field_id"]').val(); 
	console.log(select)
	book_oz_reDatePersonal(select);
	var dateText = $('input[name="oz_start_date_field_id"]').val();
	var currentClient = $('input[name="post_ID"]').val();
	$.each($('.ui-datepicker-current-day').attr('class').split(' '), function(i,v) {
		if (v.indexOf('time-') > -1) {
			var pId = v.split('-')[3];
			emp.push({'start':v.split('-')[1],'end':v.split('-')[2], pId});
		}
	});
	book_oz_checkCurrentZapisi(dateText,emp,currentClient);
	var time = $('input[name="oz_time_rot"').val();
	/* когда вывелось время на страницу */
	document.addEventListener('onTimeListRender',function(e){
			if (flagTime > 0) {
		$('.checkb[value="'+time+'"]').parents('.squaredThree').addClass('active');	
		flagTime--;
			}
	},false);
}

function book_oz_reDatePersonal(select,def = 0) {
	/*
	select - список id сотрудников
	*/
$.datepicker.setDefaults(book_oz_setLang);
$('#datePickerInput').datepicker( "destroy" );
if (def <=-1 || select <=-1) return;
var TimeWeek = [];
if (typeof select == 'string') {
	var arr = select.split(',');
 	for (i=0;i<arr.length;i++) {
		var sotrudnik = jQuery('select[name="oz_personal_field_id"] option[value="'+arr[i]+'"]');
		if (sotrudnik.length) {
			var timeweek = JSON.parse(sotrudnik.attr('data-days'));
			sotrudnik.attr('disabled',false);
			for (t=0;t<timeweek.length;t++) {
				TimeWeek.push(timeweek[t]);
			}
		}
	}
		//$('.at-posts-select[name="oz_personal_field_id"]').select2('destroy').select2();
}	
//TimeWeek = JSON.parse(jQuery('select[name="oz_personal_field_id"] option:selected').attr('data-days'));
time = [];
time =  book_oz_plav_bus_days(TimeWeek);
jQuery('#datePickerInput').datepicker({
	minDate: 0,
	maxDate: '6M',
	dateFormat: "dd.mm.yy",
	defaultDate: jQuery('#oz_start_date_field_id').val(),
	beforeShowDay: function(date){
				var popup;
				if (typeof time == 'undefined') return [ 1, ];
				var AllIndexes = [];
				var day = jQuery.datepicker.formatDate('yy-mm-dd', date);
				var today = moment().format('YYYY-MM-DD');
				var ind = time.map(function(obj,ind) { if (obj.day == day) {AllIndexes.push(time[ind])} return obj.day }).indexOf(day);
				var fal = 0;
				if (ind > -1) {
					if (AllIndexes.length) {
						var popup = '';
						for (var key in AllIndexes) {
							popup += (typeof AllIndexes[key].start !== 'undefined') ? 'time-'+AllIndexes[key].start+'-'+AllIndexes[key].end+'-'+AllIndexes[key].pId+' ' : ''; // поставил условие, т.к. не понимаю почему появляется undefined
							if (day == today && moment() >= moment(AllIndexes[key].end,'HH:mm') ) {
								var fal = 0;
							} 
							else {
								var fal = 1;
							}
						}
					}
				}
				
				var daysoff = {}
				if (document.querySelector('[name="oz_personal_field_id"]') && document.querySelector('[name="oz_personal_field_id"]').children) {
					var sel = document.querySelector('[name="oz_personal_field_id"]').children
					for (let key in sel) {
						if (sel[key].dataset && typeof sel[key].dataset.daysoff != 'undefined') {
						daysoff[sel[key].value] = sel[key].dataset.daysoff.split(',')
						}
					}
					let fal_or_popup = book_oz_daysOff(fal,date, [daysoff], AllIndexes, popup);
					if (typeof fal_or_popup == 'string') popup = fal_or_popup; else fal = fal_or_popup;
				}
				
						return [ fal, popup, ];
			},
	onSelect: function(dateText,inst) {
			var day  = inst.selectedDay,
                    mon  = inst.selectedMonth,
                    year = inst.selectedYear;

                var td = jQuery(inst.dpDiv).find('[data-year="'+year+'"][data-month="'+mon+'"]').filter(function() {
                    return jQuery(this).find('a').text().trim() == day;
                });
			var date = jQuery(this).datepicker('getDate');
			var dayOfWeek = date.getUTCDay();
			var minT, maxT;
			min = 9999;
			max = 0;
			var arrMin = [];
			var arrMax = [];
			var emp = [];
			jQuery.each(jQuery(td).attr('class').split(' '), function(i,v) {
				if (v.indexOf('time-') > -1) {
				var t = parseInt(v.split('-')[1].replace(':',''));
				var n = parseInt(v.split('-')[2].replace(':',''));
				var pId = (v.split('-')[3]) ? v.split('-')[3] : '';
				emp.push({'start':v.split('-')[1],'end':v.split('-')[2], pId});
				arrMin.push({'min':v.split('-')[1], pId});
				arrMax.push(v.split('-')[2]);
				var minR = 15;
				var min = (arrMin) ? arrMin : TimeWeek[0].start;
				var max = (arrMax) ? arrMax : TimeWeek[0].end;
				var clientRas = '';
				//generTimeList(min,max,minR,'',i,dateText); // общий массив сотрудников работающих в этот день
			var min,max,minT,maxT;
			}
			});
			var currentClient = $('input[name="post_ID"]').val(); /* id клиента */
			book_oz_checkCurrentZapisi(dateText,emp,currentClient);
			$('input[name="oz_start_date_field_id"]').val(dateText);
			$('.if_not_set_date').addClass('hide');
}
});
}

$(document).ready(function() {

	
	$('<span class="if_not_set if_not_set_serv hide">'+oz_alang.str7+'</span>').insertBefore('#datePickerInput');
	$('<span class="if_not_set if_not_set_date hide">'+oz_alang.str8+'</span>').insertBefore('#oz_time_rot');
	if ($('.at-posts-select[name="oz_personal_field_id"]').length && $('.at-posts-select[name="oz_personal_field_id"]').val() > -1) {
		if ($('.at-posts-select[name="oz_personal_field_id"]').val() > -1) {
				var uslugi = jQuery('.at-posts-select[name="oz_uslug_set"] option');
				var emp = $('.at-posts-select[name="oz_personal_field_id"]').val()
				if (emp > -1) {
				$('.if_not_set_serv').addClass('hide');
				uslugi.each(function() {
				if (jQuery(this).val() > -1) {
					var speci = jQuery(this).attr('data-pers').split(',');
					if ( speci.indexOf(emp) > -1) {
					jQuery(this).prop('disabled',false);
					}
					else {
					jQuery(this).prop('disabled',true);
					}
				}
				});
		}
		book_oz_checkIfClientExist();
		}
	}
	
	else if ($('.at-posts-select[name="oz_personal_field_id"] option').length == 2) {
		var emp = $('.at-posts-select[name="oz_personal_field_id"]').find('option:not([value="-1"])')
		if (emp.length) {
		$('.at-posts-select[name="oz_personal_field_id"]').val(emp.val())
		$('.at-posts-select[name="oz_personal_field_id"]').trigger('change')
		}
	}
	else {
		$('.if_not_set').removeClass('hide');
	}
	
	var publishing = false;
	/* проверяем на клик "обновить" обязательные поля, а также если не выбран спец, то автоматически прикрепляем запись к любому свободному */  
  $('#publish').click(function( event ) {
		if (publishing) return;
		event.preventDefault();
		/* обязательные поля для заполнения */
		var reqArr = [
		'*[name="oz_start_date_field_id"]',
		'*[name="oz_uslug_set"]',
		'*[name="oz_time_rot"]',
		'*[name="oz_personal_field_id"]'
		];
		var reqPolya = true;
		$.each(reqArr, function(i,v) {
		if ($(v).val() == '' || $(v).val() < 0) {
		$(v).parents('td').addClass('red').prepend('<span style="color:red;" class="oz_req"> Required field! </span>');
		setTimeout(function() {$(v).removeClass('red'); $('.oz_req').remove();},1500);
		reqPolya = false;
		}
			else {
				/* если это поле время, а спец не заполнен выбираем рандомно свободного спеца */
				if (i == 2 && $('*[name="oz_personal_field_id"]').val() < 0) {
					$('*[name="oz_personal_field_id"]').val(book_oz_randFromArray($('.checkb[value="'+$(v).val()+'"]').attr('data-pId').split(',')));
				}
			}
	});
		if (reqPolya) {
		publishing = true;
        var postID=$('#post_ID').attr('value');
		$('#publish').click();
		}
	});
});

 jQuery('.at-posts-select[name="oz_personal_field_id"]').change(function() { 
	jQuery('.timeRange').remove();
	$('input[name="oz_start_date_field_id"]').val('');
	$('input[name="oz_time_rot"]').val('');
	var select = jQuery(this).val();
	var uslugi = jQuery('.at-posts-select[name="oz_uslug_set"] option');
	if (select > -1) {
	$('.if_not_set_serv').addClass('hide');
	uslugi.each(function() {
	if (jQuery(this).val() > -1) {
		var speci = jQuery(this).attr('data-pers').split(',');
		if ( speci.indexOf(select) > -1) {
		jQuery(this).prop('disabled',false);
		}
		else {
		jQuery(this).prop('disabled',true);
		}
	}
	});
	}
	else {
		uslugi.prop('disabled',false);
		$('.if_not_set_serv, .if_not_set_date').removeClass('hide');
		$('.at-posts-select[name="oz_uslug_set"]').val(-1);
		$('input[name="oz_time_rot"], input[name="oz_start_date_field_id"]').val('');
	}
	if ($('.at-posts-select[name="oz_uslug_set"]').hasClass('select2-hidden-accessible')) $('.at-posts-select[name="oz_uslug_set"]').select2('destroy').select2();
	book_oz_reDatePersonal(select);
});

$('.at-posts-select[name="oz_uslug_set"], .at-posts-select[name="oz_personal_field_id"]').on('select2:select', function() {
		$(this).select2('destroy').select2();
	});

$('.at-posts-select[name="oz_uslug_set"]').change(function() {
	$('.timeRange').remove();
	$('input[name="oz_start_date_field_id"]').val('');
	$('input[name="oz_time_rot"]').val('');
	book_oz_disabledSpec();
	if ($(this).find('option:selected').length > 1) {
		var select = '';
		var uslCount = $(this).find('option:selected').length;
		$(this).find('option:selected').each(function(i,v) {
			select += (select) ? ','+$(v).attr('data-pers') : $(v).attr('data-pers');
		});
		var select = select
					.split(',');
					//.filter( function( item, index, inputArray ) {return inputArray.indexOf(item) == index;});
		var sotid = [];
		$.each(select, function(i,v) {
			var countSpec = select.reduce(function(prev, cur, i, arr) { return prev + (cur === v);},0);
			if (countSpec >= uslCount) {
				sotid.push(v);
			}
		});
		//select.splice(i);
		var select = sotid.filter( function( item, index, inputArray ) {return inputArray.indexOf(item) == index;});
	}
	else {
	var select = $(this).find('option:selected').attr('data-pers');
	}
	var def = $(this).val();
	var sotrudniki = $('.at-posts-select[name="oz_personal_field_id"] option');
	if (def > -1 || (typeof def == 'object' && def.indexOf(-1) < 0)) {
	$('.if_not_set_serv').addClass('hide');
	var speci = -1;
	sotrudniki.each(function() {
	var speci = (select && typeof select == 'string') ? select.split(',') : -1;
	if (typeof select == 'object') {
	var speci = select;	
	}
		if ( (typeof speci !== 'number' && speci.indexOf($(this).val()) > -1 ) || $(this).val() == -1) {
		$(this).prop('disabled',false);
		}
		else {
			$(this).prop('disabled',true);
			}
		});
	}
	else {
		sotrudniki.prop('disabled',false);
		$('.if_not_set_serv, .if_not_set_date').removeClass('hide');
		$(this).find('option').prop('disabled',false);
		$(this).trigger('change.select2');
		$('.timeRange').remove();
		$('.at-posts-select[name="oz_personal_field_id"]').val(-1);
		$('input[name="oz_time_rot"], input[name="oz_start_date_field_id"]').val('');
	}
	/* если уже выбран сотрудник, то берем только его id */
	if ($('.at-posts-select[name="oz_personal_field_id"]').val() > -1) {
		var select = $('.at-posts-select[name="oz_personal_field_id"]').val();
	}
	$('.at-posts-select[name="oz_personal_field_id"]').select2('destroy').select2();
	book_oz_reDatePersonal(select,def);
});

// ver 2.1.5 hack for multiple service when manual adding
$('#publish').click(function(e) {
	var usl = $('.at-posts-select[name="oz_uslug_set"]');
	if (usl.length && typeof usl.val() == 'object' && usl.val() && $('#oz_time_rot').val()) {
	var tostring = Object.values(usl.val()).join(',');
	usl.find('option').attr('value', tostring);
	}
});

 })(jQuery);
